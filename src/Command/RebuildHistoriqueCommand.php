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

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use App\Service\CommandRebuildHistorique\{
    SonarAnalysisFetcherService,
    SonarMetricsFetcherService,
    BuildMapHistoryService
};
use App\Repository\HistoriqueRepository;
use App\Exception\SonarApiException;

/**
 * [Description RebuildHistoriqueCommand]
 */
#[AsCommand(
    name: 'app:historique:rebuild',
    description: 'Reconstruit l’historique Sonar'
)]
class RebuildHistoriqueCommand extends Command
{

public function __construct(
        private SonarAnalysisFetcherService $analysisFetcher,
        private SonarMetricsFetcherService $metricsFetcher,
        private BuildMapHistoryService $buildMapHistory,
        private HistoriqueRepository $historiqueRepos,
    ) {
        parent::__construct();

        $this->analysisFetcher = $analysisFetcher;
        $this->metricsFetcher = $metricsFetcher;
        $this->$buildMapHistory = $buildMapHistory;
        $this->historiqueRepos = $historiqueRepos;
    }

    /**
     * [Description for configure]
     *
     * @return void
     *
     * Created at: 17/03/2026 04:23:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function configure(): void
    {
        $this
            ->setName('app:historique:rebuild')
            ->setDescription('Reconstruit l’historique Sonar')
            ->addOption('project', null, InputOption::VALUE_REQUIRED, 'Clé du projet Sonar')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans écriture en base');
    }

    /**
     * [Description for execute]
     * ex. app:historique:rebuild --project=sonarlint-visualstudio --dry-run
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * Created at: 17/03/2026 04:23:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $project_key = $input->getOption('project');
        $dryRun = $input->getOption('dry-run');

        if (!$project_key) {
            $io->error('Option --project obligatoire');
            return Command::FAILURE;
        }

        $io->title('Reconstruction historique Sonar');
        $io->info("Projet : $project_key");

        if ($dryRun) {
            $io->comment('Mode DRY-RUN activé');
        }

        $analyses = $this->analysisFetcher->fetchLatestAnalysesPerVersion($project_key);
        $analyses = $this->analysisFetcher->computeVersionCounters($analyses);
        $total = count($analyses);

        if ($total === 0) {
            $io->warning('Aucune donnée historique');
            return Command::SUCCESS;
        }

        $progressBar = new ProgressBar($output, $total);
        $progressBar->start();

        $batch = [];
        $batchSize = 50;
        $inserted = 0;

        foreach ($analyses as $analysis) {

            try {
                    $metrics = $this->metricsFetcher->fetchMetrics(
                        $project_key,
                        $analysis['analysisKey']
                    );
            } catch (SonarApiException $e) {
                $io->warning($e->getMessage());
                $io->newLine();
                continue;
            } catch (\Throwable $e) {
                $io->error($e->getMessage());
                $io->newLine();
                continue;
            }

            $map = $this->$this->buildMapHistory->buildHistoriqueMap(
                $project_key,
                $analysis,
                $metrics
            );

            if ($dryRun) {

                $progressBar->clear();

                $io->writeln(sprintf(
                    "<info>[%d/%d] Version %s</info>",
                    $progressBar->getProgress(),
                    $progressBar->getMaxSteps(),
                    $map['version'] ?? 'unknown'
                ));

                $io->writeln(sprintf(
                    " <comment>├─ Code</comment> : %s lignes | %s lignes code | %s fichiers | %s classes | %s fonctions",
                    number_format($map['nombre_ligne'] ?? 0, 0, ',', ' '),
                    number_format($map['nombre_ligne_code'] ?? 0, 0, ',', ' '),
                    $map['nombre_files'] ?? 0,
                    $map['nombre_classes'] ?? 0,
                    $map['nombre_functions'] ?? 0
                ));

                $io->writeln(sprintf(
                    " <comment>├─ Qualité</comment> : Coverage %s%% | Duplication %s%% | Dette ratio %s",
                    $map['coverage'] ?? 0,
                    $map['duplicated_lines_density'] ?? 0,
                    $map['sqale_debt_ratio'] ?? 0
                ));

                $io->writeln(sprintf(
                    " <comment>└─ Tests</comment> : %s | Violations %s | Bugs %s | Vuln %s | Smells %s",
                    $map['tests'] ?? 0,
                    $map['violations'] ?? 0,
                    $map['nombre_bug'] ?? 0,
                    $map['nombre_vulnerability'] ?? 0,
                    $map['nombre_code_smell'] ?? 0
                ));

                $io->newLine();
                $progressBar->display();

            } else {
                $batch[] = $map;
                if (count($batch) >= $batchSize) {
                    $inserted +=                 $this->historiqueRepos->insertHistoriqueAjoutProjet($batch,[]);
                    $batch = [];
                }
            }
            dd($batch, $inserted);
            $progressBar->advance();
        }

        if (!$dryRun && !empty($batch)) {
            $inserted += $this->historiqueRepos->batchInsert($batch,[]);
        }

        $progressBar->finish();
        $io->newLine(2);

        if (!$dryRun) {
            $io->success("$inserted lignes insérées");
        }

        $io->success('Batch terminé');

        return Command::SUCCESS;
    }

}
