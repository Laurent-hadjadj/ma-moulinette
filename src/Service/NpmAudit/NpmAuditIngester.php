<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Service\NpmAudit;

use App\Entity\NpmDependency;
use App\Entity\NpmFinding;
use App\Entity\NpmProcessingQueue;
use App\Entity\NpmScan;
use App\Entity\NpmVulnerability;
use App\Repository\NpmDependencyRepository;
use App\Repository\NpmFindingRepository;
use App\Repository\NpmScanRepository;
use App\Repository\NpmVulnerabilityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Ingestion métier d'un rapport npm audit (worker `app:npm-audit:process`).
 *
 * Pipeline :
 *   1. décompresse payload_gz de la queue
 *   2. parse JSON : enveloppe {"full": {...npm audit --json...},
 *      "prodOnly"?: {...npm audit --omit=dev --json...},
 *      "installed"?: {...npm ls --all --json...}}
 *   3. crée NpmScan depuis "full" (compteurs repris directement de
 *      full.metadata, pas recalculés)
 *   4. pour chaque paquet de full.vulnerabilities :
 *        - findOrCreate NpmDependency (clé name)
 *        - résout la version installée depuis "installed" (si présent),
 *          voir flattenInstalledVersions()
 *        - pour chaque entrée OBJET de via[] (les entrées string sont de
 *          simples maillons de la chaîne de dépendance, pas des
 *          vulnérabilités) :
 *            - findOrCreate NpmVulnerability (clé GHSA-ID extrait de l'URL,
 *              repli sur un hash du titre)
 *            - crée NpmFinding (snapshot severity/cvss/range/installedVersion/fix)
 *   5. tout dans une transaction
 *   6. si "prodOnly" présent : croise après coup (hors transaction
 *      principale, simple UPDATE) pour déterminer NpmFinding::isProd
 *
 * Idempotence : si un NpmScan existe déjà pour (projet, version, scanDate),
 * on retourne l'existant sans rien refaire. scanDate = date de réception
 * de la queue (npm audit --json ne porte aucune date de scan).
 */
class NpmAuditIngester
{
    /**
     * Cache local par ingestion : évite que le même paquet/vulnérabilité
     * apparaissant plusieurs fois dans le même rapport ne génère plusieurs
     * persist() avec la même clé naturelle -> UniqueViolation au flush.
     *
     * @var array<string, NpmVulnerability>
     */
    private array $vulnerabilityCache = [];

    /** @var array<string, NpmDependency> */
    private array $dependencyCache = [];

