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

use App\Entity\DcScan;
use App\Util\MavenVersionComparator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description DcScanRepository]
 * MODIF 2026-05-08 : 1 scan = 1 rapport DC pour (projet, version, date).
 *
 * @extends ServiceEntityRepository<DcScan>
 */
class DcScanRepository extends ServiceEntityRepository
{
    private static string $noDataBase = 'La connexion à la base de données a échoué.';
    // MODIF 2026-05-26 : extraction des string literals dupliqués (S1192).
    private static string $dqlWhereGroup      = 's.projectGroup = :g';
    private static string $dqlWhereArtifact   = 's.projectArtifact = :a';
    private static string $dqlScanDate        = 's.scanDate';
    private static string $dqlProjectArtifact = 's.projectArtifact';
    private static string $dqlProjectVersion  = 's.projectVersion';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DcScan::class);
    }

    /**
     * @return array{code: int, erreur: string}
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
     * [Description for findByProjectVersionDate]
     *
     * @param string $group
     * @param string $artifact
     * @param string $version
     * @param \DateTimeImmutable $scanDate
     * @param mixed
     *
     * @return DcScan|null
     *
     * Created at: 13/07/2026 09:26:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findByProjectVersionDate(
        string $group,
        string $artifact,
        string $version,
        \DateTimeImmutable $scanDate,
    ): ?DcScan {
        return $this->findOneBy([
            'projectGroup'    => $group,
            'projectArtifact' => $artifact,
            'projectVersion'  => $version,
            'scanDate'        => $scanDate,
        ]);
    }

    /**
     * [Description for findLatestForProject]
     * Dernier scan d'un projet/version (pour la vue projet).
     *
     * @param string $group
     * @param string $artifact
     * @param string $version
     *
     * @return DcScan|null
     *
     * Created at: 08/05/2026 18:40:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findLatestForProject(string $group, string $artifact, string $version): ?DcScan
    {
        return $this->createQueryBuilder('s')
            ->where(self::$dqlWhereGroup)
            ->andWhere(self::$dqlWhereArtifact)
            ->andWhere('s.projectVersion = :v')
            ->setParameter('g', $group)
            ->setParameter('a', $artifact)
            ->setParameter('v', $version)
            ->orderBy(self::$dqlScanDate, 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * [Description for findLatestByMavenKey]
     * MODIF 2026-05-11 : dernier scan tous-versions
     * confondues pour un maven_key. Utilise par OwaspController pour afficher
     * le bouton "Dependency-Check" sur la page OWASP du projet sans dépendre
     * du breadcrumb OWASP Sonar (qui peut être absent si pas d'analyse OWASP
     * récente). On expose les coordonnées (group, artifact, version) du
     * scan DC trouve pour construire l'URL `dc_projet`.
     *
     * @param string $mavenKey format "group:artifact"
     * @return DcScan|null
     *
     * Created at: 11/05/2026 10:14:54 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findLatestByMavenKey(string $mavenKey): ?DcScan
    {
        return $this->createQueryBuilder('s')
            ->where('s.mavenKey = :k')
            ->setParameter('k', $mavenKey)
            ->orderBy(self::$dqlScanDate, 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * MODIF 2026-05-12 : retrouve le scan
     * archetype/parent-POM correspondant a un (parent_label, parent_version)
     * declare par une app. Permet d'offrir un lien cliquable vers la page
     * executive du BOM depuis la page executive d'une app qui en hérite.
     *
     * Logique de match :
     *  - Si parent_label contient ':', on l’interprète comme "groupId:artifactId"
     *    et on cherche un scan correspondant strictement.
     *  - Sinon (label simple type "springboot-socle-config"), on cherche par
     *    project_artifact seul + project_version.
     *
     * Retourne null si parent_label/parent_version sont absents (app standalone)
     * ou si aucun scan correspondant n'est en base (cas SCAN MISSING).
     */
    public function findArchetypeScan(?string $parentLabel, ?string $parentVersion): ?DcScan
    {
        if ($parentLabel === null || $parentLabel === '' || $parentVersion === null || $parentVersion === '') {
            return null;
        }

        if (str_contains($parentLabel, ':')) {
            [$group, $artifact] = explode(':', $parentLabel, 2);
            return $this->findOneBy([
                'projectGroup'    => $group,
                'projectArtifact' => $artifact,
                'projectVersion'  => $parentVersion,
            ]);
        }

        // Label simple : on suppose project_artifact = label
        return $this->findOneBy([
            'projectArtifact' => $parentLabel,
            'projectVersion'  => $parentVersion,
        ]);
    }

    /**
     * MODIF 2026-05-13 : version bulk de findByProjectVersionDate pour la page comparer. Retourne les scans pour
     * une liste de triplets (group, artifact, version), un par triplet.
     *
     * Stratégie : pour chaque triplet, on prend le scan le plus récent. 1 seule
     * requête SQL avec WHERE (group=? AND artifact=? AND version=?) OR (...).
     * Pattern aligné sur findArchetypeScansBatch.
     *
     * MODIF 2026-05-15 : @param assoupli ({-: string}) car la méthode accepte délibérément des entrées partielles (clés manquantes ou
     * null) et filtre en interne — comportement validé par les tests.
     *
     * @param array<int, array{group?: string|null, artifact?: string|null, version?: string|null}> $triplets
     * @return array<string, DcScan> indexé par "group:artifact:version"
     */
    public function findScansForTriplets(array $triplets): array
    {
        if (empty($triplets)) {
            return [];
        }

        $seen = [];
        $valid = [];
        foreach ($triplets as $t) {
            // MODIF 2026-05-15 : `?? ''` réintroduit. Bien que le
            // @param annonce des clés obligatoires, le test
            // testFindArchetypeScansBatchSkipsInvalidPairsWhenAllInvalid envoie
            // explicitement des entrées partielles pour valider le filtrage
            // défensif. PHPStan signalait `nullCoalesce.offset` mais c'est un
            // faux positif au regard du contrat réel.
            $g = (string) ($t['group']    ?? '');
            $a = (string) ($t['artifact'] ?? '');
            $v = (string) ($t['version']  ?? '');
            if ($g === '' || $a === '' || $v === '') {
                continue;
            }
            $key = $g . ':' . $a . ':' . $v;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key]  = true;
            $valid[]     = ['group' => $g, 'artifact' => $a, 'version' => $v, 'key' => $key];
        }
        if (empty($valid)) {
            return [];
        }

        $qb  = $this->createQueryBuilder('s');
        $orX = $qb->expr()->orX();
        foreach ($valid as $i => $t) {
            $orX->add($qb->expr()->andX(
                $qb->expr()->eq('s.projectGroup',    ":g$i"),
                $qb->expr()->eq(self::$dqlProjectArtifact, ":a$i"),
                $qb->expr()->eq(self::$dqlProjectVersion,  ":v$i")
            ));
            $qb->setParameter("g$i", $t['group']);
            $qb->setParameter("a$i", $t['artifact']);
            $qb->setParameter("v$i", $t['version']);
        }
        $scans = $qb->where($orX)
            ->orderBy(self::$dqlScanDate, 'DESC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($scans as $scan) {
            $k = $scan->getProjectGroup() . ':' . $scan->getProjectArtifact() . ':' . $scan->getProjectVersion();
            // Si plusieurs scans (re-scans), on garde le 1er (déjà trié scan_date DESC).
            if (!isset($result[$k])) {
                $result[$k] = $scan;
            }
        }
        return $result;
    }

    /**
     * MODIF 2026-05-13 : retourne tous les
     * scans d'un projet (couple group, artifact), toutes versions confondues,
     * triés par scan_date ASC. Sert à la page d'historique d'une application
     * (line chart évolution CVE + tableau scans avec deltas vs scan précédent).
     *
     * Chaque ligne = 1 scan (re-scans inclus pour mesurer l'évolution NVD sur
     * la même version). Les compteurs viennent directement de dc_scan, pas
     * d'aggrégation lourde.
     *
     * @return array{code: int, liste?: array<int, array<string, mixed>>, erreur?: string}
     */
    public function listHistoryForProject(string $group, string $artifact): array
    {
        $sql = <<<SQL
            SELECT
                s.id,
                s.maven_key,
                s.project_group,
                s.project_artifact,
                s.project_version,
                s.scan_date,
                s.dep_count_total,
                s.dep_count_vulnerable,
                s.cve_count_total,
                s.cve_count_critical,
                s.cve_count_high,
                s.cve_count_medium,
                s.cve_count_low,
                s.cve_count_info,
                s.parent_label,
                s.parent_version,
                s.archetype_version
            FROM ma_moulinette.dc_scan s
            WHERE s.project_group = :g
            AND s.project_artifact = :a
            ORDER BY s.scan_date ASC
        SQL;

        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery($sql, ['g' => $group, 'a' => $artifact])
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        $liste = array_map(static fn(array $r): array => [
            'id'                   => (int) $r['id'],
            'maven_key'            => $r['maven_key'],
            'project_group'        => $r['project_group'],
            'project_artifact'     => $r['project_artifact'],
            'project_version'      => $r['project_version'],
            'scan_date'            => new \DateTimeImmutable($r['scan_date']),
            'dep_count_total'      => (int) $r['dep_count_total'],
            'dep_count_vulnerable' => (int) $r['dep_count_vulnerable'],
            'cve_count_total'      => (int) $r['cve_count_total'],
            'cve_count_critical'   => (int) $r['cve_count_critical'],
            'cve_count_high'       => (int) $r['cve_count_high'],
            'cve_count_medium'     => (int) $r['cve_count_medium'],
            'cve_count_low'        => (int) $r['cve_count_low'],
            'cve_count_info'       => (int) $r['cve_count_info'],
            'parent_label'         => $r['parent_label']      ?? null,
            'parent_version'       => $r['parent_version']    ?? null,
            'archetype_version'    => $r['archetype_version'] ?? null,
        ], $rows);

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * MODIF 2026-05-13 : version bulk de findArchetypeScan pour éliminer le N+1 du dashboard.
     * Le code appelant faisait 1 appel SQL par socle référencé dans la boucle "Synthèse par socle" ; avec un parc à 5 socles ça donnait 5 allers-retours PostgreSQL pour un travail qui se fait en 1 unique requête.
     *
     * Signature : prend une liste de paires (label, version) éventuellement
     * dupliquées, retourne une map indexée par "label@version" -> DcScan.
     * Les paires sans scan correspondant sont absentes de la map (lookup
     * cote appelant retombe sur null, sémantique identique à findArchetypeScan).
     *
     * Stratégie SQL :
     *  - Si $pairs vide -> retour [] immédiat (pas de SQL).
     *  - Sépare les labels simples (project_artifact) des labels groupés
     *    (group:artifact) pour pouvoir builder des WHERE adaptés.
     *  - 1 requête par groupe (au pire 2 SQL total).
     *  - Construit un grand WHERE (... OR ...) qui matche les paires.
     *  - Pour les paires multi-scannées (re-scan de la même version), on garde
     *    arbitrairement la 1ère row rencontrée — sémantique acceptable car
     *    findArchetypeScan ancien était findOneBy() sans ORDER BY.
     *
     * MODIF 2026-05-15 : @param assoupli, idem findScansForTriplets.
     *
     * @param array<int, array{label?: string|null, version?: string|null}> $pairs
     * @return array<string, DcScan> indexé par "label@version"
     */
    public function findArchetypeScansBatch(array $pairs): array
    {
        if (empty($pairs)) {
            return [];
        }

        $simple  = []; // [{artifact, version}, ...]
        $grouped = []; // [{group, artifact, version}, ...]
        $seenKeys = [];
        foreach ($pairs as $p) {
            // MODIF 2026-05-15 : `?? ''` réintroduit, idem
            // findScansForTriplets — filtrage défensif validé par les tests.
            $label   = (string) ($p['label']   ?? '');
            $version = (string) ($p['version'] ?? '');
            if ($label === '' || $version === '') {
                continue;
            }
            $key = $label . '@' . $version;
            // Dedup côté appelant : si plusieurs apps référencent le même socle,
            // on ne le query qu'une fois.
            if (isset($seenKeys[$key])) {
                continue;
            }
            $seenKeys[$key] = true;
            if (str_contains($label, ':')) {
                [$g, $a]    = explode(':', $label, 2);
                $grouped[]  = ['group' => $g, 'artifact' => $a, 'version' => $version];
            } else {
                $simple[]   = ['artifact' => $label, 'version' => $version];
            }
        }

        $result = [];

        if (!empty($simple)) {
            $qb = $this->createQueryBuilder('s');
            $orX = $qb->expr()->orX();
            foreach ($simple as $i => $p) {
                $orX->add($qb->expr()->andX(
                    $qb->expr()->eq(self::$dqlProjectArtifact, ":sa$i"),
                    $qb->expr()->eq(self::$dqlProjectVersion,  ":sv$i")
                ));
                $qb->setParameter("sa$i", $p['artifact']);
                $qb->setParameter("sv$i", $p['version']);
            }
            $scans = $qb->where($orX)->getQuery()->getResult();
            foreach ($scans as $scan) {
                $k = $scan->getProjectArtifact() . '@' . $scan->getProjectVersion();
                if (!isset($result[$k])) {
                    $result[$k] = $scan;
                }
            }
        }

        if (!empty($grouped)) {
            $qb = $this->createQueryBuilder('s');
            $orX = $qb->expr()->orX();
            foreach ($grouped as $i => $p) {
                $orX->add($qb->expr()->andX(
                    $qb->expr()->eq('s.projectGroup',    ":gg$i"),
                    $qb->expr()->eq(self::$dqlProjectArtifact, ":ga$i"),
                    $qb->expr()->eq(self::$dqlProjectVersion,  ":gv$i")
                ));
                $qb->setParameter("gg$i", $p['group']);
                $qb->setParameter("ga$i", $p['artifact']);
                $qb->setParameter("gv$i", $p['version']);
            }
            $scans = $qb->where($orX)->getQuery()->getResult();
            foreach ($scans as $scan) {
                $k = $scan->getProjectGroup() . ':' . $scan->getProjectArtifact() . '@' . $scan->getProjectVersion();
                if (!isset($result[$k])) {
                    $result[$k] = $scan;
                }
            }
        }

        return $result;
    }

    /**
     * MODIF 2026-05-12 : renommée depuis
     * findPreviousScan pour clarifier la sémantique. Retourne le scan
     * immédiatement antérieur au scan courant sur la même version
     * (même group/artifact/version, scan_date < courant). Sert à la diff
     * intra-version "re-scan" : depuis le dernier passage, est-ce que NVD
     * a découvert des CVE supplémentaires ?
     *
     * Comparaison de version en stratégie A (canonicalization lowercase) :
     * `LOWER(s.projectVersion) = LOWER(:v)` → `1.0.0-RELEASE` et
     * `1.0.0-release` sont reconnues comme la même version. RC, SNAPSHOT,
     * RELEASE restent distincts (sémantique correcte).
     *
     * Retour entité nullable : si aucun re-scan antérieur, on renvoie null
     * et l'appelant affiche le message "pas de comparaison disponible".
     *
     * @return DcScan|null
     */
    public function findPreviousScanSameVersion(DcScan $current): ?DcScan
    {
        return $this->createQueryBuilder('s')
            ->where(self::$dqlWhereGroup)
            ->andWhere(self::$dqlWhereArtifact)
            ->andWhere('LOWER(s.projectVersion) = LOWER(:v)')
            ->andWhere('s.scanDate < :d')
            ->setParameter('g', $current->getProjectGroup())
            ->setParameter('a', $current->getProjectArtifact())
            ->setParameter('v', $current->getProjectVersion())
            ->setParameter('d', $current->getScanDate())
            ->orderBy(self::$dqlScanDate, 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * MODIF 2026-05-12 : retourne le scan
     * de la **version sémantiquement précédente** du même projet (même
     * group/artifact, version != courante). Sert à la diff inter-versions
     * "release" : ce passage de version améliore-t-il la posture sécu par
     * rapport à la version précédente ?
     *
     * MODIF 2026-05-12 : tri par numéro de version
     * sémantique (cf. App\Util\MavenVersionComparator) au lieu de scan_date.
     * Détecte en audit qualité : si l'utilisateur ingère v4.2.0 puis v4.1.0,
     * le tri par scan_date faisait pointer le previous_any de v4.2.0 vers
     * v4.0.0 (parce que v4.1.0 n'existait pas en base au moment de l'index).
     * Avec le tri semver, peu importe l'ordre d'ingestion : on prend toujours
     * la plus haute version strictement inférieure a la courante.
     *
     * Stratégie :
     *   1. Fetch tous les scans du projet avec une version différente
     *      (LOWER pour la comparaison case-insensitive sur le qualifier)
     *   2. Dédupliquer par version (1 scan par version unique, le plus
     *      recent par scan_date — utile si re-scans multiples d'une meme
     *      version antérieure)
     *   3. Filtrer cote PHP les versions strictement inférieures a current
     *      via MavenVersionComparator::compare
     *   4. Trier semver DESC et retourner le 1er
     *
     * Limitation résiduelle : versions hors schema Maven (build hash, dates,
     * etc.) tombent en fallback strcmp dans le comparateur, ce qui peut
     * produire un ordre alphabétique trompeur. Acceptable pour un parc
     * Java metier standard.
     *
     * @return DcScan|null
     */
    public function findPreviousScanAnyVersion(DcScan $current): ?DcScan
    {
        $candidates = $this->createQueryBuilder('s')
            ->where(self::$dqlWhereGroup)
            ->andWhere(self::$dqlWhereArtifact)
            ->andWhere('LOWER(s.projectVersion) != LOWER(:v)')
            ->setParameter('g', $current->getProjectGroup())
            ->setParameter('a', $current->getProjectArtifact())
            ->setParameter('v', $current->getProjectVersion())
            ->orderBy(self::$dqlScanDate, 'DESC')
            ->getQuery()
            ->getResult();

        if (empty($candidates)) {
            return null;
        }

        // Dédup par version : on garde le scan le plus recent par version unique
        // (utile si la version antérieure a ete re-scannée plusieurs fois).
        $byVersion = [];
        foreach ($candidates as $scan) {
            $v = $scan->getProjectVersion();
            if (!isset($byVersion[$v])) {
                $byVersion[$v] = $scan;
            }
        }

        $currentVersion = $current->getProjectVersion();
        $previousScans  = [];
        foreach ($byVersion as $v => $scan) {
            if (MavenVersionComparator::compare($v, $currentVersion) < 0) {
                $previousScans[] = $scan;
            }
        }

        if (empty($previousScans)) {
            return null;
        }

        usort(
            $previousScans,
            static fn(DcScan $a, DcScan $b): int =>
            MavenVersionComparator::compare($b->getProjectVersion(), $a->getProjectVersion())
        );

        return $previousScans[0];
    }

    /**
     * MODIF 2026-05-14 : variante de listLatestPerProject qui ne retourne qu'1 ligne par couple (project_group, project_artifact) selon le mode de vue :
     *   - mode DEV  : dernière version ingérée (is_latest_overall = TRUE)
     *   - mode PROD : dernière version release stable (is_latest_release = TRUE)
     *
     * Le 3e paramètre `$permissive` contrôle le comportement en mode PROD
     * pour les couples sans release ingérée :
     *   - true  (dc_index) : fallback sur is_latest_overall. La ligne apparaît
     *                        avec has_release=false (badge "Pas de release ingérée").
     *   - false (dashboard / kpi / mutualisables / comparer) : strict. Les couples
     *                        sans release n'apparaissent simplement pas.
     *
     * En mode DEV : has_release n'est pas significatif (toujours TRUE).
     *
     * @param string                    $mode            ScanViewMode::PROD ou ScanViewMode::DEV
     * @param array<int, string>|null   $allowedMavenKeys
     * @param bool                      $permissive       (cf. doc)
     *
     * @return array{code: int, liste?: array<int, array<string, mixed>>, erreur?: string}
     *
     * Created at: 14/05/2026 10:30:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listLatestPerCoupleForView(string $mode, ?array $allowedMavenKeys = null, bool $permissive = false): array
    {
        if ($allowedMavenKeys !== null && empty($allowedMavenKeys)) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }

        $isProd = $mode === \App\Util\ScanViewMode::PROD;
        $whereScope = $allowedMavenKeys !== null ? 'AND s.maven_key IN (:keys)' : '';

        if ($isProd && $permissive) {
            // PROD permissif (dc_index) : prefere is_latest_release si dispo,
            // sinon fallback is_latest_overall. DISTINCT ON + ORDER BY
            // is_latest_release DESC fait gagner la release.
            $sql = <<<SQL
                SELECT DISTINCT ON (s.project_group, s.project_artifact)
                    s.id, s.maven_key, s.project_group, s.project_artifact, s.project_version,
                    s.scan_date,
                    s.dep_count_total, s.dep_count_vulnerable,
                    s.cve_count_total, s.cve_count_critical, s.cve_count_high,
                    s.cve_count_medium, s.cve_count_low,
                    s.parent_label, s.parent_version, s.archetype_version,
                    s.is_latest_release AS has_release
                FROM ma_moulinette.dc_scan s
                WHERE (s.is_latest_release = TRUE OR s.is_latest_overall = TRUE)
                $whereScope
                ORDER BY s.project_group, s.project_artifact, s.is_latest_release DESC
            SQL;
        } elseif ($isProd) {
            // PROD strict (dashboard / kpi / mutualisables / comparer) :
            // uniquement les couples avec une release ingeree. Les couples
            // dev-only sont volontairement exclus des agregats.
            $sql = <<<SQL
                SELECT
                    s.id, s.maven_key, s.project_group, s.project_artifact, s.project_version,
                    s.scan_date,
                    s.dep_count_total, s.dep_count_vulnerable,
                    s.cve_count_total, s.cve_count_critical, s.cve_count_high,
                    s.cve_count_medium, s.cve_count_low,
                    s.parent_label, s.parent_version, s.archetype_version,
                    TRUE AS has_release
                FROM ma_moulinette.dc_scan s
                WHERE s.is_latest_release = TRUE
                $whereScope
                ORDER BY s.project_group, s.project_artifact
            SQL;
        } else {
            // Mode DEV ("apps en mouvement") : on ne garde que
            // les apps dont la dernière version overall n'est PAS leur
            // dernière release stable. Cela inclut :
            //   - apps avec SNAPSHOT/RC plus recent que leur release       (is_latest_overall=TRUE, is_latest_release=FALSE sur la meme ligne)
            //   - apps sans release ingéré (entièrement dev)              (is_latest_overall=TRUE, is_latest_release=FALSE sur la meme ligne)
            // On exclut les apps stables (release seule = "rien en cours")  (is_latest_overall=TRUE ET is_latest_release=TRUE sur la meme ligne).
            $sql = <<<SQL
                SELECT
                    s.id, s.maven_key, s.project_group, s.project_artifact, s.project_version,
                    s.scan_date,
                    s.dep_count_total, s.dep_count_vulnerable,
                    s.cve_count_total, s.cve_count_critical, s.cve_count_high,
                    s.cve_count_medium, s.cve_count_low,
                    s.parent_label, s.parent_version, s.archetype_version,
                    FALSE AS has_release
                FROM ma_moulinette.dc_scan s
                WHERE s.is_latest_overall = TRUE
                AND s.is_latest_release = FALSE
                $whereScope
                ORDER BY s.project_group, s.project_artifact
            SQL;
        }

        $params = $allowedMavenKeys !== null ? ['keys' => $allowedMavenKeys] : [];
        $types  = $allowedMavenKeys !== null ? ['keys' => \Doctrine\DBAL\ArrayParameterType::STRING] : [];

        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery($sql, $params, $types)
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        $liste = array_map(static fn(array $r) => [
            'id' => (int) $r['id'],
            'maven_key' => $r['maven_key'],
            'project_group' => $r['project_group'],
            'project_artifact' => $r['project_artifact'],
            'project_version' => $r['project_version'],
            'scan_date' => new \DateTimeImmutable($r['scan_date']),
            'dep_count_total' => (int) $r['dep_count_total'],
            'dep_count_vulnerable' => (int) $r['dep_count_vulnerable'],
            'cve_count_total' => (int) $r['cve_count_total'],
            'cve_count_critical' => (int) $r['cve_count_critical'],
            'cve_count_high' => (int) $r['cve_count_high'],
            'cve_count_medium' => (int) $r['cve_count_medium'],
            'cve_count_low' => (int) $r['cve_count_low'],
            'parent_label'      => $r['parent_label']      ?? null,
            'parent_version'    => $r['parent_version']    ?? null,
            'archetype_version' => $r['archetype_version'] ?? null,
            'has_release'       => (bool) $r['has_release'],
        ], $rows);

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for listLatestPerProject]
     * Liste des projets distincts ayant au moins 1 scan, avec leur dernier scan.
     * Pour le dashboard "vue d'ensemble par projet".
     *
     * @param array<int, string>|null $allowedMavenKeys
     *
     * @return array{code: int, liste?: array<int, array{id: int, maven_key: string, project_group: string, project_artifact: string, project_version: string, scan_date: \DateTimeImmutable, dep_count_total: int, dep_count_vulnerable: int, cve_count_total: int, cve_count_critical: int, cve_count_high: int, cve_count_medium: int, cve_count_low: int, parent_label: string|null, parent_version: string|null, archetype_version: string|null}>, erreur?: string}
     *
     * Created at: 08/05/2026 18:40:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listLatestPerProject(?array $allowedMavenKeys = null): array
    {
        /* MODIF 2026-05-11 : filtre optionnel par
         * maven_keys. null = org-wide, [] = retour vide, [keys] = WHERE IN. */
        if ($allowedMavenKeys !== null && empty($allowedMavenKeys)) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }
        $whereScope = $allowedMavenKeys !== null ? 'WHERE s.maven_key IN (:keys)' : '';
        /* MODIF 2026-05-12 : ajout des 3
         * colonnes parent_label / parent_version / archetype_version au
         * SELECT pour les exposer au dashboard (Fragment B cartographie). */
        $sql = <<<SQL
            SELECT DISTINCT ON (s.maven_key, s.project_version)
                s.id,
                s.maven_key,
                s.project_group,
                s.project_artifact,
                s.project_version,
                s.scan_date,
                s.dep_count_total,
                s.dep_count_vulnerable,
                s.cve_count_total,
                s.cve_count_critical,
                s.cve_count_high,
                s.cve_count_medium,
                s.cve_count_low,
                s.parent_label,
                s.parent_version,
                s.archetype_version
            FROM ma_moulinette.dc_scan s
            $whereScope
            ORDER BY s.maven_key, s.project_version, s.scan_date DESC
        SQL;

        $params = $allowedMavenKeys !== null ? ['keys' => $allowedMavenKeys] : [];
        $types  = $allowedMavenKeys !== null ? ['keys' => \Doctrine\DBAL\ArrayParameterType::STRING] : [];

        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery($sql, $params, $types)
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        $liste = array_map(static fn(array $r) => [
            'id' => (int) $r['id'],
            'maven_key' => $r['maven_key'],
            'project_group' => $r['project_group'],
            'project_artifact' => $r['project_artifact'],
            'project_version' => $r['project_version'],
            'scan_date' => new \DateTimeImmutable($r['scan_date']),
            'dep_count_total' => (int) $r['dep_count_total'],
            'dep_count_vulnerable' => (int) $r['dep_count_vulnerable'],
            'cve_count_total' => (int) $r['cve_count_total'],
            'cve_count_critical' => (int) $r['cve_count_critical'],
            'cve_count_high' => (int) $r['cve_count_high'],
            'cve_count_medium' => (int) $r['cve_count_medium'],
            'cve_count_low' => (int) $r['cve_count_low'],
            'parent_label'      => $r['parent_label']      ?? null,
            'parent_version'    => $r['parent_version']    ?? null,
            'archetype_version' => $r['archetype_version'] ?? null,
        ], $rows);

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * MODIF 2026-05-11 : agrège par jour les
     * compteurs de sévérité des scans des N derniers jours. Sert au line
     * chart "evolution temporelle" du dashboard.
     *
     * NB v1 : SUM agrégée sur tous les scans du jour. Si un projet est
     * scanne plusieurs fois le meme jour, ses CVE sont comptées plusieurs
     * fois. Acceptable pour une tendance visuelle, a affiner plus tard
     * via window function si besoin (dernier scan par projet par jour).
     *
     * @param array<int, string>|null $allowedMavenKeys
     *
     * @return array{code: int, liste?: array<int, array{day: string, critical: int, high: int, medium: int, low: int}>, erreur?: string}
     */
    public function aggregateSeverityByDay(int $days = 30, ?array $allowedMavenKeys = null): array
    {
        if ($allowedMavenKeys !== null && empty($allowedMavenKeys)) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }
        $days = max(1, $days);
        $andScope = $allowedMavenKeys !== null ? 'AND s.maven_key IN (:keys)' : '';
        // $days cast int -> safe contre injection malgré l'interpolation littérale.
        $sql = <<<SQL
            SELECT
                to_char(date_trunc('day', s.scan_date), 'YYYY-MM-DD') AS day,
                SUM(s.cve_count_critical) AS critical,
                SUM(s.cve_count_high)     AS high,
                SUM(s.cve_count_medium)   AS medium,
                SUM(s.cve_count_low)      AS low
            FROM ma_moulinette.dc_scan s
            WHERE s.scan_date >= NOW() - INTERVAL '{$days} days'
                $andScope
            GROUP BY date_trunc('day', s.scan_date)
            ORDER BY date_trunc('day', s.scan_date) ASC
        SQL;

        $params = $allowedMavenKeys !== null ? ['keys' => $allowedMavenKeys] : [];
        $types  = $allowedMavenKeys !== null ? ['keys' => \Doctrine\DBAL\ArrayParameterType::STRING] : [];

        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery($sql, $params, $types)
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        $liste = array_map(static fn(array $r) => [
            'day'      => $r['day'],
            'critical' => (int) $r['critical'],
            'high'     => (int) $r['high'],
            'medium'   => (int) $r['medium'],
            'low'      => (int) $r['low'],
        ], $rows);

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }
}
