<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Service;

use App\Repository\UserAgentStatsRepository;
use App\Repository\UtilisateurRepository;
use Psr\Log\LoggerInterface;

/**
 * Service de reporting multi-statistiques User-Agent
 *
 * ⚠️ Tolérant aux erreurs
 */
class UserAgentReportingService
{
    private const TODAY = 'Aujourd’hui';
    private const NOERROR = 'Erreur non disponible';

    public function __construct(
        private readonly UserAgentStatsRepository $userAgentRepos,
        private LoggerInterface $logger,
        private UtilisateurRepository $userRepos
    ) {}

    /**
     * [Description for logger]
     * Helper pour tracer une erreur SQL
     *
     * @param string $message
     * @param string $code
     * @param string $error
     *
     * @return void
     *
     * Created at: 23/12/2025 11:29:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function logger(string $message, string $code, string $error){
        if ($this->logger) {
            $this->logger->warning($message, [
                'code' => $code ?? null,
                'erreur' => $error ?? null
            ]);
        }
    }
    /**
     * [Description for getWeekBounds]
     * Helper pour traiter le semaine du date-picker
     *
     * @param string|null $week
     * @param \DateTimeImmutable $now
     * @param \DateTimeZone $tz
     *
     * @return array
     *
     * Created at: 22/12/2025 16:24:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getWeekBounds(?string $week, \DateTimeImmutable $now, \DateTimeZone $tz): array
    {
        try {
                if (!$week) {
                        $now = new \DateTimeImmutable('now', $tz);
                        $start = $now->modify('monday this week')->setTime(0, 0, 0);
                    } else {
                        [$year, $w] = explode('-W', $week);
                        $start = (new \DateTimeImmutable('now', $tz))
                            ->setISODate((int)$year, (int)$w)
                            ->setTime(0, 0, 0);
                    }

                $end = $start->modify('+6 days')->setTime(23, 59, 59);
        } catch (\Exception) {
            $start = $now->modify('monday this week');
        }

        return [
                'start' => $start,
                'end'   => $end,
                'label' => sprintf(
                    'Semaine du %s au %s',
                    $start->format('d/m/Y'),
                    $end->format('d/m/Y')),
                ];
    }

    /**
     * [Description for getMonthBounds]
     * Helper pour traiter le semaine du date-picker
     *
     * @param string|null $month
     * @param \DateTimeImmutable $now
     * @param \DateTimeZone $tz
     *
     * @return array
     *
     * Created at: 22/12/2025 16:24:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getMonthBounds(?string $month, \DateTimeImmutable $now, \DateTimeZone $tz): array
    {
        try {
                if ($month) {
                    $start = new \DateTimeImmutable($month . '-01', $tz);
                } else {
                    $start = (new \DateTimeImmutable('now', $tz))
                        ->modify('first day of this month');
                }

                $start = $start->setTime(0, 0, 0);
                $end   = $start->modify('last day of this month')->setTime(23, 59, 59);
        } catch (\Exception) {
            $start = $now->modify('first day of this month');
        }

        return [
                'start' => $start,
                'end'   => $end,
                'label' => $start->format('F Y'),
            ];
    }

    /**
     * [Description for getPeriodBounds]
     *
     * @param string $period
     * @param string|null $week
     * @param string|null $month
     *
     * @return array
     *
     * Created at: 22/12/2025 16:23:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getPeriodBounds(string $period, ?string $week = null, ?string $month = null): array
    {
        $tz = new \DateTimeZone('Europe/Paris');
        $now = new \DateTimeImmutable('now', $tz);
        return match ($period) {
            'day' => [
                'start' => $now->setTime(0, 0, 0),
                'end'   => $now->setTime(23, 59, 59),
                'label' => self::TODAY,
            ],
            'week' => $this->getWeekBounds($week, $now, $tz),
            'month' => $this->getMonthBounds($month, $now, $tz),
            default => throw new \InvalidArgumentException('Invalid period'),
        };
    }

    /**
     * [Description for normalizeStats]
     *
     * @param array $rows
     * @param int $top
     *
     * @return array
     *
     * Created at: 17/12/2025 14:14:57 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function normalizeStats(array $rows, int $top = 5): array
    {
        $total = array_sum(array_column($rows, 'total'));
        if ($total === 0) {
            return ['total' => 0, 'items' => []];
        }

        $items = [];
        $others = 0;

        foreach ($rows as $index => $row) {
            if ($index < $top) {
                $row['percent'] = round(($row['total'] / $total) * 100);
                $items[] = $row;
            } else {
                $others += $row['total'];
            }
        }

        if ($others > 0) {
            $items[] = [
                'name' => 'Autres',
                'version' => '',
                'total' => $others,
                'percent' => round(($others / $total) * 100),
            ];
        }

        return [
            'total' => $total,
            'items' => $items,
        ];
    }

    /**
     * [Description for getEventAggregate]
     *
     * @return array
     *
     * Created at: 28/12/2025 10:33:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getUtilisateurActif(){
        /** On récupère le nombre d'utilisateur ayant déjà fait une requête */
        $result = $this->userRepos->countUtilisateurActif();
        if ($result['code'] !== 200) {
            $message = '[Statistique] ⚠️ Échec de la requête countUtilisateurActif().';
            $this->logger($message, $result['code'], $result['erreur'] ?? self::NOERROR);
            return $result;
        }

