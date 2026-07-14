<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Service\DependencyCheck;

use App\Entity\DcCve;
use App\Entity\DcDependency;
use App\Entity\DcFinding;
use App\Entity\DcProcessingQueue;
use App\Entity\DcScan;
use App\Exception\DependencyCheck\{DcEmptyPayloadException,
    DcGzipDecodeException,
    DcInvalidJsonException,
    DcInvalidStructureException,
    DcPayloadException};
use App\Repository\DcCveRepository;
use App\Repository\DcDependencyRepository;
use App\Repository\DcScanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * [Description DependencyCheckIngester]
 * MODIF 2026-05-08 : ingestion metier d'un rapport DependencyCheck.
 *
 * Pipeline :
 *   1. décompresse le payload_gz de la queue
 *   2. parse JSON
 *   3. extrait scanInfo + projectInfo
 *   4. pour chaque dependency :
 *        - findOrCreate DcDependency (cle sha1)
 *        - pour chaque vulnerability :
 *            - findOrCreate DcCve (cle cve_id)
 *            - Crée DcFinding (snapshot severity/cvss)
 *   5. calcule compteurs et crée DcScan
 *   6. tout dans une transaction
 *
 * Idempotence : si un DcScan existe deja pour (projet, version, scan_date),
 * on retourne l'existant sans rien refaire.
 */
class DependencyCheckIngester
{
    /**
     * Cache local par ingestion : evite que la meme CVE/dep apparaissant N fois
     * dans le meme rapport (cas standard avec dépendances transitives) ne génère
     * plusieurs persist() avec le meme cveId/sha1 -> UniqueViolation au flush.
     *
     * Reset au debut de chaque appel a ingest().
     *
     * @var array<string, DcCve>
     */
    private array $cveCache = [];

