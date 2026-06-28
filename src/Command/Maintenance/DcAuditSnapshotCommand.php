<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

/* MODIF 2026-05-21 : déplacé dans App\Command\Maintenance. */
namespace App\Command\Maintenance;

use App\Repository\DcScanRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * MODIF 2026-05-12 : commande d'audit qualité.
 *
 * Affiche un snapshot ASCII pur de l'état de la base DC :
 *   - Section A : liste des scans (id, maven_key, version, date, compteurs)
 *   - Section B : invariants compteurs (cveCountTotal == somme des severities)
 *   - Section C : groupage par projet, versions présentes, existence des
 *                 previous_scan_same_version / any_version (lot dc-diff-modes)
 *
 * Sortie volontairement ASCII pur (pas d'emoji, pas de couleur ANSI) pour
 * éviter les problèmes d'encodage observés avec `doctrine:query:sql` sur
 * un terminal Windows PowerShell (sortie binaire qui casse l'affichage).
 *
 * Usage :
 *   php bin/console app:dc:audit-snapshot
 *
 * A retirer en fin de campagne qualité ou a garder comme outil de debug.
 */
#[AsCommand(
    name: 'app:dc:audit-snapshot',
    description: "Snapshot ASCII de l’état des données Dependency-Check (campagne qualité).",
)]
class DcAuditSnapshotCommand extends Command
{
    private const AUCUN = '(aucun)';