        $items['nombre_utilisateur_actif'] = $result['total'] ?? 0;

        return [
            'code' => 200,
            'data' => $items,
        ];
    }

    /**
     * [Description for getUtilisateurDisponible]
     *
     * @return [type]
     *
     * Created at: 28/12/2025 11:26:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getUtilisateurDisponible(){
        /** On récupère le nombre d'utilisateur créé dans la table */
        $result = $this->userRepos->countUtilisateurDisponible();
        if ($result['code'] !== 200) {
            $message = '[Statistique] ⚠️ Échec de la requête countUtilisateurDisponible().';
            $this->logger($message, $result['code'], $result['erreur'] ?? self::NOERROR);
            return $result;
        }

        $items['nombre_utilisateur_disponible'] = $result['total'] ?? 0;

        return [
            'code' => 200,
            'data' => $items,
        ];
    }

    /**
     * [Description for getOsStats]
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @param string $label
     *
     * @return array
     *
     * Created at: 17/12/2025 14:16:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getOsStats(\DateTimeInterface $start, \DateTimeInterface $end, string $label): array
    {
        $result = $this->userAgentRepos->selectOsStatsByPeriod($start, $end);
        if ($result['code'] !== 200) {
            $message = '[Statistique] ⚠️ Échec de la requête selectOsStatsByPeriod().';
            $this->logger($message, $result['code'], $result['erreur'] ?? self::NOERROR);
            return $result;
        }

        return [
            'code' => 200,
            'data' => $this->normalizeStats($result['liste'], 5),
            'period_label' => $label,
        ];
    }

    /**
     * [Description for getBrowserStats]
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @param string $label
     *
     * @return array
     *
     * Created at: 17/12/2025 14:44:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getBrowserStats(\DateTimeInterface $start, \DateTimeInterface $end, string $label): array
    {
        $result = $this->userAgentRepos->selectBrowserStatsByPeriod($start, $end);

        if ($result['code'] !== 200) {
            $message = '[Statistique] ⚠️ Échec de la requête selectBrowserStatsByPeriod().';
            $this->logger($message, $result['code'], $result['erreur'] ?? self::NOERROR);
            return $result;
        }

        return [
            'code' => 200,
            'data' => $this->normalizeStats($result['liste'], 5),
            'period_label' => $label,
        ];
    }

    /**
     * [Description for getDeviceStats]
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @param string $label
     *
     * @return array
     *
     * Created at: 17/12/2025 14:45:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDeviceStats(\DateTimeInterface $start, \DateTimeInterface $end, string $label): array
    {
        $result = $this->userAgentRepos->selectDeviceTypeStatsByPeriod($start, $end);

        if ($result['code'] !== 200) {
            $message = '[Statistique] ⚠️ Échec de la requête selectDeviceTypeStatsByPeriod().';
            $this->logger($message, $result['code'], $result['erreur'] ?? self::NOERROR);
            return $result;
        }

        return [
            'code' => 200,
            'data' => $this->normalizeStats($result['liste'], 5),
            'period_label' => $label,
        ];
    }

    /**
     * [Description for generateSessionPagesReport]
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @param string $label
     *
     * @return array
     *
     * Created at: 21/12/2025 12:47:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getSessionPagesReport(\DateTimeInterface $start, \DateTimeInterface $end, string $label):array{
        try {
                $result = $this->userAgentRepos->selectSessionPagesStats($start, $end);

                if ($result['code'] !== 200) {
                    $message = '[Statistique] ⚠️ Échec de la requête selectSessionPagesStats().';
                    $this->logger($message, $result['code'], $result['erreur'] ?? self::NOERROR);
                    return $result;
                }

                $uniqueUsers = (int) $result['kpi']['unique_users'];
                $pageViews   = (int) $result['kpi']['page_views'];
                $items       = $result['items'];

                $pages = [];
                foreach ($items as $row) {
                    $total = (int) $row['total'];
                    $pages[] = [
                        'label' => $row['label'],
                        'url' => $row['url'],
                        'total' => $total,
                        'percent' => $pageViews > 0
                            ? round($total * 100 / $pageViews, 1)
                            : 0
                    ];
                }

                return [
                    'code' => 200,
                    'kpi' => [
                        'unique_users' => $uniqueUsers,
                        'page_views' => $pageViews,
                        'pages_per_session' => $uniqueUsers > 0
                            ? round($pageViews / $uniqueUsers, 1)
                            : 0,
                    ],
                    'pages' => [
                        'total' => $pageViews,
                        'items' => $pages,
                    ],
                    'period_label' => $label,
                ];
            } catch (\Throwable $e) {
            return [
                'code' => 500,
                'erreur' => $e->getMessage(),
            ];
        }
    }

    /**
     * [Description for getAvgSessionDurationReport]
     *
     * @return array
     *
     * Created at: 22/12/2025 08:05:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getAvgSessionDurationReport():array{
        try {
                $result = $this->userAgentRepos->selectAvgSessionDurationStats();

                if ($result['code'] !== 200) {
                    $message = '[Statistique] ⚠️ Échec de la requête selectAvgSessionDurationStats().';
                    $this->logger($message, $result['code'], $result['erreur'] ?? self::NOERROR);
                    return $result;
                }

                // Formatage pour le JS
                $rows = $result['rows'];
                if (empty($rows)) { return [ 'code' => 200, 'data' => [] ]; }
                foreach ($rows as $row) {
                    $data[] = [
                        'date' => $row['session_date'],
                        'avg'  => (float) $row['avg_duration_minutes'],
                    ];
                }

                return [ 'code' => 200, 'data' => $data ];
            } catch (\Throwable $e) {
            return [
                'code' => 500,
                'erreur' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * [Description for getUniqueSessionReport]
     *
     * @return array
     *
     * Created at: 22/12/2025 08:07:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getUniqueSessionReport():array{
        try {
                $result = $this->userAgentRepos->selectUniqueSessionStats();

                if ($result['code'] !== 200) {
                    $message = '[Statistique] ⚠️ Échec de la requête selectUniqueSessionStats().';
                    $this->logger($message, $result['code'], $result['erreur'] ?? self::NOERROR);
                    return $result;
                }

                // Formatage pour le JS
                $rows = $result['rows'];
                if (empty($rows)) { return [ 'code' => 200, 'data' => [] ];}

                foreach ($rows as $row) {
                    $data[] = [
                        'date' => $row['session_date'],
                        'session'  => (float) $row['nb_sessions'],
                    ];
                }

                return [ 'code' => 200, 'data' => $data ];
            } catch (\Throwable $e) {
            return [
                'code' => 500,
                'erreur' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * [Description for getUniqueSessionReport]
     *
     * @return array
     *
     * Created at: 22/12/2025 08:07:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getUniqueSessionByCategoryReport():array{

        try {
                $result = $this->userAgentRepos->selectSessionDurationByCategoryStats();

                if ($result['code'] !== 200) {
                    $message = '[Statistique] ⚠️ Échec de la requête selectSessionDurationByCategoryStats().';
                    $this->logger($message, $result['code'], $result['erreur'] ?? self::NOERROR);
                    return $result;
                }

                $totalSessions = count($result['rows']);

                $items = array_map(function ($row) use ($totalSessions) {
                    return [
                        'first' => new \DateTime($row['session_start']),
                        'last'  => new \DateTime($row['session_end']),
                        'minute' => (float) $row['duration_minutes'],
                        'hour'   => (float) $row['duration_hours'],
                        'category' => $row['session_length_category'],
                        'percent' => $totalSessions > 0
                            ? round(100 / $totalSessions, 1)
                            : 0,
                    ];
                }, $result['rows']);

                return [
                    'code' => 200,
                    'total' => $totalSessions,
                    'items' => $items,
                    'period_label' => self::TODAY,
                ];
            } catch (\Throwable $e) {
            return [
                'code' => 500,
                'erreur' => $e->getMessage(),
            ];
        }
    }

    /**
     * [Description for getCategoryByUniqueSessionReport]
     *
     * @return array
     *
     * Created at: 22/12/2025 11:32:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCategoryByUniqueSessionReport():array{
        try {
                $result = $this->userAgentRepos->selectCategoryByUniqueSessionStats();

                if ($result['code'] !== 200) {
                    $message = '[Statistique] ⚠️ Échec de la requête selectCategoryByUniqueSessionStats().';
                    $this->logger($message, $result['code'], $result['erreur'] ?? self::NOERROR);
                    return $result;
                }

                $totalCategory = count($result['rows']);

                $items = array_map(function ($row) use ($totalCategory) {
                    return [
                        'category' => $row['category'],
                        'session'  => $row['session_count'],
                        'average' => (float) $row['avg_duration_min'],
                        'percent' => $totalCategory > 0
                            ? round(100 / $totalCategory, 1)
                            : 0,
                    ];
                }, $result['rows']);

                return [
                    'code' => 200,
                    'total' => $totalCategory,
                    'items' => $items,
                    'period_label' => self::TODAY,
                ];
            } catch (\Throwable $e) {
            return [
                'code' => 500,
                'erreur' => $e->getMessage(),
            ];
        }
    }

    /**
     * [Description for getSessionDurationByPeriodStats]
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @param string $label
     *
     * @return array
     *
     * Created at: 22/12/2025 14:46:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getSessionDurationByPeriodStats(\DateTimeInterface $start, \DateTimeInterface $end, string $label):array{

        try {
                $result = $this->userAgentRepos->selectSessionDurationByPeriodStats($start,$end);

                if ($result['code'] !== 200) {
                    $message = '[Statistique] ⚠️ Échec de la requête selectSessionDurationByPeriodStats().';
                    $this->logger($message, $result['code'], $result['erreur'] ?? self::NOERROR);
                    return $result;
                }

                $total = count($result['liste']);
                $rows = $result['liste'];
                $items = [];

                foreach ($rows as $row) {
                    $items[] = [
                        'date' => $row['session_date'],
                        'minute' => $row['avg_duration_minutes'],
                        'hour' => $row['avg_duration_hours'],
                        'percent' => $row['percent_of_total_avg']
                    ];
                }

                return [
                    'code' => 200,
                    'total' => $total,
                    'items' => $items,
                    'period_label' => $label,
                ];
            } catch (\Throwable $e) {
            return [
                'code' => 500,
                'erreur' => $e->getMessage(),
            ];
        }
    }

}
