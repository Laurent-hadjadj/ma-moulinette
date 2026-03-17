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
    SonarAnalysisFetcher,
    SonarMetricsFetcher,
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
        private SonarAnalysisFetcher $analysisFetcher,
        private SonarMetricsFetcher $metricsFetcher,
        private HistoriqueRepository $historiqueRepos,
    ) {
        parent::__construct();

        $this->analysisFetcher = $analysisFetcher;
        $this->metricsFetcher = $metricsFetcher;
        $this->historiqueRepos = $historiqueRepos;
    }

    /**
     * [Description for buildHistoriqueMap]
     *
     * @param string $projectKey
     * @param array $analysis
     * @param array $metrics
     *
     * @return array
     *
     * Created at: 17/03/2026 04:22:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function buildHistoriqueMap(string $project_key, array $analysis, array $measures
    ): array {
        return [
            'maven_key' => $project_key,
            'analyse_key' => $analysis['analysisKey'],
            'version' => $analysis['version'],
            'date_version' => new \DateTime($analysis['date']),
            'nom_projet' => $project_key,

            'version_release' => $analysis['version_release'] ?? 0,
            'version_snapshot' => $analysis['version_snapshot'] ?? 0,
            'version_autre' => $analysis['version_autre'] ?? 0,

            'suppress_warning' => 0,
            'no_sonar' => 0,
            'todo' => 0,

            'logger_info' => 0,
            'logger_warn' => 0,
            'logger_error' => 0,
            'logger_debug' => 0,

            'nombre_ligne' => $measures['lines'] ?? 0,
            'nombre_ligne_code' => $measures['ncloc'] ?? 0,

            'nombre_files' => $measures['files'] ?? 0,
            'nombre_classes' => $measures['classes'] ?? 0,
            'nombre_functions' => $measures['functions'] ?? 0,

            'coverage' => $measures['coverage'] ?? 0,
            'duplicated_lines_density' => $measures['duplicated_lines_density'] ?? 0,
            'sqale_debt_ratio' => $measures['sqale_debt_ratio'] ?? 0,

            'tests' => $measures['tests'] ?? 0,
            'violations' => $measures['violations'] ?? 0,
            'dette' => $measures['sqale_index'] ?? 0,

            'nombre_bug' => $measures['bugs'] ?? 0,
            'nombre_vulnerability' => $measures['vulnerabilities'] ?? 0,
            'nombre_code_smell' => $measures['code_smells'] ?? 0,

            'bug_blocker' => 0,
            'bug_critical' => 0,
            'bug_major' => 0,
            'bug_minor' => 0,
            'bug_info' => 0,

            'vulnerability_blocker' => 0,
            'vulnerability_critical' => 0,
            'vulnerability_major' => 0,
            'vulnerability_minor' => 0,
            'vulnerability_info' => 0,

            'code_smell_blocker' => 0,
            'code_smell_critical' => 0,
            'code_smell_major' => 0,
            'code_smell_minor' => 0,
            'code_smell_info' => 0,

            'frontend' => 0,
            'backend' => 0,
            'autre' => 0,
            'inconnu' => 0,

            'nombre_anomalie_bloquant' => $measures['blocker_violations'] ?? 0,
            'nombre_anomalie_critique' => $measures['critical_violations'] ?? 0,
            'nombre_anomalie_majeur' => $measures['major_violations'] ?? 0,
            'nombre_anomalie_mineur' => $measures['minor_violations'] ?? 0,
            'nombre_anomalie_info' => $measures['info_violations'] ?? 0,

            'note_reliability' => $measures['reliability_rating'] ?? 'F',
            'note_security' => $measures['security_rating'] ?? 'F',
            'note_sqale' => $measures['sqale_rating'] ?? 'F',
            'note_hotspot' => $measures['hotspot_rating'] ?? 'F',

            'menace_potentielle_totale' => 0,
            'menace_potentielle_to_review_high' => 0,
            'menace_potentielle_to_review_medium' => 0,
            'menace_potentielle_to_review_low' => 0,
            'menace_potentielle_reviewed_high' => 0,
            'menace_potentielle_reviewed_medium' => 0,
            'menace_potentielle_reviewed_low' => 0,

            'mode_collecte' => 'rebuild',
            'utilisateur_collecte' => 'cli',

            'date_enregistrement' => new \DateTime()
        ];
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

            $map = $this->buildHistoriqueMap(
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
                    //$inserted +=                 $this->historiqueRepos->insertHistoriqueAjoutProjet($batch,[]);
                    $batch = [];
                }
            }
            dd($batch, $inserted);
            $progressBar->advance();
        }

        if (!$dryRun && !empty($batch)) {
            //$inserted += $this->historiqueRepos->batchInsert($batch,[]);
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