    /** @var array<string, true> */
    private array $findingCache = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NpmVulnerabilityRepository $vulnerabilityRepository,
        private readonly NpmDependencyRepository $dependencyRepository,
        private readonly NpmScanRepository $scanRepository,
        private readonly NpmFindingRepository $findingRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function ingest(NpmProcessingQueue $queue): NpmScan
    {
        $this->vulnerabilityCache = [];
        $this->dependencyCache = [];
        $this->findingCache = [];

        $jsonString = $this->extractJson($queue);
        $envelope = $this->parseJson($jsonString);

        if (!isset($envelope['full']) || !is_array($envelope['full'])) {
            throw new NpmAuditIngestException('Bloc "full" absent de l\'enveloppe en queue.');
        }
        $full = $envelope['full'];
        $prodOnly = isset($envelope['prodOnly']) && is_array($envelope['prodOnly']) ? $envelope['prodOnly'] : null;
        $installed = isset($envelope['installed']) && is_array($envelope['installed']) ? $envelope['installed'] : null;
        $installedVersions = $installed !== null ? $this->flattenInstalledVersions($installed) : [];

        $group    = $queue->getProjectGroup();
        $artifact = $queue->getProjectArtifact();
        $version  = $queue->getProjectVersion();
        $scanDate = $queue->getCreatedAt();

        $existing = $this->scanRepository->findByProjectVersionDate($group, $artifact, $version, $scanDate);
        if ($existing !== null) {
            $this->logger->info('[NpmAudit-Ingester] Scan déjà existant, skip.', [
                'queue_ulid' => $queue->getUlid(),
                'scan_id'    => $existing->getId(),
            ]);
            return $existing;
        }

        $this->em->beginTransaction();
        try {
            $scan = $this->createScan($queue, $group, $artifact, $version, $scanDate, $full);
            $this->em->persist($scan);
            $this->em->flush(); // pour avoir scan->id

            foreach ($full['vulnerabilities'] ?? [] as $packageName => $packageRaw) {
                if (!is_array($packageRaw) || !is_string($packageName) || $packageName === '') {
                    continue;
                }

                $via = $packageRaw['via'] ?? [];
                if (!is_array($via)) {
                    continue;
                }

                $depEntity = $this->findOrCreateDependency($packageName);
                $isDirect = (bool) ($packageRaw['isDirect'] ?? false);
                $range = isset($packageRaw['range']) ? substr((string) $packageRaw['range'], 0, 255) : null;
                $installedVersion = $this->resolveInstalledVersion($installedVersions[$packageName] ?? null);
                [$fixAvailable, $fixToVersion, $fixIsSemVerMajor] = $this->parseFixAvailable($packageRaw['fixAvailable'] ?? false);

                foreach ($via as $viaRaw) {
                    // Une entrée string de via[] est un simple maillon de la
                    // chaîne de dépendance (autre paquet), pas une vulnérabilité.
                    if (!is_array($viaRaw)) {
                        continue;
                    }

                    $vulnEntity = $this->findOrCreateVulnerability($viaRaw);

                    $findingKey = spl_object_id($depEntity) . '|' . $vulnEntity->getAdvisoryId();
                    if (isset($this->findingCache[$findingKey])) {
                        continue;
                    }
                    $this->findingCache[$findingKey] = true;

                    $severity = $this->normalizeSeverity((string) ($viaRaw['severity'] ?? 'info'));
                    $cvssScore = isset($viaRaw['cvss']['score']) ? (string) $viaRaw['cvss']['score'] : null;

                    $finding = (new NpmFinding())
                        ->setScan($scan)
                        ->setDependency($depEntity)
                        ->setVulnerability($vulnEntity)
                        ->setSeverityAtScan($severity)
                        ->setCvssAtScan($cvssScore)
                        ->setRange($range)
                        ->setInstalledVersion($installedVersion)
                        ->setIsDirect($isDirect)
                        ->setFixAvailable($fixAvailable)
                        ->setFixToVersion($fixToVersion)
                        ->setFixIsSemVerMajor($fixIsSemVerMajor);

                    $this->em->persist($finding);
                }
            }

            $this->em->flush();
            $this->em->commit();

            $this->logger->info('[NpmAudit-Ingester] Scan ingéré.', [
                'queue_ulid'  => $queue->getUlid(),
                'scan_id'     => $scan->getId(),
                'project'     => sprintf('%s:%s:%s', $group, $artifact, $version),
                'vuln_total'  => $scan->getVulnCountTotal(),
                'has_prodonly' => $prodOnly !== null,
            ]);
        } catch (\Throwable $e) {
            $this->em->rollback();
            $this->logger->error('[NpmAudit-Ingester] Échec ingestion, rollback.', [
                'queue_ulid' => $queue->getUlid(),
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }

        // Croisement prod/dev — hors transaction principale (simple UPDATE,
        // un échec ici ne doit pas annuler l'ingestion elle-même déjà commitée).
        if ($prodOnly !== null) {
            try {
                $prodNames = array_values(array_filter(
                    array_keys($prodOnly['vulnerabilities'] ?? []),
                    static fn($v) => is_string($v)
                ));
                $this->findingRepository->markIsProdForScan((int) $scan->getId(), $prodNames);
            } catch (\Throwable $e) {
                $this->logger->warning('[NpmAudit-Ingester] ⚠️ Échec croisement prod/dev, isProd reste indéterminé.', [
                    'queue_ulid' => $queue->getUlid(),
                    'scan_id'    => $scan->getId(),
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return $scan;
    }

    /**
     * [Description for extractJson]
     *
     * @param NpmProcessingQueue $queue
     *
     * @return string
     *
     * Created at: 22/11/2026 18:44:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    private function extractJson(NpmProcessingQueue $queue): string
    {
        $gz = $queue->getPayloadGz();
        if (is_resource($gz)) {
            $gz = stream_get_contents($gz);
        }
        if (!is_string($gz) || $gz === '') {
            throw new NpmAuditIngestException('Payload queue vide ou illisible.');
        }
        $json = @gzdecode($gz);
        if ($json === false) {
            throw new NpmAuditIngestException('Décompression gzip échouée à l\'ingestion.');
        }
        return $json;
    }

    /**
     * [Description for parseJson]
     *
     * @param string $json
     *
     * @return array<string, mixed>
     *
     * Created at: 22/11/2026 18:44:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    private function parseJson(string $json): array
    {
        try {
            $parsed = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new NpmAuditIngestException('JSON invalide à l\'ingestion : ' . $e->getMessage(), 0, $e);
        }
        if (!is_array($parsed)) {
            throw new NpmAuditIngestException('JSON racine doit être un objet.');
        }
        return $parsed;
    }

    /**
     * [Description for createScan]
     *
     * @param array<string, mixed> $full
     *
     * @return NpmScan
     *
     * Created at: 22/11/2026 18:45:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    private function createScan(
        NpmProcessingQueue $queue,
        string $group,
        string $artifact,
        string $version,
        \DateTimeImmutable $scanDate,
        array $full,
    ): NpmScan {
        $vulnMeta = is_array($full['metadata']['vulnerabilities'] ?? null) ? $full['metadata']['vulnerabilities'] : [];
        $depMeta  = is_array($full['metadata']['dependencies'] ?? null) ? $full['metadata']['dependencies'] : [];

        return (new NpmScan())
            ->setQueue($queue)
            ->setProjectGroup($group)
            ->setProjectArtifact($artifact)
            ->setProjectVersion($version)
            ->setScanDate($scanDate)
            ->setAuditReportVersion(isset($full['auditReportVersion']) ? (string) $full['auditReportVersion'] : null)
            ->setVulnCountCritical((int) ($vulnMeta['critical'] ?? 0))
            ->setVulnCountHigh((int) ($vulnMeta['high'] ?? 0))
            ->setVulnCountModerate((int) ($vulnMeta['moderate'] ?? 0))
            ->setVulnCountLow((int) ($vulnMeta['low'] ?? 0))
            ->setVulnCountInfo((int) ($vulnMeta['info'] ?? 0))
            ->setVulnCountTotal((int) ($vulnMeta['total'] ?? 0))
            ->setDepCountProd((int) ($depMeta['prod'] ?? 0))
            ->setDepCountDev((int) ($depMeta['dev'] ?? 0))
            ->setDepCountOptional((int) ($depMeta['optional'] ?? 0))
            ->setDepCountPeer((int) ($depMeta['peer'] ?? 0))
            ->setDepCountPeerOptional((int) ($depMeta['peerOptional'] ?? 0))
            ->setDepCountTotal((int) ($depMeta['total'] ?? 0));
    }

    private function findOrCreateDependency(string $name): NpmDependency
    {
        if (isset($this->dependencyCache[$name])) {
            return $this->dependencyCache[$name];
        }

        $existing = $this->dependencyRepository->findByName($name);
        if ($existing !== null) {
            $existing->setLastSeenAt(new \DateTimeImmutable());
            $this->dependencyCache[$name] = $existing;
            return $existing;
        }

        $entity = (new NpmDependency())->setName($name);
        $this->em->persist($entity);
        $this->dependencyCache[$name] = $entity;
        return $entity;
    }

    /**
     * @param array<string, mixed> $viaRaw
     */
    private function findOrCreateVulnerability(array $viaRaw): NpmVulnerability
    {
        $url = isset($viaRaw['url']) ? (string) $viaRaw['url'] : '';
        $title = substr((string) ($viaRaw['title'] ?? ''), 0, 255);

        $advisoryId = $this->extractAdvisoryId($url, $title);

        if (isset($this->vulnerabilityCache[$advisoryId])) {
            return $this->vulnerabilityCache[$advisoryId];
        }

        $existing = $this->vulnerabilityRepository->findByAdvisoryId($advisoryId);
        if ($existing !== null) {
            $existing->setLastSeenAt(new \DateTimeImmutable());
            $this->vulnerabilityCache[$advisoryId] = $existing;
            return $existing;
        }

        $cvss = is_array($viaRaw['cvss'] ?? null) ? $viaRaw['cvss'] : [];
        $cwes = array_values(array_filter(
            (array) ($viaRaw['cwe'] ?? []),
            static fn($v) => is_string($v) && $v !== ''
        ));

        $entity = (new NpmVulnerability())
            ->setAdvisoryId($advisoryId)
            ->setTitle($title)
            ->setUrl($url !== '' ? substr($url, 0, 255) : null)
            ->setSeverity($this->normalizeSeverity((string) ($viaRaw['severity'] ?? 'info')))
            ->setCvssScore(isset($cvss['score']) ? (string) $cvss['score'] : null)
            ->setCvssVector(isset($cvss['vectorString']) ? substr((string) $cvss['vectorString'], 0, 255) : null)
            ->setCwes($cwes);

        $this->em->persist($entity);
        $this->vulnerabilityCache[$advisoryId] = $entity;
        return $entity;
    }

    /**
     * Clé naturelle de dédup globale : GHSA-ID extrait de via[].url, repli
     * sur un hash du titre si l'URL n'en contient aucun.
     */
    private function extractAdvisoryId(string $url, string $title): string
    {
        if (preg_match('/GHSA-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}/i', $url, $matches) === 1) {
            return strtoupper($matches[0]);
        }
        return 'TITLE-' . substr(sha1($title), 0, 16);
    }

    /**
     * @param mixed $raw vulnerabilities[name].fixAvailable : bool ou objet {name, version, isSemVerMajor}
     * @return array{0: bool, 1: ?string, 2: ?bool}
     */
    private function parseFixAvailable(mixed $raw): array
    {
        if (is_array($raw)) {
            return [
                true,
                isset($raw['version']) ? substr((string) $raw['version'], 0, 64) : null,
                isset($raw['isSemVerMajor']) ? (bool) $raw['isSemVerMajor'] : null,
            ];
        }
        return [(bool) $raw, null, null];
    }

    /**
     * Aplatit récursivement l'arbre `npm ls --all --json` ({"dependencies":
     * {"name": {"version": "x.y.z", "dependencies": {...}}}}) en
     * {nom de paquet => ensemble des versions distinctes trouvées dans
     * l'arbre}. Un même paquet peut apparaître à plusieurs versions
     * (conflit de résolution npm) : toutes sont conservées, pas seulement
     * la première rencontrée. Défensif : un nœud sans "version" exploitable
     * (paquet manquant/en erreur) est simplement ignoré, jamais une cause
     * d'échec de l'ingestion (ce rapport est optionnel).
     *
     * @param array<string, mixed> $node
     * @param array<string, array<string, true>> $acc
     * @return array<string, array<string, true>>
     */
    private function flattenInstalledVersions(array $node, array $acc = []): array
    {
        $deps = $node['dependencies'] ?? null;
        if (!is_array($deps)) {
            return $acc;
        }
        foreach ($deps as $name => $info) {
            if (!is_string($name) || $name === '' || !is_array($info)) {
                continue;
            }
            $version = $info['version'] ?? null;
            if (is_string($version) && $version !== '') {
                $acc[$name][$version] = true;
            }
            $acc = $this->flattenInstalledVersions($info, $acc);
        }
        return $acc;
    }

    /**
     * @param array<string, true>|null $versions ensemble des versions distinctes trouvées pour ce paquet
     */
    private function resolveInstalledVersion(?array $versions): ?string
    {
        if ($versions === null || $versions === []) {
            return null;
        }
        $sorted = array_keys($versions);
        sort($sorted, SORT_STRING);
        return substr(implode(', ', $sorted), 0, 255);
    }

    private function normalizeSeverity(string $raw): string
    {
        $low = strtolower(trim($raw));
        return match ($low) {
            'critical' => NpmVulnerability::SEVERITY_CRITICAL,
            'high'     => NpmVulnerability::SEVERITY_HIGH,
            'moderate', 'medium' => NpmVulnerability::SEVERITY_MODERATE,
            'low'      => NpmVulnerability::SEVERITY_LOW,
            default    => NpmVulnerability::SEVERITY_INFO,
        };
    }
}
