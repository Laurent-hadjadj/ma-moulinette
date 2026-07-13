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

use App\Entity\DcDependency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description DcDependencyRepository]
 * MODIF 2026-05-08  : référentiel des dépendances analyses.
 *
 * @extends ServiceEntityRepository<DcDependency>
 */
class DcDependencyRepository extends ServiceEntityRepository
{
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DcDependency::class);
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
     * [Description for findBySha1]
     *
     * @param string $sha1
     *
     * @return DcDependency|null
     *
     * Created at: 13/07/2026 09:16:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findBySha1(string $sha1): ?DcDependency
    {
        return $this->findOneBy(['sha1' => $sha1]);
    }

    /**
     * [Description for topVulnerableDependencies]
     * Vue agrégée : top des dépendances vulnérables les plus répandues.
     *
     * @param int $limit
     *
     * @return array{code: int, liste?: array<int, array<string, mixed>>, erreur?: string}
     *
     * Created at: 08/05/2026 18:30:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function topVulnerableDependencies(
        int $limit = 20,
        ?array $allowedMavenKeys = null,
        /* MODIF 2026-05-14 : restreint
         * l’agrégation aux scans dont l'id est dans la liste (= versions
         * latest selon mode prod/dev). null = pas de restriction. */
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
        /* MODIF 2026-05-11 : meme fix que pour
         * topMutualisableDependencies. La row dc_dependency a un sha1 unique
         * par finding (cf. DependencyCheckIngester), donc GROUP BY d.id donne
         * systématiquement nb_projets=1. Le bon critère metier de
         * "dependence vulnerable" est "meme produit + meme version".
         * MIN() utilise pour les champs stables (pkg_coordinates / file_name /
         * vendor) car Postgres pre-16 n'a pas ANY_VALUE. */
        $whereClauses = [];
        if ($allowedMavenKeys !== null) { $whereClauses[] = 's.maven_key IN (:keys)'; }
        if ($allowedScanIds  !== null) { $whereClauses[] = 's.id IN (:scan_ids)'; }
        $whereScope = $whereClauses !== [] ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
        $sql = <<<SQL
            SELECT
                MIN(d.pkg_coordinates) AS pkg_coordinates,
                MIN(d.file_name)       AS file_name,
                MIN(d.vendor)          AS vendor,
                d.product,
                d.version,
                COUNT(DISTINCT s.maven_key) AS nb_projets,
                COUNT(DISTINCT f.cve_id) AS nb_cves
            FROM ma_moulinette.dc_finding f
                JOIN ma_moulinette.dc_dependency d ON f.dependency_id = d.id
                JOIN ma_moulinette.dc_scan s       ON f.scan_id = s.id
            $whereScope
            GROUP BY d.product, d.version
            ORDER BY nb_projets DESC, nb_cves DESC
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
            'pkg_coordinates' => $r['pkg_coordinates'],
            'file_name' => $r['file_name'],
            'vendor' => $r['vendor'],
            'product' => $r['product'],
            'version' => $r['version'],
            'nb_projets' => (int) $r['nb_projets'],
            'nb_cves' => (int) $r['nb_cves'],
        ], $rows);

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * MODIF 2026-05-12 : dépendances présentes dans >= minProjects projets distincts du périmètre, avec leur repartition
     * CVE par sévérité. Le flag has_blocking_without_fix a été retiré : il
     * dérivait de fixed_version (= versionEndExcluding du JSON DC) qui n'est
     * pas une fix recommandée fiable. La donnée a été supprimée du système
     * tant qu'on n'a pas branché une vraie source type GHSA/OSV.
     *
     * Pas de tri par "gain mutualisation" cote SQL : il se fait cote PHP après
     * calcul du JH (qui depend de la repartition par sévérité).
     * Le hardLimit (défaut 200) sert de garde-fou contre les parcs très gros.
     *
     * @return array{code: int, liste?: array<int, array<string, mixed>>, erreur?: string}
     */
    public function topMutualisableDependencies(
        int $minProjects = 2,
        int $hardLimit = 200,
        ?array $allowedMavenKeys = null,
        /* MODIF 2026-05-14 : si fourni, restreint
         * l’agrégation aux scans dont l'id est dans la liste (= versions
         * latest sélectionnées selon le mode prod/dev). Si null, comportement
         * historique : agrège sur tous les scans du périmètre maven_keys. */
        ?array $allowedScanIds = null
    ): array {
        if ($allowedMavenKeys !== null && empty($allowedMavenKeys)) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }
        if ($allowedScanIds !== null && empty($allowedScanIds)) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }
        $minProjects = max(2, $minProjects);
        $hardLimit   = max(1, $hardLimit);

        $whereClauses = [];
        if ($allowedMavenKeys !== null) {
            $whereClauses[] = 's.maven_key IN (:keys)';
        }
        if ($allowedScanIds !== null) {
            $whereClauses[] = 's.id IN (:scan_ids)';
        }
        $whereScope = $whereClauses !== [] ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        /* MODIF 2026-05-11 : GROUP BY (product, version) au lieu de d.id. La row dc_dependency
         * est identifiée par sha1 unique, qui diffère d'un scan a l'autre meme
         * pour la meme lib (cf. DependencyCheckIngester). Grouper par d.id
         * conduisait a 0 mutualisation systématique. Le bon critère metier de
         * mutualisation est "meme produit + meme version", pas "meme row PK".
         * pkg_coordinates / file_name / vendor sont stables pour un product+version
         * donne, on peut donc utiliser MIN() pour les récupérer sans les ajouter
         * au GROUP BY (Postgres ANY_VALUE n'existe pas avant 16). */
        /* MODIF 2026-05-12 : nb_archetypes_distincts =
         * nb de FAMILLES d'archetype distinctes (par parent_label, SANS la version).
         *   nb_archetypes_distincts = 1 -> VIA ARCHETYPE (même famille, deja mutualise)
         *   nb_archetypes_distincts >= 2 -> CONVERGENCE (familles différentes ou
         *                                  mix archetype+standalone, vraie opportunité).
         *
         * MODIF 2026-05-12 : CTE known_bom_labels.
         * Détecte : un scan du BOM lui-meme (springboot-config) a parent_label NULL,
         * et était donc traite comme standalone implicite, gonflant artificiellement
         * nb_archetypes_distincts pour les deps présentes dans le BOM ET dans les apps
         * qui en héritent (cas apache-cxf vu en audit). Solution : on identifie les
         * BOM via le set des parent_label connus, et un scan dont l'artifact match un
         * label connu est reconnu comme appartenant a sa propre famille (pas standalone).
         * Standalone VRAIS (tetris, 2048, track-logger) restent isoles. */
        $sql = <<<SQL
            WITH known_bom_labels AS (
                SELECT DISTINCT parent_label AS label
                FROM ma_moulinette.dc_scan
                WHERE parent_label IS NOT NULL
            )
            SELECT
                MIN(d.pkg_coordinates) AS pkg_coordinates,
                MIN(d.file_name)       AS file_name,
                MIN(d.vendor)          AS vendor,
                d.product,
                d.version,
                COUNT(DISTINCT s.maven_key) AS nb_projets,
                COUNT(DISTINCT f.cve_id)    AS nb_cves,
                COUNT(DISTINCT f.cve_id) FILTER (WHERE c.severity = 'CRITICAL') AS nb_critical,
                COUNT(DISTINCT f.cve_id) FILTER (WHERE c.severity = 'HIGH')     AS nb_high,
                COUNT(DISTINCT f.cve_id) FILTER (WHERE c.severity = 'MEDIUM')   AS nb_medium,
                COUNT(DISTINCT f.cve_id) FILTER (WHERE c.severity = 'LOW')      AS nb_low,
                COUNT(DISTINCT
                    CASE
                        WHEN s.parent_label IS NOT NULL
                            THEN s.parent_label
                        WHEN s.project_artifact IN (SELECT label FROM known_bom_labels)
                            THEN s.project_artifact
                        WHEN s.project_group || ':' || s.project_artifact IN (SELECT label FROM known_bom_labels)
                            THEN s.project_group || ':' || s.project_artifact
                        ELSE '__standalone_' || s.maven_key
                    END
                ) AS nb_archetypes_distincts,
                /* MODIF 2026-05-13 : liste des
                 * projets utilisateurs de cette dépendance, agrégée en string
                 * "group##artifact##version;;group##artifact##version;;...".
                 * Parsing PHP cote repository pour renvoyer un tableau structuré.
                 * Séparateurs choisis : ';;' (entre triplets) et '##' (entre
                 * champs) pour éviter de collider avec les caractères Maven
                 * standards (:, ., -). */
                STRING_AGG(
                    DISTINCT s.project_group || '##' || s.project_artifact || '##' || s.project_version,
                    ';;'
                ) AS using_projects_raw
            FROM ma_moulinette.dc_finding f
                JOIN ma_moulinette.dc_dependency d ON f.dependency_id = d.id
                JOIN ma_moulinette.dc_cve c        ON f.cve_id = c.id
                JOIN ma_moulinette.dc_scan s       ON f.scan_id = s.id
            $whereScope
            GROUP BY d.product, d.version
            HAVING COUNT(DISTINCT s.maven_key) >= :min
            ORDER BY nb_projets DESC, nb_cves DESC
            LIMIT :lim
        SQL;

        $params = ['min' => $minProjects, 'lim' => $hardLimit];
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

        $liste = array_map(static function (array $r): array {
            $nbArchetypes = (int) $r['nb_archetypes_distincts'];

            /* MODIF 2026-05-13 : parse la
             * string agrégée "group##artifact##version;;..." en tableau de
             * triplets {group, artifact, version}. Triés artifact ASC pour un
             * affichage stable. Tableau vide si pas de donnée (improbable car
             * HAVING COUNT >= 2 garantit >=1 projet, mais filtre défensif). */
            $usingProjects = [];
            $raw = (string) ($r['using_projects_raw'] ?? '');
            if ($raw !== '') {
                foreach (explode(';;', $raw) as $triplet) {
                    $parts = explode('##', $triplet);
                    if (count($parts) === 3) {
                        $usingProjects[] = [
                            'group'    => $parts[0],
                            'artifact' => $parts[1],
                            'version'  => $parts[2],
                        ];
                    }
                }
                usort($usingProjects, static fn(array $a, array $b): int =>
                    strcmp($a['artifact'], $b['artifact'])
                    ?: strcmp($a['version'], $b['version'])
                );
            }

            return [
                'pkg_coordinates'         => $r['pkg_coordinates'],
                'file_name'               => $r['file_name'],
                'vendor'                  => $r['vendor'],
                'product'                 => $r['product'],
                'version'                 => $r['version'],
                'nb_projets'              => (int) $r['nb_projets'],
                'nb_cves'                 => (int) $r['nb_cves'],
                'nb_critical'             => (int) $r['nb_critical'],
                'nb_high'                 => (int) $r['nb_high'],
                'nb_medium'               => (int) $r['nb_medium'],
                'nb_low'                  => (int) $r['nb_low'],
                'nb_archetypes_distincts' => $nbArchetypes,
                // true si toutes les apps utilisatrices partagent le meme archetype
                // (deja mutualise par heritage). false si convergence réelle.
                'is_via_archetype'        => $nbArchetypes <= 1,
                'using_projects'          => $usingProjects,
            ];
        }, $rows);

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }
}
