<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Repository;

use App\Entity\DcCve;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * MODIF 2026-05-08 : référentiel global des CVE.
 *
 * @extends ServiceEntityRepository<DcCve>
 */
class DcCveRepository extends ServiceEntityRepository
{
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DcCve::class);
    }

    /**
     * [Description for handleDatabaseException]
     * Pattern partage par tous les repositories du projet.
     *
     * @param \Throwable $e
     *
     * @return array{code: int, erreur: string}
     *
     * Created at: 11/05/2026 10:28:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function handleDatabaseException(\Throwable $e): array
    {
        $message = $e->getMessage();

        if ($e instanceof \Doctrine\DBAL\Exception\ConnectionException) {
            $message = self::$noDataBase;
        }

        if ($e instanceof \Doctrine\DBAL\Exception\NotNullConstraintViolationException) {
            $message = $e->getMessage();
        }

        if ($e instanceof \Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
            return ['code' => 23505, 'erreur' => 'Les informations existent déjà.'];
        }

        return ['code' => 500, 'erreur' => $message];
    }

    /**
     * [Description for findByCveId]
     *
     * @param string $cveId
     *
     * @return DcCve|null
     *
     * Created at: 13/07/2026 09:09:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findByCveId(string $cveId): ?DcCve
    {
        return $this->findOneBy(['cveId' => $cveId]);
    }

    /**
     * [Description for countProjectsByCwe]
     * Vue agrégée : combien de projets touches par chaque CWE, avec la liste des projets concernés. Tri par nb de projets desc puis cwe asc.
     *
     * @return array{code: int, liste?: array<int, array{cwe: string, nb_projets: int, projets: array<int, string>}>, erreur?: string}
     *
     * Created at: 08/05/2026 18:27:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countProjectsByCwe(
        ?array $allowedMavenKeys = null,
        /* MODIF 2026-05-14 : restreint
         * l’agrégation aux scans latest selon mode prod/dev. */
        ?array $allowedScanIds = null
    ): array {
        /* MODIF 2026-05-11 : filtre optionnel par
         * maven_keys. null = org-wide, [] = retour vide, [keys] = WHERE IN. */
        if ($allowedMavenKeys !== null && empty($allowedMavenKeys)) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }
        if ($allowedScanIds !== null && empty($allowedScanIds)) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }
        $whereClauses = [];
        if ($allowedMavenKeys !== null) { $whereClauses[] = 's.maven_key IN (:keys)'; }
        if ($allowedScanIds  !== null) { $whereClauses[] = 's.id IN (:scan_ids)'; }
        $whereScope = $whereClauses !== [] ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
        $sql = <<<SQL
            SELECT
                cwe.value::text AS cwe,
                COUNT(DISTINCT s.maven_key) AS nb_projets,
                array_agg(DISTINCT s.project_artifact ORDER BY s.project_artifact) AS projets
            FROM ma_moulinette.dc_finding f
                JOIN ma_moulinette.dc_cve c   ON f.cve_id = c.id
                JOIN ma_moulinette.dc_scan s  ON f.scan_id = s.id
                CROSS JOIN LATERAL jsonb_array_elements_text(c.cwes) AS cwe(value)
            $whereScope
            GROUP BY cwe.value
            ORDER BY nb_projets DESC, cwe ASC
        SQL;

        $params = [];
        $types  = [];
        if ($allowedMavenKeys !== null) {
            $params['keys'] = $allowedMavenKeys;
            $types['keys']  = \Doctrine\DBAL\ArrayParameterType::STRING;
        }
        if ($allowedScanIds !== null) {
            $params['scan_ids'] = $allowedScanIds;
            $types['scan_ids']  = \Doctrine\DBAL\ArrayParameterType::INTEGER;
        }

        try {
            $rows = $this->getEntityManager()->getConnection()->executeQuery($sql, $params, $types)->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        $liste = array_map(static fn(array $r) => [
            'cwe' => $r['cwe'],
            'nb_projets' => (int) $r['nb_projets'],
            'projets' => self::parsePgArray($r['projets']),
        ], $rows);

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for findCriticalCvesAcrossProjects]
     * CVE critiques touchant plusieurs projets.
     *
     *  @return array<int, array<string, mixed>>
     *
     * @return array
     *
     * Created at: 08/05/2026 18:28:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findCriticalCvesAcrossProjects(
        int $minProjects = 2,
        ?array $allowedMavenKeys = null,
        /* MODIF 2026-05-14 : restreint
         * l’agrégation aux scans latest selon mode prod/dev. */
        ?array $allowedScanIds = null
    ): array {
        /* MODIF 2026-05-11 : filtre optionnel par
         * maven_keys. null = org-wide, [] = retour vide, [keys] = AND IN. */
        if ($allowedMavenKeys !== null && empty($allowedMavenKeys)) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }
        if ($allowedScanIds !== null && empty($allowedScanIds)) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }
        $andClauses = [];
        if ($allowedMavenKeys !== null) { $andClauses[] = 'AND s.maven_key IN (:keys)'; }
        if ($allowedScanIds  !== null) { $andClauses[] = 'AND s.id IN (:scan_ids)'; }
        $andScope = implode(' ', $andClauses);
        $sql = <<<SQL
            SELECT
                c.cve_id,
                c.severity,
                c.cvss_v3_score,
                c.description,
                COUNT(DISTINCT s.maven_key) AS nb_projets,
                array_agg(DISTINCT s.project_artifact ORDER BY s.project_artifact) AS projets
            FROM ma_moulinette.dc_finding f
                JOIN ma_moulinette.dc_cve c  ON f.cve_id = c.id
                JOIN ma_moulinette.dc_scan s ON f.scan_id = s.id
            WHERE c.severity IN ('CRITICAL', 'HIGH')
            $andScope
            GROUP BY c.id
            HAVING COUNT(DISTINCT s.maven_key) >= :min
            ORDER BY nb_projets DESC, c.cvss_v3_score DESC NULLS LAST
        SQL;

        $params = ['min' => $minProjects];
        $types  = [];
        if ($allowedMavenKeys !== null) {
            $params['keys'] = $allowedMavenKeys;
            $types['keys']  = \Doctrine\DBAL\ArrayParameterType::STRING;
        }
        if ($allowedScanIds !== null) {
            $params['scan_ids'] = $allowedScanIds;
            $types['scan_ids']  = \Doctrine\DBAL\ArrayParameterType::INTEGER;
        }

        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery($sql, $params, $types)
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        $liste = array_map(static fn(array $r) => [
            'cve_id' => $r['cve_id'],
            'severity' => $r['severity'],
            'cvss_v3_score' => $r['cvss_v3_score'] !== null ? (float) $r['cvss_v3_score'] : null,
            'description' => $r['description'],
            'nb_projets' => (int) $r['nb_projets'],
            'projets' => self::parsePgArray($r['projets']),
        ], $rows);

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * MODIF 2026-05-11 : retourne les
     * CVE de sévérité CRITICAL du périmètre, triées par CVSS v3 desc puis
     * cve_id asc (stabilité). Diff avec findCriticalCvesAcrossProjects :
     *   - aucun filtrage minProjects (toute CVE CRITICAL compte)
     *   - sévérité stricte 'CRITICAL' (pas HIGH)
     *   - LIMIT applique cote SQL
     * Utilisée par le fragment "Mon périmètre" du dashboard.
     *
     * @return array{code: int, liste?: array<int, array<string, mixed>>, erreur?: string}
     */
    public function listTopCriticalCves(
        int $limit = 10,
        ?array $allowedMavenKeys = null,
        /* MODIF 2026-05-14 : restreint
         * l’agrégation aux scans latest selon mode prod/dev. */
        ?array $allowedScanIds = null
    ): array {
        if ($allowedMavenKeys !== null && empty($allowedMavenKeys)) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }
        if ($allowedScanIds !== null && empty($allowedScanIds)) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }
        $limit = max(1, $limit);
        $andClauses = [];
        if ($allowedMavenKeys !== null) { $andClauses[] = 'AND s.maven_key IN (:keys)'; }
        if ($allowedScanIds  !== null) { $andClauses[] = 'AND s.id IN (:scan_ids)'; }
        $andScope = implode(' ', $andClauses);
        $sql = <<<SQL
            SELECT
                c.cve_id,
                c.severity,
                c.cvss_v3_score,
                c.description,
                COUNT(DISTINCT s.maven_key) AS nb_projets,
                array_agg(DISTINCT s.project_artifact ORDER BY s.project_artifact) AS projets
            FROM ma_moulinette.dc_finding f
                JOIN ma_moulinette.dc_cve c  ON f.cve_id = c.id
                JOIN ma_moulinette.dc_scan s ON f.scan_id = s.id
            WHERE c.severity = 'CRITICAL'
            $andScope
            GROUP BY c.id
            ORDER BY c.cvss_v3_score DESC NULLS LAST, c.cve_id ASC
            LIMIT :lim
        SQL;

        $params = ['lim' => $limit];
        $types  = [];
        if ($allowedMavenKeys !== null) {
            $params['keys'] = $allowedMavenKeys;
            $types['keys']  = \Doctrine\DBAL\ArrayParameterType::STRING;
        }
        if ($allowedScanIds !== null) {
            $params['scan_ids'] = $allowedScanIds;
            $types['scan_ids']  = \Doctrine\DBAL\ArrayParameterType::INTEGER;
        }

        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery($sql, $params, $types)
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        $liste = array_map(static fn(array $r) => [
            'cve_id'        => $r['cve_id'],
            'severity'      => $r['severity'],
            'cvss_v3_score' => $r['cvss_v3_score'] !== null ? (float) $r['cvss_v3_score'] : null,
            'description'   => $r['description'],
            'nb_projets'    => (int) $r['nb_projets'],
            'projets'       => self::parsePgArray($r['projets']),
        ], $rows);

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * MODIF 2026-05-12 : findings CRITICAL "vraiment
     * nouveaux" du périmètre = CVE présentes dans le dernier scan d'un projet
     * mais absentes du scan immédiatement antérieur (même group/artifact/version).
     *
     * Remplace l'ancien fenêtrage 7 jours (listRecentCriticalFindings) qui
     * pouvait masquer une dérive (CVE qui traîne sur plusieurs scans
     * consécutifs reste affichée) ou inversement faire disparaître une CVE
     * nouvelle si le scan tombe au-delà de la fenêtre.
     *
     * Stratégie SQL (un seul aller-retour, pas de N+1) :
     *  - CTE `latest_scans` : dernier scan par (group, artifact, version).
     *  - CTE `previous_scan_per_project` : scan immédiatement antérieur
     *    pour chaque latest (NULL si jamais scanné avant = premier scan).
     *  - On garde toutes les findings CRITICAL du latest où la même CVE
     *    n'existe pas dans le previous (NOT EXISTS), ou bien le previous
     *    est NULL (cas premier scan = tout est nouveau).
     *  - On expose un flag `is_first_scan` pour que le template puisse
     *    afficher un badge "1ère analyse" distinct de "vraiment apparu".
     *
     * Retourne une ligne PAR finding (pas dédup par CVE), pour afficher
     * la dépendance concernée. Tri scan_date desc puis cvss desc.
     *
     * @return array{code: int, liste?: array<int, array<string, mixed>>, erreur?: string}
     */
    public function listNewCriticalSinceLastScan(
        int $hardLimit = 50,
        ?array $allowedMavenKeys = null
    ): array {
        if ($allowedMavenKeys !== null && empty($allowedMavenKeys)) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }
        $hardLimit = max(1, $hardLimit);
        $whereScopeCte = $allowedMavenKeys !== null ? 'WHERE s.maven_key IN (:keys)' : '';
        $sql = <<<SQL
            WITH latest_scans AS (
                SELECT DISTINCT ON (s.project_group, s.project_artifact, s.project_version)
                    s.id,
                    s.project_group,
                    s.project_artifact,
                    s.project_version,
                    s.scan_date
                FROM ma_moulinette.dc_scan s
                $whereScopeCte
                ORDER BY s.project_group, s.project_artifact, s.project_version, s.scan_date DESC
            ),
            previous_scan_per_project AS (
                SELECT
                    ls.id AS latest_id,
                    (SELECT s2.id
                        FROM ma_moulinette.dc_scan s2
                        WHERE s2.project_group    = ls.project_group
                            AND s2.project_artifact = ls.project_artifact
                            AND s2.project_version  = ls.project_version
                            AND s2.scan_date        < ls.scan_date
                        ORDER BY s2.scan_date DESC
                        LIMIT 1) AS previous_id
                FROM latest_scans ls
            )
            SELECT
                c.cve_id,
                c.cvss_v3_score,
                s.project_group,
                s.project_artifact,
                s.project_version,
                s.maven_key,
                s.scan_date,
                d.product       AS dep_product,
                d.version       AS dep_version,
                (psp.previous_id IS NULL) AS is_first_scan
            FROM ma_moulinette.dc_finding f
                JOIN ma_moulinette.dc_cve c        ON f.cve_id = c.id
                JOIN ma_moulinette.dc_scan s       ON f.scan_id = s.id
                JOIN ma_moulinette.dc_dependency d ON f.dependency_id = d.id
                JOIN latest_scans ls               ON s.id = ls.id
                LEFT JOIN previous_scan_per_project psp ON psp.latest_id = ls.id
            WHERE c.severity = 'CRITICAL'
                /* MODIF 2026-05-12 : NOT EXISTS
                 * enrichi sur (product, version, cve_id) au lieu de cve_id seul.
                 * Cas couvert : si la même CVE était dans le previous sur une
                 * dep différente (autre version du meme product, ou autre module),
                 * la nouvelle apparition sur la dep courante doit être marquée
                 * "nouvelle". Symétrique du fix du service computeScanDiff. */
                AND (
                    psp.previous_id IS NULL
                    OR NOT EXISTS (
                        SELECT 1
                        FROM ma_moulinette.dc_finding f2
                            JOIN ma_moulinette.dc_dependency d2 ON f2.dependency_id = d2.id
                        WHERE f2.scan_id = psp.previous_id
                          AND f2.cve_id  = f.cve_id
                          AND d2.product = d.product
                          AND d2.version = d.version
                    )
                )
            ORDER BY s.scan_date DESC, c.cvss_v3_score DESC NULLS LAST, c.cve_id ASC
            LIMIT :lim
        SQL;

        $params = ['lim' => $hardLimit];
        $types  = [];
        if ($allowedMavenKeys !== null) {
            $params['keys'] = $allowedMavenKeys;
            $types['keys']  = \Doctrine\DBAL\ArrayParameterType::STRING;
        }

        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery($sql, $params, $types)
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        $liste = array_map(static fn(array $r) => [
            'cve_id'           => $r['cve_id'],
            'cvss_v3_score'    => $r['cvss_v3_score'] !== null ? (float) $r['cvss_v3_score'] : null,
            'project_group'    => $r['project_group'],
            'project_artifact' => $r['project_artifact'],
            'project_version'  => $r['project_version'],
            'maven_key'        => $r['maven_key'],
            'scan_date'        => $r['scan_date'],
            'dep_product'      => $r['dep_product'],
            'dep_version'      => $r['dep_version'],
            'is_first_scan'    => (bool) $r['is_first_scan'],
        ], $rows);

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * MODIF 2026-05-14 Point 2 : variante
     * "agrégée par CVE" de listNewCriticalSinceLastScan. Retourne 1 ligne
     * par cve_id avec un sous-tableau `impacted_apps` listant tous les
     * scans ou cette CVE apparaît comme nouvelle (avec leur socle).
     *
    //  * Objectif : éviter le panneau "10 lignes pour la meme CVE qui touche
     * socle-config 4.1.0, 4.1.1, 4.1.2..." qui saturait l'affichage. Le user
     * voit 1 ligne par CVE distincte + un bouton qui ouvre une modale
     * détaillée avec la liste des apps (et leur socle pour identifier
     * l'origine de la vulnérabilité : socle vs jar embarque).
     *
     * @param int                       $hardLimit         max CVE distinctes retournées
     * @param array<int, string>|null   $allowedMavenKeys  périmètre user
     *
     * @return array{code: int, liste?: array<int, array<string, mixed>>, erreur?: string}
     *
     * Created at: 14/05/2026 14:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listNewCriticalCvesGroupedByCve(
        int $hardLimit = 25,
        ?array $allowedMavenKeys = null
    ): array {
        if ($allowedMavenKeys !== null && empty($allowedMavenKeys)) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }
        $hardLimit = max(1, $hardLimit);
        $whereScopeCte = $allowedMavenKeys !== null ? 'WHERE s.maven_key IN (:keys)' : '';

        // Logique identique a listNewCriticalSinceLastScan (CTE latest_scans
        // + previous_scan_per_project + NOT EXISTS sur (cve_id, product, version))
        // mais on agrège le résultat par cve_id avec json_agg pour les apps impactées.
        $sql = <<<SQL
            WITH latest_scans AS (
                SELECT DISTINCT ON (s.project_group, s.project_artifact, s.project_version)
                    s.id, s.project_group, s.project_artifact, s.project_version, s.scan_date,
                    s.parent_label, s.parent_version
                FROM ma_moulinette.dc_scan s
                $whereScopeCte
                ORDER BY s.project_group, s.project_artifact, s.project_version, s.scan_date DESC
            ),
            previous_scan_per_project AS (
                SELECT
                    ls.id AS latest_id,
                    (SELECT s2.id
                        FROM ma_moulinette.dc_scan s2
                        WHERE s2.project_group    = ls.project_group
                            AND s2.project_artifact = ls.project_artifact
                            AND s2.project_version  = ls.project_version
                            AND s2.scan_date        < ls.scan_date
                        ORDER BY s2.scan_date DESC
                        LIMIT 1) AS previous_id
                FROM latest_scans ls
            ),
            new_findings AS (
                SELECT
                    c.cve_id, c.cvss_v3_score, c.severity, c.description,
                    ls.project_group, ls.project_artifact, ls.project_version,
                    ls.scan_date, ls.parent_label, ls.parent_version,
                    d.product AS dep_product, d.version AS dep_version,
                    (psp.previous_id IS NULL) AS is_first_scan
                FROM ma_moulinette.dc_finding f
                    JOIN ma_moulinette.dc_cve c        ON f.cve_id = c.id
                    JOIN ma_moulinette.dc_dependency d ON f.dependency_id = d.id
                    JOIN latest_scans ls               ON f.scan_id = ls.id
                    LEFT JOIN previous_scan_per_project psp ON psp.latest_id = ls.id
                WHERE c.severity = 'CRITICAL'
                    AND (
                        psp.previous_id IS NULL
                        OR NOT EXISTS (
                            SELECT 1
                            FROM ma_moulinette.dc_finding f2
                                JOIN ma_moulinette.dc_dependency d2 ON f2.dependency_id = d2.id
                            WHERE f2.scan_id = psp.previous_id
                                AND f2.cve_id  = f.cve_id
                                AND d2.product = d.product
                                AND d2.version = d.version
                        )
                )
            )
            SELECT
                cve_id,
                MAX(cvss_v3_score) AS cvss_v3_score,
                MAX(severity)      AS severity,
                MAX(description)   AS description,
                COUNT(DISTINCT (project_group || ':' || project_artifact || ':' || project_version)) AS nb_apps_impactees,
                BOOL_OR(is_first_scan) AS any_first_scan,
                MAX(scan_date)         AS last_seen_date,
                json_agg(
                    json_build_object(
                        'group',          project_group,
                        'artifact',       project_artifact,
                        'version',        project_version,
                        'scan_date',      scan_date,
                        'parent_label',   parent_label,
                        'parent_version', parent_version,
                        'dep_product',    dep_product,
                        'dep_version',    dep_version,
                        'is_first_scan',  is_first_scan
                    )
                    ORDER BY scan_date DESC, project_artifact ASC
                ) AS impacted_apps_json
            FROM new_findings
            GROUP BY cve_id
            ORDER BY nb_apps_impactees DESC, MAX(cvss_v3_score) DESC NULLS LAST, cve_id ASC
            LIMIT :lim
        SQL;

        $params = ['lim' => $hardLimit];
        $types  = [];
        if ($allowedMavenKeys !== null) {
            $params['keys'] = $allowedMavenKeys;
            $types['keys']  = \Doctrine\DBAL\ArrayParameterType::STRING;
        }

        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery($sql, $params, $types)
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        $liste = array_map(static function (array $r): array {
            $apps = [];
            $raw = (string) ($r['impacted_apps_json'] ?? '');
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $apps = $decoded;
                }
            }
            return [
                'cve_id'             => $r['cve_id'],
                'cvss_v3_score'      => $r['cvss_v3_score'] !== null ? (float) $r['cvss_v3_score'] : null,
                'severity'           => $r['severity'],
                'description'        => $r['description'],
                'nb_apps_impactees'  => (int) $r['nb_apps_impactees'],
                'any_first_scan'     => (bool) $r['any_first_scan'],
                'last_seen_date'     => $r['last_seen_date'],
                'impacted_apps'      => $apps,
            ];
        }, $rows);

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for parsePgArray]
     * Parse une representation textuelle PostgreSQL d'array (ex: {"a","b"}) vers PHP.
     *
     * @param string|null $raw
     *
     *@return array<int, string>
     *
     * Created at: 08/05/2026 18:29:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    /**
     * MODIF 2026-05-13 : retourne les CVE présentes dans ≥2 des scans donnés.
     * Sert à la page comparer pour
     * identifier rapidement les CVE communes entre 2-4 apps sélectionnées.
     *
     * @param array<int, int> $scanIds liste d'IDs de scans à comparer
     * @return array{code: int, liste?: array<int, array<string, mixed>>, erreur?: string}
     */
    public function findCommonCvesForScans(array $scanIds): array
    {
        // MODIF 2026-05-15 : array_filter défensif retiré, le @param
        // garantit array<int, int>. Reindex via array_values pour list strict.
        $scanIds = array_values($scanIds);
        if (count($scanIds) < 2) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }

        $sql = <<<SQL
            SELECT
                c.cve_id,
                c.severity,
                c.cvss_v3_score,
                COUNT(DISTINCT f.scan_id) AS nb_scans,
                array_agg(DISTINCT f.scan_id ORDER BY f.scan_id) AS scan_ids
            FROM ma_moulinette.dc_finding f
                JOIN ma_moulinette.dc_cve c ON f.cve_id = c.id
            WHERE f.scan_id IN (:scan_ids)
            GROUP BY c.id, c.cve_id, c.severity, c.cvss_v3_score
            HAVING COUNT(DISTINCT f.scan_id) >= 2
            ORDER BY
                nb_scans DESC,
                CASE c.severity
                    WHEN 'CRITICAL' THEN 1
                    WHEN 'HIGH' THEN 2
                    WHEN 'MEDIUM' THEN 3
                    WHEN 'LOW' THEN 4
                    ELSE 5
                END,
                c.cvss_v3_score DESC NULLS LAST
        SQL;

        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery(
                    $sql,
                    ['scan_ids' => array_map('intval', $scanIds)],
                    ['scan_ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
                )
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        $liste = array_map(static fn(array $r): array => [
            'cve_id'        => $r['cve_id'],
            'severity'      => $r['severity'],
            'cvss_v3_score' => $r['cvss_v3_score'] !== null ? (float) $r['cvss_v3_score'] : null,
            'nb_scans'      => (int) $r['nb_scans'],
            'scan_ids'      => array_map('intval', self::parsePgArray($r['scan_ids'])),
        ], $rows);

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for parsePgArray]
     *
     * @param string|null $raw
     *
     * @return array
     *
     * Created at: 13/07/2026 09:11:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function parsePgArray(?string $raw): array
    {
        if ($raw === null || $raw === '' || $raw === '{}') {
            return [];
        }
        $trimmed = trim($raw, '{}');
        if ($trimmed === '') {
            return [];
        }
        // Format simple : valeurs séparées par , avec éventuelles guillemets
        return array_map(
            static fn(string $v) => trim($v, '"'),
            str_getcsv($trimmed, ',', '"', '\\')
        );
    }
}