    /** @var array<string, DcDependency> */
    private array $depCache = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DcCveRepository $cveRepository,
        private readonly DcDependencyRepository $depRepository,
        private readonly DcScanRepository $scanRepository,
        private readonly LoggerInterface $logger,
        /* MODIF 2026-05-14 : injection du
         * service de maintenance des flags is_latest_* déclenche après
         * chaque ingestion réussie (cf. fin de ingest()). */
        private readonly DcLatestVersionUpdater $latestVersionUpdater,
    ) {}

    /**
     * [Description for ingest]
     * Ingère le payload d'une row de la queue. Retourne le DcScan crée (ou existant).
     *
     * @param DcProcessingQueue $queue
     * @throws DcPayloadException si le payload est illisible / JSON invalide / structure inattendue
     *         (sous-types concrets : DcEmptyPayloadException, DcGzipDecodeException,
     *         DcInvalidJsonException, DcInvalidStructureException — tous héritant
     *         de \RuntimeException pour rester compatibles avec les catch historiques)
     * @return DcScan
     *
     * Created at: 11/05/2026 00:01:25 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function ingest(DcProcessingQueue $queue): DcScan
    {
        // Reset des caches locaux pour cette ingestion
        $this->cveCache = [];
        $this->depCache = [];

        $jsonString = $this->extractJson($queue);
        $parsed = $this->parseJson($jsonString);

        // Extraction metadata projet
        $projectInfo = $parsed['projectInfo'] ?? [];
        $scanInfo    = $parsed['scanInfo']    ?? [];

        $group    = trim((string) ($projectInfo['groupID']    ?? $queue->getProjectGroup()));
        $artifact = trim((string) ($projectInfo['artifactID'] ?? $queue->getProjectArtifact()));
        $version  = trim((string) ($projectInfo['version']    ?? $queue->getProjectVersion()));
        $scanDate = $this->parseReportDate((string) ($projectInfo['reportDate'] ?? ''));

        // Idempotence
        $existing = $this->scanRepository->findByProjectVersionDate($group, $artifact, $version, $scanDate);
        if ($existing !== null) {
            $this->logger->info('[DC-Ingester] Scan deja existant, skip.', [
                'queue_ulid' => $queue->getUlid(),
                'scan_id'    => $existing->getId(),
            ]);
            return $existing;
        }

        $this->em->beginTransaction();
        try {
            $scan = $this->createScan($queue, $group, $artifact, $version, $scanDate, $parsed, $scanInfo);
            $this->em->persist($scan);
            $this->em->flush(); // pour avoir scan->id

            $stats = ['CRITICAL' => 0, 'HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0, 'INFO' => 0, 'UNKNOWN' => 0];
            $depsTotal = 0;
            $depsVulnerable = 0;

            foreach ($parsed['dependencies'] ?? [] as $depRaw) {
                if (!is_array($depRaw)) { continue; }
                $depsTotal++;

                $vulns = $depRaw['vulnerabilities'] ?? [];
                if (!is_array($vulns) || $vulns === []) {
                    continue; // dependance sans CVE : pas de finding
                }
                $depsVulnerable++;

                $depEntity = $this->findOrCreateDependency($depRaw);

                foreach ($vulns as $vulnRaw) {
                    if (!is_array($vulnRaw)) { continue; }

                    $cveEntity = $this->findOrCreateCve($vulnRaw);

                    $severity = $this->normalizeSeverity((string) ($vulnRaw['severity'] ?? 'UNKNOWN'));
                    $stats[$severity] = ($stats[$severity] ?? 0) + 1;

                    $cvssScore = isset($vulnRaw['cvssv3']['baseScore'])
                        ? (string) $vulnRaw['cvssv3']['baseScore']
                        : null;

                    /* MODIF 2026-05-12 : retrait de
                     * setFixedVersion. La source disponible (versionEndExcluding
                     * du JSON DC) n'est pas la fix recommandée fiable, et
                     * l'heuristique Upgrade vs Mitigation qui en dépendait a
                     * été simplifiée (cf. DcExecutiveAnalyticsService). Le
                     * champ DB dc_finding.fixed_version reste en place pour
                     * réinjecter une source fiable (GHSA/OSV) plus tard. */
                    $finding = (new DcFinding())
                        ->setScan($scan)
                        ->setDependency($depEntity)
                        ->setCve($cveEntity)
                        ->setSeverityAtScan($severity)
                        ->setCvssAtScan($cvssScore);

                    $this->em->persist($finding);
                }
            }

            // Mise a jour des compteurs sur le scan
            $scan->setDepCountTotal($depsTotal)
                    ->setDepCountVulnerable($depsVulnerable)
                    ->setCveCountCritical($stats['CRITICAL'])
                    ->setCveCountHigh($stats['HIGH'])
                    ->setCveCountMedium($stats['MEDIUM'])
                    ->setCveCountLow($stats['LOW'])
                    ->setCveCountInfo($stats['INFO'])
                    ->setCveCountTotal(array_sum($stats));

            $this->em->flush();
            $this->em->commit();

            $this->logger->info('[DC-Ingester] Scan ingere.', [
                'queue_ulid'      => $queue->getUlid(),
                'scan_id'         => $scan->getId(),
                'project'         => sprintf('%s:%s:%s', $group, $artifact, $version),
                'dep_total'       => $depsTotal,
                'dep_vulnerable'  => $depsVulnerable,
                'cve_total'       => $scan->getCveCountTotal(),
                'cve_critical'    => $scan->getCveCountCritical(),
                'cve_high'        => $scan->getCveCountHigh(),
            ]);

            /* MODIF 2026-05-14 : MAJ des flags
             * is_latest_overall / is_latest_release sur le couple (group, artifact)
             * apres ingestion réussie. Place HORS de la transaction principale :
             * un échec ici ne doit pas annuler l'ingestion (le drift sera rattrape
             * par la commande filet `php bin/console app:dc:recompute-latest`).
             * On log en warning pour traçabilité. */
            try {
                $this->latestVersionUpdater->recomputeFor($group, $artifact);
            } catch (\Throwable $e) {
                $this->logger->warning('[DC-Ingester] ⚠️ Échec MAJ flags is_latest_*, drift possible jusqu\'au prochain backfill.', [
                    'queue_ulid' => $queue->getUlid(),
                    'scan_id'    => $scan->getId(),
                    'group'      => $group,
                    'artifact'   => $artifact,
                    'error'      => $e->getMessage(),
                ]);
            }

            return $scan;
        } catch (\Throwable $e) {
            $this->em->rollback();
            $this->logger->error('[DC-Ingester] Echec ingestion, rollback.', [
                'queue_ulid' => $queue->getUlid(),
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * [Description for extractJson]
     *
     * @param DcProcessingQueue $queue
     *
     * @return string
     *
     * Created at: 14/07/2026 20:05:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function extractJson(DcProcessingQueue $queue): string
    {
        $gz = $queue->getPayloadGz();
        if (is_resource($gz)) {
            $gz = stream_get_contents($gz);
        }
        /* MODIF 2026-05-12 : exceptions dédiées au lieu
         * de \RuntimeException brut. Permet au worker / aux tests de discriminer
         * les causes (payload vide vs gzip corrompu vs JSON invalide vs structure
         * inattendue) sans regex sur le message. Tous héritent de DcPayloadException
         * (elle-même \RuntimeException) → catch historiques préservés. */
        if (!is_string($gz) || $gz === '') {
            throw new DcEmptyPayloadException('Payload queue vide ou illisible.');
        }
        $json = @gzdecode($gz);
        if ($json === false) {
            throw new DcGzipDecodeException('Decompression gzip échouée a l\'ingestion.');
        }
        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJson(string $json): array
    {
        try {
            $parsed = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new DcInvalidJsonException('JSON invalide a l\'ingestion : ' . $e->getMessage(), 0, $e);
        }
        if (!is_array($parsed)) {
            throw new DcInvalidStructureException('JSON racine doit être un objet.');
        }
        return $parsed;
    }

    /**
     * [Description for parseReportDate]
     * Parse une date OWASP DC qui peut contenir des nanosecondes.
     * Format observe : "2026-05-08T06:15:17.838775800Z"
     *
     * @param string $raw
     *
     * @return \DateTimeImmutable
     *
     * Created at: 11/05/2026 00:04:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function parseReportDate(string $raw): \DateTimeImmutable
    {
        $raw = trim($raw);
        if ($raw === '') {
            return new \DateTimeImmutable();
        }
        // Retire la partie fractionnaire (>microseconde non geree par PHP)
        $cleaned = preg_replace('/\.\d+/', '', $raw);
        try {
            return new \DateTimeImmutable($cleaned);
        } catch (\Exception) {
            return new \DateTimeImmutable();
        }
    }

    /**
     * [Description for createScan]
     *
     * @param array<string, mixed> $parsed
     * @param array<string, mixed> $scanInfo
     *
     * @return DcScan
     *
     * Created at: 11/05/2026 00:04:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function createScan(
        DcProcessingQueue $queue,
        string $group,
        string $artifact,
        string $version,
        \DateTimeImmutable $scanDate,
        array $parsed,
        array $scanInfo,
    ): DcScan {
        /* MODIF 2026-05-12 : propage les métadonnées
         * d'archetype/parent reçues a l'upload depuis la queue vers le scan
         * (donnee historique pour le dashboard). */
        return (new DcScan())
            ->setQueue($queue)
            ->setMavenKey($group . ':' . $artifact)
            ->setProjectGroup($group)
            ->setProjectArtifact($artifact)
            ->setProjectVersion($version)
            ->setScanDate($scanDate)
            ->setEngineVersion(isset($scanInfo['engineVersion']) ? (string) $scanInfo['engineVersion'] : null)
            ->setReportSchema(isset($parsed['reportSchema']) ? (string) $parsed['reportSchema'] : null)
            ->setParentLabel($queue->getParentLabel())
            ->setParentVersion($queue->getParentVersion())
            ->setArchetypeVersion($queue->getArchetypeVersion());
    }

    /**
     * [Description for findOrCreateDependency]
     *
     * @param array<string, mixed> $depRaw
     *
     * @return DcDependency
     *
     * Created at: 11/05/2026 00:05:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function findOrCreateDependency(array $depRaw): DcDependency
    {
        $sha1 = (string) ($depRaw['sha1'] ?? '');
        if ($sha1 === '' || strlen($sha1) !== 40) {
            // Fallback : si sha1 absent (rare), on génère un identifiant déterministe sur sha256/filename. SHA1 est légitime ici.
            $sha1 = sha1((string) ($depRaw['sha256'] ?? '') . ($depRaw['fileName'] ?? ''));
        }

        // Cache local d'abord (même dep référencée plusieurs fois dans le même rapport)
        if (isset($this->depCache[$sha1])) {
            return $this->depCache[$sha1];
        }

        $existing = $this->depRepository->findBySha1($sha1);
        if ($existing !== null) {
            $existing->setLastSeenAt(new \DateTimeImmutable());
            $this->depCache[$sha1] = $existing;
            return $existing;
        }

        // Coordonnées package (purl)
        $pkg = null;
        if (isset($depRaw['packages']) && is_array($depRaw['packages']) && isset($depRaw['packages'][0]['id'])) {
            $pkg = (string) $depRaw['packages'][0]['id'];
        }
        [$vendor, $product, $version] = $this->parsePurl($pkg);

        $entity = (new DcDependency())
            ->setSha1($sha1)
            ->setMd5(isset($depRaw['md5']) ? (string) $depRaw['md5'] : null)
            ->setSha256(isset($depRaw['sha256']) ? (string) $depRaw['sha256'] : null)
            ->setFileName(substr((string) ($depRaw['fileName'] ?? 'unknown'), 0, 512))
            ->setPkgCoordinates($pkg !== null ? substr($pkg, 0, 512) : null)
            ->setVendor($vendor)
            ->setProduct($product)
            ->setVersion($version)
            ->setLicense(isset($depRaw['license']) ? substr((string) $depRaw['license'], 0, 255) : null)
            ->setDescription(isset($depRaw['description']) ? (string) $depRaw['description'] : null);

        $this->em->persist($entity);
        $this->depCache[$sha1] = $entity;
        return $entity;
    }

    /**
     * [Description for parsePurl]
     * Parse un purl (Package URL) : pkg:maven/com.rabbitmq/amqp-client@5.9.0
     *
     * @param string|null $purl
     *
     * @return array{0: ?string, 1: ?string, 2: ?string} [vendor, product, version]
     *
     * Created at: 11/05/2026 00:06:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function parsePurl(?string $purl): array
    {
        if ($purl === null || !str_starts_with($purl, 'pkg:')) {
            return [null, null, null];
        }
        $body = substr($purl, 4); // retire "pkg:"
        $atPos = strrpos($body, '@');
        if ($atPos === false) {
            $path = $body;
            $version = null;
        } else {
            $path = substr($body, 0, $atPos);
            $version = urldecode(substr($body, $atPos + 1));
        }
        $segments = explode('/', $path);
        // segments[0] = type (maven/npm/...), reste = namespace + name
        array_shift($segments);
        if (count($segments) >= 2) {
            $vendor  = urldecode($segments[0]);
            $product = urldecode(end($segments));
        } else {
            $vendor  = null;
            $product = $segments[0] ?? null;
        }
        return [$vendor !== null ? substr($vendor, 0, 255) : null,
                $product !== null ? substr($product, 0, 255) : null,
                $version !== null ? substr($version, 0, 64) : null];
    }

    /**
     * [Description for findOrCreateCve]
     *
     * @param array<string, mixed> $vulnRaw
     *
     * @return DcCve
     *
     * Created at: 11/05/2026 00:06:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function findOrCreateCve(array $vulnRaw): DcCve
    {
        $cveId = trim((string) ($vulnRaw['name'] ?? ''));
        if ($cveId === '') {
            throw new DcInvalidStructureException('vulnerability sans "name" (cve_id manquant).');
        }

        // Cache local d'abord (meme CVE peut apparaitre dans N deps du meme rapport)
        if (isset($this->cveCache[$cveId])) {
            return $this->cveCache[$cveId];
        }

        $existing = $this->cveRepository->findByCveId($cveId);
        if ($existing !== null) {
            $existing->setLastSeenAt(new \DateTimeImmutable());
            $this->cveCache[$cveId] = $existing;
            return $existing;
        }

        $cvss = $vulnRaw['cvssv3'] ?? [];

        $entity = (new DcCve())
            ->setCveId($cveId)
            ->setSource((string) ($vulnRaw['source'] ?? 'NVD'))
            ->setSeverity($this->normalizeSeverity((string) ($vulnRaw['severity'] ?? 'UNKNOWN')))
            ->setCvssV3Score(isset($cvss['baseScore']) ? (string) $cvss['baseScore'] : null)
            ->setCvssV3Severity(isset($cvss['baseSeverity']) ? (string) $cvss['baseSeverity'] : null)
            ->setCvssV3AttackVector(isset($cvss['attackVector']) ? (string) $cvss['attackVector'] : null)
            ->setCvssV3AttackComplexity(isset($cvss['attackComplexity']) ? (string) $cvss['attackComplexity'] : null)
            ->setCvssV3Vector(isset($cvss['vector']) ? substr((string) $cvss['vector'], 0, 255) : null)
            ->setCwes(array_values(array_filter(
                (array) ($vulnRaw['cwes'] ?? []),
                static fn($v) => is_string($v) && $v !== ''
            )))
            ->setDescription((string) ($vulnRaw['description'] ?? ''))
            ->setCveReferences(array_values(array_filter(
                (array) ($vulnRaw['references'] ?? []),
                static fn($v) => is_array($v)
            )));

        $this->em->persist($entity);
        $this->cveCache[$cveId] = $entity;
        return $entity;
    }

    /**
     * [Description for normalizeSeverity]
     *
     * @param string $raw
     *
     * @return string
     *
     * Created at: 11/05/2026 00:07:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function normalizeSeverity(string $raw): string
    {
        $up = strtoupper(trim($raw));
        return match ($up) {
            'CRITICAL' => DcCve::SEVERITY_CRITICAL,
            'HIGH'     => DcCve::SEVERITY_HIGH,
            'MEDIUM', 'MODERATE' => DcCve::SEVERITY_MEDIUM,
            'LOW'      => DcCve::SEVERITY_LOW,
            'INFO', 'INFORMATIONAL' => DcCve::SEVERITY_INFO,
            default    => DcCve::SEVERITY_UNKNOWN,
        };
    }

}
