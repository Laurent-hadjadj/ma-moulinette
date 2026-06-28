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

use App\Service\DependencyCheck\DcLatestVersionUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * MODIF 2026-05-14 : commande backfill des flags
 * is_latest_overall / is_latest_release sur dc_scan.
 *
 * A executer :
 *   - une fois après l'application de la migration dc_latest_flags.sql
 *     pour initialiser les flags sur l'existant
 *   - en filet si jamais on suspecte un drift (ex: ingestion qui aurait
 *     plante avant l'appel au service de maintenance)
 *
 * Idempotente : peut être relancée autant de fois que voulu, le résultat
 * est stable.
 *
 * Usage :
 *   php bin/console app:dc:recompute-latest
 */
#[AsCommand(
    name: 'app:dc:recompute-latest',
    description: 'Outil one-shot : Recalcule les flags is_latest_overall / is_latest_release pour tous les couples (group, artifact) de dc_scan.',
)]
class DcRecomputeLatestCommand extends Command
{
    public function __construct(
        private readonly DcLatestVersionUpdater $updater,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    /**
     * [Description for execute]
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * Created at: 15/05/2026 08:12:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');
        $output->writeln('============================================================');
        $output->writeln(' [dc-latest-version-filter] backfill is_latest_* flags');
        $output->writeln('============================================================');

        $conn = $this->em->getConnection();

        $totalScansAvant = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM ma_moulinette.dc_scan'
        );
        if ($totalScansAvant === 0) {
            $output->writeln(' (aucun scan en base - rien a faire)');
            return Command::SUCCESS;
        }

        $output->writeln(sprintf(' Scans en base : %d', $totalScansAvant));
        $output->writeln(' Recompute en cours...');
        $output->writeln('');

        $t0 = microtime(true);
        $nbCouples = $this->updater->recomputeAll();
        $dureeMs = (int) round((microtime(true) - $t0) * 1000);

        // Statistiques apres backfill
        $nbLatestOverall = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM ma_moulinette.dc_scan WHERE is_latest_overall = TRUE'
        );
        $nbLatestRelease = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM ma_moulinette.dc_scan WHERE is_latest_release = TRUE'
        );
        $nbCouplesSansRelease = $nbCouples - $nbLatestRelease;

        $output->writeln(' ------------------------------------------------------------');
        $output->writeln(sprintf(' Couples (group, artifact) traites  : %d', $nbCouples));
        $output->writeln(sprintf(' Flag is_latest_overall = TRUE      : %d', $nbLatestOverall));
        $output->writeln(sprintf(' Flag is_latest_release = TRUE      : %d', $nbLatestRelease));
        $output->writeln(sprintf(' Couples sans release ingérée       : %d', $nbCouplesSansRelease));
        $output->writeln(sprintf(' Durée                              : %d ms', $dureeMs));
        $output->writeln(' ------------------------------------------------------------');

        // Vérification invariant : nbLatestOverall doit être = nbCouples
        if ($nbLatestOverall !== $nbCouples) {
            $output->writeln('');
            $output->writeln(sprintf(
                ' [WARN] Incoherence : %d couples traités mais seulement %d ligne(s) marquée(s) latest_overall.',
                $nbCouples,
                $nbLatestOverall
            ));
            $output->writeln(' Cela peut indiquer un scan avec une version non parsable par MavenVersionComparator.');
            $output->writeln(' Verifier avec : SELECT project_group, project_artifact, project_version FROM ma_moulinette.dc_scan');
            $output->writeln('                WHERE (project_group, project_artifact) NOT IN');
            $output->writeln('                  (SELECT project_group, project_artifact FROM ma_moulinette.dc_scan WHERE is_latest_overall = TRUE);');
            return Command::FAILURE;
        }

        $output->writeln('');
        $output->writeln(' OK - backfill terminé sans incoherence.');

        return Command::SUCCESS;
    }
}
