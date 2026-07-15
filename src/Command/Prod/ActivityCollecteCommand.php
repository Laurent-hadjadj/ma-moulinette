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

/* MODIF 2026-05-24 : remplace le trio Messenger
 * (ActivityMessage / ActivityHandler / ProcessTaskHandler)
 * + ActivityYesterdaySchedule par une Command synchrone.
 * Avantages : 1 seule classe, debug direct sans queue, options CLI,
 * rattrapage auto depuis la dernière date enregistrée en base. */
namespace App\Command\Prod;

use App\Entity\Activity;
use App\Repository\ActivityRepository;
use App\Service\{ActivityReportService, ClientService};
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Collecte les tâches SonarQube CE Activity depuis la dernière date enregistrée.
 *
 * Sans options : détecte la dernière date en base et rattrape en fenêtres
 * de --catch-up-days jours jusqu'à hier (J-1).
 *
 * Avec --from-date et --to-date : traite exactement cette plage.
 *
 * Usage :
 *   php bin/console app:activity:collecte                    → auto, hier
 *   php bin/console app:activity:collecte --from-date=2026-05-01 --to-date=2026-05-23
 *   php bin/console app:activity:collecte --catch-up-days=14 --page-size=500
 *   php bin/console app:activity:collecte --dry-run          → affiche les fenêtres
 */