    public function __construct(
        private readonly DcScanRepository $scanRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->scanRepository->getEntityManager();
        $conn = $em->getConnection();

        // ---- Section A : liste des scans -----------------------------------
        $output->writeln('');
        $output->writeln('============================================================  ');
        $output->writeln('SECTION A - SCANS EN BASE');
        $output->writeln('============================================================  ');

        $rows = $conn->executeQuery(
            'SELECT id, maven_key, project_group, project_artifact, project_version,
                    scan_date,
                    dep_count_total, dep_count_vulnerable,
                    cve_count_total,
                    cve_count_critical, cve_count_high, cve_count_medium,
                    cve_count_low, cve_count_info,
                    parent_label, parent_version, archetype_version
            FROM ma_moulinette.dc_scan
            ORDER BY project_group, project_artifact, scan_date ASC'
        )->fetchAllAssociative();

        if (count($rows) === 0) {
            $output->writeln('(aucun scan en base)');
            return Command::SUCCESS;
        }

        $output->writeln(sprintf('Total : %d scan(s)', count($rows)));
        $output->writeln('');
        $output->writeln(str_pad('ID', 5) . ' | '
            . str_pad('MAVEN_KEY', 50) . ' | '
            . str_pad('VERSION', 22) . ' | '
            . str_pad('DEPS', 10) . ' | '
            . str_pad('C/H/M/L/I', 22) . ' | '
            . str_pad('TOTAL', 7) . ' | '
            . str_pad('PARENT', 40) . ' | '
            . 'ARCHETYPE');
        $output->writeln(str_repeat('-', 170));

        foreach ($rows as $r) {
            $depsCell = sprintf('%d/%d', (int) $r['dep_count_vulnerable'], (int) $r['dep_count_total']);
            $sevCell  = sprintf(
                '%d/%d/%d/%d/%d',
                (int) $r['cve_count_critical'],
                (int) $r['cve_count_high'],
                (int) $r['cve_count_medium'],
                (int) $r['cve_count_low'],
                (int) $r['cve_count_info'],
            );
            $parentCell = ($r['parent_label'] ?? null) !== null
                ? sprintf('%s@%s', $r['parent_label'], $r['parent_version'] ?? '-')
                : '-';
            $output->writeln(
                str_pad((string) $r['id'], 5) . ' | '
                . str_pad(self::cap($r['maven_key'], 50), 50) . ' | '
                . str_pad(self::cap($r['project_version'], 22), 22) . ' | '
                . str_pad($depsCell, 10) . ' | '
                . str_pad($sevCell, 22) . ' | '
                . str_pad((string) $r['cve_count_total'], 7) . ' | '
                . str_pad(self::cap($parentCell, 40), 40) . ' | '
                . ($r['archetype_version'] ?? '-')
            );
        }

        // ---- Section B : invariants ---------------------------------------
        $output->writeln('');
        $output->writeln('=============================================================');
        $output->writeln('SECTION B - INVARIANT cveCountTotal == C+H+M+L+I');
        $output->writeln('=============================================================');

        $kos = 0;
        foreach ($rows as $r) {
            $expected = (int) $r['cve_count_critical']
                        + (int) $r['cve_count_high']
                        + (int) $r['cve_count_medium']
                        + (int) $r['cve_count_low']
                        + (int) $r['cve_count_info'];
            $actual = (int) $r['cve_count_total'];
            if ($expected !== $actual) {
                $output->writeln(sprintf(
                    '[KO] scan_id=%d %s:%s expected=%d actual=%d (delta=%d)',
                    (int) $r['id'],
                    $r['maven_key'],
                    $r['project_version'],
                    $expected,
                    $actual,
                    $actual - $expected
                ));
                $kos++;
            }
        }
        if ($kos === 0) {
            $output->writeln(sprintf('[OK] %d scan(s) respectent l\'invariant.', count($rows)));
        } else {
            $output->writeln(sprintf('[ALERTE] %d scan(s) en violation sur %d.', $kos, count($rows)));
        }

        // ---- Section C : groupage par projet + chaînage previous ----------
        $output->writeln('');
        $output->writeln('============================================================ ');
        $output->writeln('SECTION C - GROUPAGE PAR PROJET + CHAÎNAGE previous_scan_*');
        $output->writeln('============================================================ ');

        // Regroupement par (group, artifact)
        $byProject = [];
        foreach ($rows as $r) {
            $key = $r['project_group'] . ':' . $r['project_artifact'];
            $byProject[$key][] = $r;
        }

        foreach ($byProject as $key => $scans) {
            $output->writeln('');
            $output->writeln(sprintf('-- %s (%d scan(s)) --', $key, count($scans)));
            // tri par scan_date asc (utile pour visualiser l'ordre d'ingestion)
            usort($scans, static fn(array $a, array $b): int =>
                strcmp((string) $a['scan_date'], (string) $b['scan_date'])
            );
            foreach ($scans as $r) {
                $scanEntity = $this->scanRepository->find((int) $r['id']);
                $prevSame = $scanEntity ? $this->scanRepository->findPreviousScanSameVersion($scanEntity) : null;
                $prevAny  = $scanEntity ? $this->scanRepository->findPreviousScanAnyVersion($scanEntity)  : null;

                $output->writeln(sprintf(
                    '  scan_id=%d  version=%-22s  date=%s',
                    (int) $r['id'],
                    (string) $r['project_version'],
                    substr((string) $r['scan_date'], 0, 19),
                ));
                $output->writeln(sprintf(
                    '    previous_same_version : %s',
                    $prevSame === null
                        ? self::AUCUN
                        : sprintf('scan_id=%d  v=%s  date=%s',
                            (int) $prevSame->getId(),
                            $prevSame->getProjectVersion(),
                            $prevSame->getScanDate()->format('Y-m-d H:i:s'))
                ));
                $output->writeln(sprintf(
                    '    previous_any_version  : %s',
                    $prevAny === null
                        ? self::AUCUN
                        : sprintf('scan_id=%d  v=%s  date=%s',
                            (int) $prevAny->getId(),
                            $prevAny->getProjectVersion(),
                            $prevAny->getScanDate()->format('Y-m-d H:i:s'))
                ));
            }
        }

        // ---- Section D : socles utilisés et orphelins ----------------------
        $output->writeln('');
        $output->writeln('============================================================');
        $output->writeln('SECTION D - SOCLES (parent_label@parent_version)');
        $output->writeln('============================================================');

        $this->renderSectionD($rows, $output);

        $output->writeln('');
        return Command::SUCCESS;
    }

