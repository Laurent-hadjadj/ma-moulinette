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

namespace App\Service\Zap;

use App\Entity\{OwaspDast, ZapFinding, ZapProcessingQueue, ZapRule, ZapScan};
use App\Exception\Zap\{ZapEmptyPayloadException,
    ZapGzipDecodeException,
    ZapInvalidJsonException,
    ZapInvalidStructureException,
    ZapPayloadException};
use App\Repository\{ZapRuleRepository, ZapScanRepository};
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * [Description ZapIngester]
 * MODIF 2026-10-13 : ingestion metier d'un rapport ZAP (DAST), sur le
 * gabarit DependencyCheckIngester.
 *
 * Pipeline :
 *   1. décompresse le payload_gz de la queue
 *   2. parse JSON, extrait le site unique (validé par ZapPayloadDecoder a
 *      l'upload, re-vérifié ici par défense en profondeur)
 *   3. crée ZapScan (identification projet = maven_key/project_version de
 *      la queue, PAS du payload — contrairement a Dependency-Check)
 *   4. pour chaque alerte :
 *        - findOrCreate ZapRule (cle pluginid)
 *        - crée ZapFinding (snapshot riskcode/confidence)
 *   5. construit owasp_dast (2017 + 2021) en parcourant les tags
 *      OWASP_<année>_AXX de chaque finding
 *   6. tout dans une transaction
 *
 * Idempotence : si un ZapScan existe deja pour (maven_key, project_version,
 * target_url, generated_at), on retourne l'existant sans rien refaire.
 */
class ZapIngester
{
    private const RISK_LABELS = [0 => 'informational', 1 => 'low', 2 => 'medium', 3 => 'high'];

    /**
     * Cache local par ingestion : evite que la meme règle apparaissant N fois
     * dans le meme rapport ne génère plusieurs persist() avec le meme
     * pluginid -> UniqueViolation au flush.
     *
     * @var array<string, ZapRule>
     */
    private array $ruleCache = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ZapRuleRepository $ruleRepository,
        private readonly ZapScanRepository $scanRepository,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * [Description for ingest]
     * Ingère le payload d'une row de la queue. Retourne le ZapScan crée (ou existant).
     *
     * @param ZapProcessingQueue $queue
     * @throws ZapPayloadException si le payload est illisible / JSON invalide / structure inattendue
     *
     * @return ZapScan
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function ingest(ZapProcessingQueue $queue): ZapScan
    {
        $this->ruleCache = [];

        $jsonString = $this->extractJson($queue);
        $parsed = $this->parseJson($jsonString);

        $site = $this->extractSite($parsed);
        $generatedAt = $this->parseGeneratedAt((string) ($parsed['@generated'] ?? ''));
        $zapVersion = isset($parsed['@version']) ? (string) $parsed['@version'] : null;
        $targetUrl = $queue->getTargetUrl() ?? (isset($site['@name']) ? (string) $site['@name'] : null);

        // Idempotence
        $existing = $this->scanRepository->findByUniqueKey(
            $queue->getMavenKey(),
            $queue->getProjectVersion(),
            $targetUrl,
            $generatedAt
        );
        if ($existing !== null) {
            $this->logger->info('[ZAP-Ingester] Scan deja existant, skip.', [
                'queue_ulid' => $queue->getUlid(),
                'scan_id'    => $existing->getId(),
            ]);
            return $existing;
        }

        $this->em->beginTransaction();
        try {
            $scan = (new ZapScan())
                ->setQueue($queue)
                ->setMavenKey($queue->getMavenKey())
                ->setProjectVersion($queue->getProjectVersion())
                ->setTargetUrl($targetUrl)
                ->setZapVersion($zapVersion)
                ->setGeneratedAt($generatedAt);
            $this->em->persist($scan);
            $this->em->flush(); // pour avoir scan->id

            $riskCounts = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
            $alertCountTotal = 0;

            // Accumulateurs owasp_dast en mémoire : [année => [categorie => ['total' => n, 'risk' => [...]]]]
            $owaspAccumulator = [2017 => [], 2021 => []];

            foreach ($site['alerts'] ?? [] as $alertRaw) {
                if (!is_array($alertRaw)) {
                    continue;
                }
                $alertCountTotal++;

                $ruleEntity = $this->findOrCreateRule($alertRaw);

                $riskcode = $this->toSmallInt($alertRaw['riskcode'] ?? 0);
                $confidence = $this->toSmallInt($alertRaw['confidence'] ?? 0);
                $riskCounts[$riskcode] = ($riskCounts[$riskcode] ?? 0) + 1;

                $instances = $alertRaw['instances'] ?? [];
                $tags = is_array($alertRaw['tags'] ?? null) ? $alertRaw['tags'] : [];

                $finding = (new ZapFinding())
                    ->setScan($scan)
                    ->setRule($ruleEntity)
                    ->setRiskcodeAtScan($riskcode)
                    ->setConfidenceAtScan($confidence)
                    ->setInstanceCount(is_array($instances) ? count($instances) : 0)
                    ->setTags($tags);
                $this->em->persist($finding);

                $this->accumulateOwaspTags($owaspAccumulator, $tags, $riskcode);
            }

            $scan->setAlertCountTotal($alertCountTotal)
                 ->setRiskCountHigh($riskCounts[3])
                 ->setRiskCountMedium($riskCounts[2])
                 ->setRiskCountLow($riskCounts[1])
                 ->setRiskCountInformational($riskCounts[0]);

            $this->persistOwaspDast($scan, $owaspAccumulator);

            $this->em->flush();
            $this->em->commit();

            $this->logger->info('[ZAP-Ingester] Scan ingéré.', [
                'queue_ulid'   => $queue->getUlid(),
                'scan_id'      => $scan->getId(),
                'maven_key'    => $queue->getMavenKey(),
                'alert_total'  => $alertCountTotal,
                'risk_high'    => $riskCounts[3],
                'risk_medium'  => $riskCounts[2],
            ]);

            return $scan;
        } catch (\Throwable $e) {
            $this->em->rollback();
            $this->logger->error('[ZAP-Ingester] Echec ingestion, rollback.', [
                'queue_ulid' => $queue->getUlid(),
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * [Description for extractJson]
     *
     * @param ZapProcessingQueue $queue
     *
     * @return string
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function extractJson(ZapProcessingQueue $queue): string
    {
        $gz = $queue->getPayloadGz();
        if (is_resource($gz)) {
            $gz = stream_get_contents($gz);
        }
        if (!is_string($gz) || $gz === '') {
            throw new ZapEmptyPayloadException('Payload queue vide ou illisible.');
        }
        $json = @gzdecode($gz);
        if ($json === false) {
            throw new ZapGzipDecodeException('Decompression gzip échouée a l\'ingestion.');
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
            throw new ZapInvalidJsonException('JSON invalide a l\'ingestion : ' . $e->getMessage(), 0, $e);
        }
        if (!is_array($parsed)) {
            throw new ZapInvalidStructureException('JSON racine doit être un objet.');
        }
        return $parsed;
    }

    /**
     * [Description for extractSite]
     * Défense en profondeur : ZapPayloadDecoder a déjà validé ce schema a
     * l'upload, mais l'ingestion peut survenir longtemps après (queue), donc
     * on re-vérifie ici plutôt que de faire confiance a un payload déja en base.
     *
     * @param array<string, mixed> $parsed
     *
     * @return array<string, mixed>
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function extractSite(array $parsed): array
    {
        if (!isset($parsed['site']) || !is_array($parsed['site']) || $parsed['site'] === []) {
            throw new ZapInvalidStructureException('Bloc "site" absent, invalide ou vide.');
        }
        if (count($parsed['site']) > 1) {
            throw new ZapInvalidStructureException(
                sprintf('Rapport multi-site (%d sites) non supporté (hors périmètre v1).', count($parsed['site']))
            );
        }
        $site = $parsed['site'][0];
        if (!is_array($site)) {
            throw new ZapInvalidStructureException('"site[0]" doit être un objet.');
        }
        return $site;
    }

    /**
     * [Description for parseGeneratedAt]
     * Parse la date de génération du rapport ZAP.
     * Format observé : "Mon, 13 Oct 2026 12:00:00" (RFC 2822-like, format
     * natif du champ @generated du rapport ZAP).
     *
     * @param string $raw
     *
     * @return \DateTimeImmutable
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function parseGeneratedAt(string $raw): \DateTimeImmutable
    {
        $raw = trim($raw);
        if ($raw === '') {
            return new \DateTimeImmutable();
        }
        try {
            return new \DateTimeImmutable($raw);
        } catch (\Exception) {
            return new \DateTimeImmutable();
        }
    }

    /**
     * [Description for findOrCreateRule]
     *
     * @param array<string, mixed> $alertRaw
     *
     * @return ZapRule
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function findOrCreateRule(array $alertRaw): ZapRule
    {
        $pluginid = trim((string) ($alertRaw['pluginid'] ?? ''));
        if ($pluginid === '') {
            throw new ZapInvalidStructureException('Alerte sans "pluginid".');
        }

        if (isset($this->ruleCache[$pluginid])) {
            return $this->ruleCache[$pluginid];
        }

        $existing = $this->ruleRepository->findByPluginid($pluginid);
        if ($existing !== null) {
            $existing->setLastSeenAt(new \DateTimeImmutable());
            $this->ruleCache[$pluginid] = $existing;
            return $existing;
        }

        $entity = (new ZapRule())
            ->setPluginid($pluginid)
            ->setName((string) ($alertRaw['name'] ?? $alertRaw['alert'] ?? 'Alerte ZAP sans nom'))
            ->setCweid(isset($alertRaw['cweid']) ? (int) $alertRaw['cweid'] : null)
            ->setWascid(isset($alertRaw['wascid']) ? (int) $alertRaw['wascid'] : null)
            ->setSolution((string) ($alertRaw['solution'] ?? ''))
            ->setReference((string) ($alertRaw['reference'] ?? ''));

        $this->em->persist($entity);
        $this->ruleCache[$pluginid] = $entity;
        return $entity;
    }

    /**
     * [Description for toSmallInt]
     * Lecture défensive de riskcode/confidence (0-3 attendu, tout le reste
     * retombe sur 0 plutôt que de lever une exception sur une valeur
     * inattendue d'une future version de ZAP).
     *
     * @param mixed $value
     *
     * @return int
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function toSmallInt(mixed $value): int
    {
        $int = (int) $value;
        return ($int >= 0 && $int <= 3) ? $int : 0;
    }

    /**
     * [Description for accumulateOwaspTags]
     * Extrait les tags OWASP_<année>_AXX d'une alerte et incrémente
     * l'accumulateur en mémoire (pas d'accès BDD ici, juste un tableau PHP —
     * les entités OwaspDast sont créées une seule fois en fin d'ingestion).
     *
     * @param array<int, array<int, array<string, mixed>>> &$owaspAccumulator référence [année => [categorie => ['total' => n, 'risk' => [label => n]]]]
     * @param array<int, array<string, mixed>> $tags
     * @param int $riskcode
     *
     * @return void
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function accumulateOwaspTags(array &$owaspAccumulator, array $tags, int $riskcode): void
    {
        $riskLabel = self::RISK_LABELS[$riskcode] ?? 'informational';

        foreach ($tags as $tagEntry) {
            $tagName = (string) ($tagEntry['tag'] ?? '');
            if (!preg_match('/^OWASP_(2017|2021)_A0?(\d{1,2})$/', $tagName, $matches)) {
                continue;
            }
            $year = (int) $matches[1];
            $category = (int) $matches[2];
            if ($category < 1 || $category > 10) {
                continue;
            }

            $owaspAccumulator[$year][$category]['total'] = ($owaspAccumulator[$year][$category]['total'] ?? 0) + 1;
            $owaspAccumulator[$year][$category]['risk'][$riskLabel] =
                ($owaspAccumulator[$year][$category]['risk'][$riskLabel] ?? 0) + 1;
        }
    }

    /**
     * [Description for persistOwaspDast]
     * Transforme l'accumulateur en mémoire en entités OwaspDast (une par
     * référentiel réellement observé dans les tags du rapport — pas de ligne
     * créée si aucun tag OWASP_<année>_AXX n'a été rencontré).
     *
     * @param ZapScan $scan
     * @param array<int, array<int, array<string, mixed>>> $owaspAccumulator
     *
     * @return void
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function persistOwaspDast(ZapScan $scan, array $owaspAccumulator): void
    {
        foreach ($owaspAccumulator as $year => $categories) {
            if ($categories === []) {
                continue;
            }
            $owaspDast = (new OwaspDast())
                ->setScan($scan)
                ->setReferentialOwasp($year)
                ->setMavenKey($scan->getMavenKey())
                ->setProjectVersion($scan->getProjectVersion());

            foreach ($categories as $category => $data) {
                foreach ((array) ($data['risk'] ?? []) as $riskLabel => $count) {
                    for ($i = 0; $i < $count; $i++) {
                        $owaspDast->incrementCategory((int) $category, (string) $riskLabel);
                    }
                }
            }

            $this->em->persist($owaspDast);
        }
    }
}
