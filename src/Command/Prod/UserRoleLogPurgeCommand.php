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

namespace App\Command\Prod;

use App\Repository\UserRoleLogRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * MODIF 2026-10-18 : purge RGPD du journal des modifications de rôle
 * (user_role_log), jusqu'ici conservé sans limite — écart identifié dans
 * mkDocs/docs/ma-moulinette/conformite/rgpd.md (section 13, action 1).
 *
 * La suppression manuelle ligne à ligne reste disponible depuis
 * /admin/journal-roles (ROLE_INTERNAL) ; cette commande couvre la purge
 * automatique régulière, sur le même modèle que app:zap:purge /
 * app:dependency-check:purge. Durée de rétention par défaut réglable via la
 * variable d'environnement USER_ROLE_LOG_RETENTION_DAYS (config/services.yaml),
 * surchargeable ponctuellement par --days.
 *
 * Usage typique :
 *   - cron quotidien en prod
 *   - manuel a la demande en dev
 *
 * Exemples :
 *   php bin/console app:user-role-log:purge                  -> purge >retention par défaut
 *   php bin/console app:user-role-log:purge --days=180       -> purge >180j
 *   php bin/console app:user-role-log:purge --dry-run        -> simulation
 */
#[AsCommand(
    name: 'app:user-role-log:purge',
    description: 'Purge le journal des modifications de rôle (user_role_log) plus vieux que N jours.',
)]
class UserRoleLogPurgeCommand extends Command
{
    public function __construct(
        private readonly UserRoleLogRepository $repository,
        private readonly int $defaultDays,
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
     * Created at: 18/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_REQUIRED,
                'Nombre de jours de retention (lignes plus vieilles sont purgées).',
                $this->defaultDays
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Affiche le nombre de lignes qui seraient purgées sans rien supprimer.'
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
     * Created at: 18/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days   = max(1, (int) $input->getOption('days'));
        $dryRun = (bool) $input->getOption('dry-run');

        $io->section(sprintf(
            'Purge journal des rôles (user_role_log) : retention %d jour(s)%s',
            $days,
            $dryRun ? ' [DRY-RUN]' : ''
        ));

        try {
            if ($dryRun) {
                $count = $this->repository->countOlderThan($days);
            } else {
                $count = $this->repository->purge($days);
            }
        } catch (\Throwable $e) {
            $io->error('❌ Échec de la purge : ' . $e->getMessage());
            $this->logger->critical('[UserRoleLog-Purge] ❌ Échec.', [
                'error'   => $e->getMessage(),
                'days'    => $days,
                'dry_run' => $dryRun,
            ]);
            return Command::FAILURE;
        }

        if ($dryRun) {
            $io->note(sprintf('%d ligne(s) seraient purgées (dry-run, rien supprimé).', $count));
        } else {
            $io->success(sprintf('ℹ️ %d ligne(s) supprimée(s).', $count));
            $this->logger->info('[UserRoleLog-Purge] ℹ️ Purge effectuée.', [
                'rows_deleted' => $count,
                'days'         => $days,
            ]);
        }

        return Command::SUCCESS;
    }
}