    /**
     * [Description for renderSectionD]
     * MODIF 2026-05-12 : Section D, vue
     * groupement par archetype/parent_pom.
     *
     * D.1 : pour chaque (parent_label, parent_version) reference par >= 1
     *       app, statut "SCAN OK" (le scan archetype est en base) ou
     *       "SCAN MISSING" + liste des apps qui en héritent.
     *
     * D.2 : scans qui sont des archetypes connus (leur project_artifact
     *       apparaît comme parent_label quelque part) mais dont la version
     *       exacte n'est référencée par aucune app -> archetype orphelin.
     *
     * @param array<int, array<string, mixed>> $rows tous les scans avec metadata archetype
     *
     * @return void
     *
     * Created at: 15/05/2026 08:15:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function renderSectionD(array $rows, OutputInterface $output): void
    {
        // Step 1 : collecter les couples (parent_label, parent_version) et l'ensemble
        //          des noms de "BOM/parent" reconnus.
        $references = [];   // "label@version" => [scan rows des apps qui l'utilisent]
        $bomNames   = [];   // set des labels (artifact ou group:artifact) servant d'archetype
        foreach ($rows as $r) {
            $label   = $r['parent_label']   ?? null;
            $version = $r['parent_version'] ?? null;
            if ($label === null || $label === '' || $version === null || $version === '') {
                continue;
            }
            $key = $label . '@' . $version;
            $references[$key][] = $r;
            $bomNames[$label]   = true;
        }

        // Step 2 : pour chaque reference, statut du scan archetype
        if (empty($references)) {
            $output->writeln('Aucun parent_label renseigne sur les apps (toutes en standalone).');
        } else {
            $output->writeln('D.1 - Socles utilises par les apps');
            $output->writeln(str_repeat('-', 60));
            ksort($references);
            foreach ($references as $key => $apps) {
                [$label, $version] = explode('@', $key, 2);
                $archetypeScan = $this->findArchetypeScan($rows, $label, $version);
                $status = $archetypeScan !== null
                    ? sprintf('SCAN OK (scan_id=%d)', (int) $archetypeScan['id'])
                    : 'SCAN MISSING';
                $output->writeln('');
                $output->writeln(sprintf('  %s  [%s]', $key, $status));
                foreach ($apps as $a) {
                    $output->writeln(sprintf(
                        '    -> %s:%s %s',
                        $a['project_group'],
                        $a['project_artifact'],
                        $a['project_version'],
                    ));
                }
            }
        }

        // Step 3 : archetypes scannes mais orphelins
        $output->writeln('');
        $output->writeln('D.2 - Socles scannes sans usage dans le perimetre');
        $output->writeln(str_repeat('-', 60));
        $orphans = [];
        foreach ($rows as $r) {
            $artifactKey      = $r['project_artifact'];
            $groupArtifactKey = $r['project_group'] . ':' . $r['project_artifact'];
            $matchLabel       = null;
            if (isset($bomNames[$artifactKey])) {
                $matchLabel = $artifactKey;
            } elseif (isset($bomNames[$groupArtifactKey])) {
                $matchLabel = $groupArtifactKey;
            }
            if ($matchLabel === null) {
                continue; // pas un BOM-like
            }
            $fullKey = $matchLabel . '@' . $r['project_version'];
            if (!isset($references[$fullKey])) {
                $orphans[] = $r;
            }
        }
        if (empty($orphans)) {
            $output->writeln(self::AUCUN . ' (tous les socles scannés sont références par au moins une app)');
        } else {
            foreach ($orphans as $o) {
                $output->writeln(sprintf(
                    '  scan_id=%d  %s:%s %s',
                    (int) $o['id'],
                    $o['project_group'],
                    $o['project_artifact'],
                    $o['project_version'],
                ));
            }
        }
    }

    /**
     * [Description for findArchetypeScan]
     * Cherche dans $rows le scan qui correspond a (label, version).
     * Match label "group:artifact" -> (project_group, project_artifact),
     * ou label "artifact" seul -> project_artifact.
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<string, mixed>|null
     *
     * Created at: 15/05/2026 08:16:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function findArchetypeScan(array $rows, string $label, string $version): ?array
    {
        $hasGroup = str_contains($label, ':');
        $expectedGroup    = null;
        $expectedArtifact = $label;
        if ($hasGroup) {
            [$expectedGroup, $expectedArtifact] = explode(':', $label, 2);
        }
        foreach ($rows as $r) {
            if ((string) $r['project_version'] !== $version) {
                continue;
            }
            if ((string) $r['project_artifact'] !== $expectedArtifact) {
                continue;
            }
            if ($hasGroup && (string) $r['project_group'] !== $expectedGroup) {
                continue;
            }
            return $r;
        }
        return null;
    }

    /**
     * [Description for cap]
     *
     * @param string|null $s
     * @param int $max
     *
     * @return string
     *
     * Created at: 15/05/2026 08:17:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function cap(?string $s, int $max): string
    {
        $s = (string) $s;
        return strlen($s) <= $max ? $s : substr($s, 0, $max - 1) . '~';
    }
}
