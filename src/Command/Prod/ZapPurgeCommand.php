<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\Command\Prod;

use App\Repository\ZapProcessingQueueRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * MODIF 2026-10-15 : purge des lignes terminées (done|failed) de la
 * queue ZAP (DAST).
 *
 * Pas de purge des status queued/processing :
 *  - queued ancien = anomalie à investiguer (worker en panne ?)
 *  - processing ancien = traité par reclaimStaleProcessing() du worker
 *
 * Usage typique :
 *   - cron quotidien en prod
 *   - manuel a la demande en dev
 *
 * Exemples :
 *   php bin/console app:zap:purge                  -> purge >30j
 *   php bin/console app:zap:purge --days=7         -> purge >7j
 *   php bin/console app:zap:purge --dry-run        -> simulation
 */
#[AsCommand(
    name: 'app:zap:purge',
    description: 'Purge les rapports ZAP (DAST) termines plus vieux que N jours.',
)]
class ZapPurgeCommand extends Command
{
    public function __construct(
        private readonly ZapProcessingQueueRepository $queueRepository,
        /* MODIF 2026-10-15 : log dédié aux commandes de la crontab Docker
         * (cf. config/packages/monolog.yaml, channel "cron"). */
        #[Autowire(service: 'monolog.logger.cron')]
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * [Description for configure]
     *
     * @return void
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_REQUIRED,
                'Nombre de jours de retention (rows plus vieilles sont purgées).',
                30
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Affiche le nombre de rows qui seraient purgées sans rien supprimer.'
            );
    }

    /**
     * [Description for execute]
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days   = max(1, (int) $input->getOption('days'));
        $dryRun = (bool) $input->getOption('dry-run');

        $threshold = (new \DateTimeImmutable())->modify("-{$days} days");

        $io->section(sprintf(
            'Purge ZAP (DAST) : retention %d jour(s) (avant %s)%s',
            $days,
            $threshold->format('Y-m-d H:i:s'),
            $dryRun ? ' [DRY-RUN]' : ''
        ));

        try {
            $count = $this->queueRepository->purgeTerminatedBefore($threshold, $dryRun);
        } catch (\Throwable $e) {
            $io->error('❌ Échec de la purge : ' . $e->getMessage());
            $this->logger->critical('[ZAP-Purge] ❌ Échec.', [
                'error'   => $e->getMessage(),
                'days'    => $days,
                'dry_run' => $dryRun,
            ]);
            return Command::FAILURE;
        }

        if ($dryRun) {
            $io->note(sprintf('%d row(s) seraient purgées (dry-run, rien supprime).', $count));
        } else {
            $io->success(sprintf('ℹ️ %d row(s) terminée(s) supprimée(s).', $count));
            $this->logger->info('[ZAP-Purge] ℹ️ Purge effectuée.', [
                'rows_deleted' => $count,
                'days'         => $days,
                'threshold'    => $threshold->format('c'),
            ]);
        }

        // Statistiques restantes
        $stats = $this->queueRepository->countByStatus();
        $io->definitionList(
            ['Restant queued'     => $stats['queued']],
            ['Restant processing' => $stats['processing']],
            ['Restant done'       => $stats['done']],
            ['Restant failed'     => $stats['failed']],
        );

        return Command::SUCCESS;
    }
}