#[AsCommand(
    name: 'app:activity:collecte',
    description: 'Collecte les tâches SonarQube CE Activity (rattrapage auto multi-fenêtres).',
)]
class ActivityCollecteCommand extends Command
{
    private const DEFAULT_WINDOW_DAYS = 7;
    // Profondeur de backfill par défaut quand la base est vide (≈ 3 ans) : on balaie
    // tout l'historique disponible côté SonarQube, borné par sa rétention.
    private const DEFAULT_INIT_DAYS = 1096;
    private const DATE_TIME = 'Y-m-d H:i:s';
    // Format attendu par l'API SonarQube pour minSubmittedAt/maxExecutedAt :
    // ISO 8601 avec offset de timezone (ex. 2026-07-08T00:00:00+0200), sinon 400.
    private const DATE_TIME_API = 'Y-m-d\TH:i:sO';

    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly ActivityReportService $activityReportService,
        private readonly EntityManagerInterface $em,
        private readonly ClientService $client,
        private readonly LoggerInterface $logger,
        private readonly ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    /**
     * [Description for configure]
     *
     * @return void
     *
     * Created at: 24/05/2026 14:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'from-date', null,
                InputOption::VALUE_REQUIRED,
                'Date de début (Y-m-d). Auto-calculée depuis dernierDate() si absente.'
            )
            ->addOption(
                'to-date', null,
                InputOption::VALUE_REQUIRED,
                'Date de fin (Y-m-d). Défaut = hier (J-1).'
            )
            ->addOption(
                'catch-up-days', null,
                InputOption::VALUE_REQUIRED,
                'Largeur max de chaque fenêtre de rattrapage (jours).',
                self::DEFAULT_WINDOW_DAYS
            )
            ->addOption(
                'init-days', null,
                InputOption::VALUE_REQUIRED,
                'Profondeur du backfill quand la base est vide (jours d\'historique à balayer).',
                self::DEFAULT_INIT_DAYS
            )
            ->addOption(
                'page-size', null,
                InputOption::VALUE_REQUIRED,
                'Taille de page API SonarQube (max 1000).',
                1000
            )
            ->addOption(
                'dry-run', null,
                InputOption::VALUE_NONE,
                'Affiche les fenêtres calculées sans appeler l\'API ni persister.'
            );
    }

    /**
     * [Description for execute]
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * Created at: 24/05/2026 14:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Activity Collecte — tâches SonarQube CE');

        $tz          = new \DateTimeZone('Europe/Paris');
        $pageSize    = max(1, (int) $input->getOption('page-size'));
        $windowDays  = max(1, (int) $input->getOption('catch-up-days'));
        $dryRun      = (bool) $input->getOption('dry-run');

        // ── Calcul des bornes ─────────────────────────────────────────────────
        $fromOpt = $input->getOption('from-date');
        $toOpt   = $input->getOption('to-date');

        if ($fromOpt !== null && $toOpt !== null) {
            $from    = (new \DateTimeImmutable($fromOpt, $tz))->setTime(0, 0, 0);
            $to      = (new \DateTimeImmutable($toOpt, $tz))->setTime(23, 59, 59);
            $windows = [[$from, $to]];
        } else {
            $yesterday = (new \DateTimeImmutable('yesterday', $tz))->setTime(23, 59, 59);
            $last_date  = $this->activityRepository->dernierDate();

            if (!empty($last_date['liste']) && isset($last_date['liste']['date'])) {
                $lastDate = new \DateTimeImmutable($last_date['liste']['date'], $tz);
                $from     = $lastDate->modify('+1 day')->setTime(0, 0, 0);
                $io->text(sprintf('Dernière date connue : %s', $lastDate->format(self::DATE_TIME)));
            } else {
                // Base vide : on part de la date la plus éloignée (backfill) pour tout
                // récupérer, et non de la seule fenêtre de rattrapage. Le découpage en
                // sous-fenêtres de $windowDays jours (ci-dessous) maintient chaque requête
                // sous le plafond des 10 000 résultats de l'API SonarQube.
                $initDays = max(1, (int) $input->getOption('init-days'));
                $from = (new \DateTimeImmutable('yesterday', $tz))
                    ->modify('-' . ($initDays - 1) . ' days')
                    ->setTime(0, 0, 0);
                $io->text(sprintf(
                    'Aucune donnée en base — backfill de l\'historique sur %d jours (fenêtres de %d j).',
                    $initDays,
                    $windowDays
                ));
            }

            $to = $yesterday;

            if ($from > $to) {
                $io->success('Base à jour — aucune collecte nécessaire.');
                return Command::SUCCESS;
            }

            $windows = $this->splitWindows($from, $to, $windowDays, $tz);
        }

        $io->text(sprintf('%d fenêtre(s) à traiter.', count($windows)));

        // ── Traitement des fenêtres ───────────────────────────────────────────
        $totals    = ['tasks' => 0, 'inserted' => 0, 'skipped' => 0, 'errors' => 0];
        $allErrors = [];
        $infos     = [
            'date_start' => new \DateTimeImmutable('now', $tz),
            'task_count' => 0,
            'page'       => 1,
        ];

        foreach ($windows as [$winFrom, $winTo]) {
            // Affichage lisible (heure locale).
            $io->section(sprintf(
                'Fenêtre : %s → %s',
                $winFrom->format(self::DATE_TIME),
                $winTo->format(self::DATE_TIME)
            ));

            if ($dryRun) {
                $io->text('[dry-run] pas d\'appel API.');
                continue;
            }

            // SonarQube exige un datetime ISO 8601 avec offset (sinon 400 « cannot be parsed »).
            $result = $this->collectWindow(
                $winFrom->format(self::DATE_TIME_API),
                $winTo->format(self::DATE_TIME_API),
                $pageSize,
                $io,
                $infos,
                $allErrors
            );
            $totals['tasks']    += $result['tasks'];
            $totals['inserted'] += $result['inserted'];
            $totals['skipped']  += $result['skipped'];
            $totals['errors']   += $result['errors'];
        }

        // ── Rapport batch ─────────────────────────────────────────────────────
        if (!$dryRun) {
            $infos['task_count'] = $totals['tasks'];
            $infos['date_end']   = new \DateTimeImmutable('now', $tz);
            $this->activityReportService->generateReport($infos, $totals['inserted'], $allErrors);
        }

        $io->newLine();
        $io->definitionList(
            ['Tâches lues' => $totals['tasks']],
            ['Insérées'    => $totals['inserted']],
            ['Doublons'    => $totals['skipped']],
            ['Erreurs'     => $totals['errors']],
        );

        $this->logger->info('[Activity] Collecte terminée.', $totals);

        return $totals['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Découpe [from, to] en sous-fenêtres de $windowDays jours.
     *
     * @return array<array{\DateTimeImmutable, \DateTimeImmutable}>
     *
     * Created at: 24/05/2026 14:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function splitWindows(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        int $windowDays,
        \DateTimeZone $tz
    ): array {
        $windows = [];

        // Ancrer le curseur dans le timezone cible
        $cursor = $from->setTimezone($tz);
        $toTz   = $to->setTimezone($tz);

        while ($cursor <= $toTz) {
            // Ajouter N jours calendaires (tient compte du DST grâce au timezone)
            $winEnd = \DateTimeImmutable::createFromFormat(
                self::DATE_TIME,
                $cursor->format(self::DATE_TIME),
                $tz
            )
            ->add(new \DateInterval('P' . $windowDays . 'D'))
            ->modify('-1 second');

            if ($winEnd > $toTz) {
                $winEnd = $toTz;
            }

            $windows[] = [$cursor, $winEnd];
            $cursor    = $winEnd->modify('+1 second');
        }

        return $windows;
    }

    /**
     * [Description for collectWindow]
     * Collecte et persiste les tâches pour une fenêtre [fromDate, toDate].
     *
     * @param array<string, mixed> $infos  (passage par référence pour le rapport)
     * @param array<string>        $errors (passage par référence pour le rapport)
     * @return array{tasks: int, inserted: int, skipped: int, errors: int}
     *
     * Created at: 24/05/2026 14:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function collectWindow(
        string $fromDate,
        string $toDate,
        int $pageSize,
        SymfonyStyle $io,
        array &$infos,
        array &$errors
    ): array {
        $counts      = ['tasks' => 0, 'inserted' => 0, 'skipped' => 0, 'errors' => 0];
        $pageIndex   = 1;
        // SONAR_URL peut se terminer par « / » : on le retire pour éviter un double
        // slash (http://host//api/... renvoie la page HTML de SonarQube, pas du JSON).
        $baseUrl     = rtrim($this->params->get('sonar.url'), '/');
        $urlTemplate = $baseUrl . '/api/ce/activity';

        do {
            $url = sprintf('%s?%s', $urlTemplate, http_build_query([
                'minSubmittedAt' => $fromDate,
                'maxExecutedAt'  => $toDate,
                'p'              => $pageIndex,
                'ps'             => $pageSize,
            ]));

            try {
                $response = $this->client->httpActivity($url);

                // Une réponse non-200 (401/403/…) ne doit PAS être confondue avec « aucune tâche ».
                $code = $response['code'] ?? 0;
                if ($code !== 200) {
                    $detail = (string) ($response['body'] ?? ($response['erreur'] ?? 'erreur inconnue'));
                    // Plafond SonarQube (p × ps > 10 000) : fenêtre trop dense, pas un échec dur.
                    if (str_contains($detail, '10000') || str_contains($detail, '10 000')) {
                        $io->warning(sprintf(
                            'Page %d — plafond SonarQube (10 000 résultats) atteint sur cette fenêtre. '
                            . 'Relancez avec un --catch-up-days plus petit (ex. 1) pour tout récupérer.',
                            $pageIndex
                        ));
                    } else {
                        $io->error(sprintf('Page %d — SonarQube a répondu %d : %s', $pageIndex, $code, $detail));
                    }
                    $errors[]         = sprintf('HTTP %d — %s', $code, $detail);
                    $counts['errors'] += 1;
                    break;
                }

                $tasks = $response['json']['tasks'] ?? [];

                if (empty($tasks)) {
                    $io->text(sprintf('  Page %d — aucune tâche, fin de pagination.', $pageIndex));
                    break;
                }

                $pageCount = count($tasks);
                $io->text(sprintf('  Page %d — %d tâche(s).', $pageIndex, $pageCount));
                $counts['tasks'] += $pageCount;

                foreach ($tasks as $task) {
                    $result = $this->persistTask($task, $errors);
                    if ($result === 'inserted') {
                        $counts['inserted']++;
                    } elseif ($result === 'skipped') {
                        $counts['skipped']++;
                    } else {
                        $counts['errors']++;
                    }
                }

                unset($tasks, $response);
                gc_collect_cycles();

                // Page partielle (< page-size) → c'est la dernière. Demander la page
                // suivante provoquerait un 400 SonarQube (« Can return only … 1001th result asked »).
                if ($pageCount < $pageSize) {
                    break;
                }

                // SonarQube 8 ne pagine pas
                if ($this->params->get('sonar.version') == 8) {
                    $this->logger->info('[Activity] SonarQube 8 détecté — 1 seule page.');
                    break;
                }

                sleep(5);
                $pageIndex++;
                $infos['page'] = $pageIndex;
            } catch (\Throwable $e) {
                $this->logger->error('[Activity] Erreur page ' . $pageIndex, ['error' => $e->getMessage()]);
                $errors[] = $e->getMessage();
                $counts['errors']++;
                break;
            }
        } while (true);

        return $counts;
    }

    /**
     * [Description for persistTask]
     * Persiste une tâche SonarQube. Retourne 'inserted', 'skipped' ou 'error'.
     *
     * @param array<string, mixed> $task
     * @param array<string>        $errors (passage par référence)
     *
     * Created at: 24/05/2026 14:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function persistTask(array $task, array &$errors): string
    {
        try {
            $existing = $this->em->getRepository(Activity::class)
                ->findOneBy(['analyseId' => $task['id']]);

            if ($existing !== null) {
                $this->logger->info('[Activity] Doublon ignoré.', ['taskId' => $task['id']]);
                return 'skipped';
            }

            $activity = new Activity();
            $activity->setAnalyseId($task['id']);
            $activity->setMavenKey($task['componentId']);
            $activity->setProjectName($task['componentName']);
            $activity->setStatus($task['status']);
            // submitterLogin est absent pour les tâches déclenchées par CI/système.
            $activity->setSubmitterLogin($task['submitterLogin'] ?? '');
            $activity->setSubmittedAt(new \DateTimeImmutable($task['submittedAt']));
            $activity->setStartedAt(new \DateTimeImmutable($task['startedAt'] ?? $task['submittedAt']));
            $activity->setExecutedAt(new \DateTimeImmutable($task['executedAt']));

            $executionTime = (new \DateTimeImmutable($task['executedAt']))->getTimestamp()
                - (new \DateTimeImmutable($task['submittedAt']))->getTimestamp();
            $activity->setExecutionTime($executionTime);

            $this->em->persist($activity);
            $this->em->flush();

            $this->logger->info('[Activity] Tâche insérée.', ['taskId' => $task['id']]);
            return 'inserted';
        } catch (\Throwable $e) {
            $taskId = $task['id'] ?? '?';
            $this->logger->error('[Activity] Erreur persistTask.', ['taskId' => $taskId, 'error' => $e->getMessage()]);
            $errors[] = sprintf('[%s] %s', $taskId, $e->getMessage());
            return 'error';
        }
    }
}
