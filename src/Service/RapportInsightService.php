<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*/

namespace App\Service;

/**
 * Génère des "insights" pour le rapport PDF Suivi a partir d'une liste de
 * versions historisées (sortie de HistoriqueRepository::selectHistoriqueProjetForRapport).
 *
 * Deux usages :
 *   - extractKpi($suivi)        -> 6 indicateurs clés de la dernière version + delta vs N-1.
 *   - extractTopAlerts($suivi)  -> alertes triées par criticité (note D/E, QG ERROR,
 *                                  degradation forte) avec phrase narrative.
 *
 * Pas d'IO, pas de DB : pur calcul sur le tableau passe en paramètre.
 *
 * Created at: 06/05/2026
 * @author     Laurent HADJADJ <laurent_h@me.com>
 *
 * @phpstan-type Trend 'up'|'down'|'flat'|'na'
 * @phpstan-type Severity 'ok'|'warn'|'error'|'na'
 * @phpstan-type Kpi array{
 *     label: string,
 *     value: string|null,
 *     note: string|null,
 *     trend: Trend,
 *     delta_label: string|null,
 *     severity: Severity
 * }
 */
class RapportInsightService
{
    private const QG_SCALE   = ['OK' => 1, 'WARN' => 2, 'ERROR' => 3];

    /**
     * [Description for extractKpi]
     * KPIs de la dernière version + delta vs N-1.
     *
     * @param array<int, array<string, mixed>> $suivi
     * @return array<int, Kpi>
     *
     *
     * Created at: 14/07/2026 20:15:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function extractKpi(array $suivi): array
    {
        $count = count($suivi);
        if ($count === 0) {
            return [];
        }
        $last = $suivi[$count - 1];
        $prev = $count >= 2 ? $suivi[$count - 2] : null;

        return [
            $this->kpiQualityGate($last, $prev),
            $this->kpiCoverage($last, $prev),
            $this->kpiBugs($last, $prev),
            $this->kpiVulnerabilities($last, $prev),
            $this->kpiHotspots($last, $prev),
            $this->kpiMaintainability($last, $prev),
        ];
    }

    /**
     * [Description for extractTopAlerts]
     * Top N alertes triées par criticité. Chaque alerte porte une phrase prêtes-a-lire.
     *
     * @param array<int, array<string, mixed>> $suivi
     * @return array<int, array{
     *     titre: string,
     *     phrase: string,
     *     severity: 'warn'|'error'
     * }>
     *
     * Created at: 14/07/2026 20:16:48 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function extractTopAlerts(array $suivi, int $limit = 5): array
    {
        $count = count($suivi);
        if ($count === 0) {
            return [];
        }
        $last = $suivi[$count - 1];
        $first = $suivi[0];
        $alerts = [];

        // 1. Quality Gate ERROR
        if (($last['alert_status'] ?? null) !== null && strtoupper((string) $last['alert_status']) === 'ERROR') {
            $alerts[] = [
                'titre' => 'Quality Gate en échec',
                'phrase' => 'Le Quality Gate de la dernière version est en ERREUR — au moins un seuil bloquant est dépasse.',
                'severity' => 'error',
                'score' => 100,
            ];
        }

        // 2. Notes en zone D / E sur la dernière version
        $notes = [
            'reliability' => 'Fiabilité',
            'security' => 'Sécurité',
            'maintainability' => 'Maintenabilité',
            'note_hotspot' => 'Review Hotspots',
            'note_complexity' => 'Complexité cyclomatique',
            'note_cognitive' => 'Complexité cognitive',
        ];
        foreach ($notes as $col => $label) {
            $val = $last[$col] ?? null;
            if ($val === null || $val === '') {
                continue;
            }
            $note = strtoupper((string) $val);
            if (in_array($note, ['D', 'E'], true)) {
                $first_val = $first[$col] ?? null;
                $deltaPhrase = '';
                if ($first_val !== null && $first_val !== '' && $count >= 2) {
                    $first_note = strtoupper((string) $first_val);
                    if ($first_note !== $note) {
                        $deltaPhrase = sprintf(' (passé de %s à %s sur %d versions)', $first_note, $note, $count);
                    } else {
                        $deltaPhrase = sprintf(' (stable depuis %d versions)', $count);
                    }
                }
                $alerts[] = [
                    'titre' => sprintf('Note %s : %s', $label, $note),
                    'phrase' => sprintf('La note %s est en zone critique : %s%s.', $label, $note, $deltaPhrase),
                    'severity' => $note === 'E' ? 'error' : 'warn',
                    'score' => $note === 'E' ? 90 : 80,
                ];
            }
        }

        // 3. Couverture < seuils
        $cov = $last['coverage'] ?? null;
        if ($cov !== null) {
            $covF = (float) $cov;
            $firstCov = $first['coverage'] ?? null;
            if ($covF < 50.0) {
                $sev = $covF < 30.0 ? 'error' : 'warn';
                $delta = '';
                if ($firstCov !== null && $count >= 2) {
                    $delta = sprintf(' (de %s %% à %s %% sur %d versions)',
                        number_format((float) $firstCov, 1, ',', ' '),
                        number_format($covF, 1, ',', ' '),
                        $count);
                }
                $alerts[] = [
                    'titre' => sprintf('Couverture faible : %s %%', number_format($covF, 1, ',', ' ')),
                    'phrase' => sprintf('La couverture globale (%s %%) est sous le seuil%s%s.',
                        number_format($covF, 1, ',', ' '),
                        $covF < 30.0 ? ' critique de 30 %' : ' recommandé de 50 %',
                        $delta),
                    'severity' => $sev,
                    'score' => $sev === 'error' ? 85 : 70,
                ];
            } elseif ($firstCov !== null && $count >= 2 && (float) $firstCov - $covF >= 10.0) {
                $alerts[] = [
                    'titre' => 'Couverture en forte baisse',
                    'phrase' => sprintf('La couverture est passée de %s %% à %s %% sur %d versions (-%s pts).',
                        number_format((float) $firstCov, 1, ',', ' '),
                        number_format($covF, 1, ',', ' '),
                        $count,
                        number_format((float) $firstCov - $covF, 1, ',', ' ')),
                    'severity' => 'warn',
                    'score' => 65,
                ];
            } else {
                // MODIF 2026-05-26 : else terminal requis par S126.
                // Couverture stable ou données insuffisantes : aucune alerte générée.
            }
        }

        // 4. Tests en échec / erreurs sur la dernière version
        $te = (int) ($last['test_errors'] ?? 0);
        $tf = (int) ($last['test_failures'] ?? 0);
        if ($te + $tf > 0) {
            $alerts[] = [
                'titre' => sprintf('Tests en échec : %d', $te + $tf),
                'phrase' => sprintf('La dernière exécution rapporte %d erreur(s) et %d échec(s) — la suite de tests est instable.',
                    $te, $tf),
                'severity' => 'error',
                'score' => 75,
            ];
        }

        // 5. Hotspots non revus élevés
        $hot = $last['menace_potentielle_totale'] ?? null;
        if ($hot !== null && (int) $hot > 0) {
            $hotInt = (int) $hot;
            if ($hotInt >= 10) {
                $alerts[] = [
                    'titre' => sprintf('%d hotspots à revoir', $hotInt),
                    'phrase' => sprintf('%d hotspots de sécurité restent à examiner sur la dernière version.', $hotInt),
                    'severity' => $hotInt >= 50 ? 'error' : 'warn',
                    'score' => $hotInt >= 50 ? 60 : 50,
                ];
            }
        }

        // 6. Degradation forte de la complexité
        if ($count >= 2) {
            $cFirst = $first['complexity_ratio'] ?? null;
            $cLast = $last['complexity_ratio'] ?? null;
            if ($cFirst !== null && $cLast !== null && (float) $cLast - (float) $cFirst >= 5.0) {
                $alerts[] = [
                    'titre' => 'Complexité cyclomatique en hausse',
                    'phrase' => sprintf('Le ratio de complexité cyclomatique est passé de %s %% à %s %% (+%s pts).',
                        number_format((float) $cFirst, 1, ',', ' '),
                        number_format((float) $cLast, 1, ',', ' '),
                        number_format((float) $cLast - (float) $cFirst, 1, ',', ' ')),
                    'severity' => 'warn',
                    'score' => 55,
                ];
            }
        }

        // Tri par score desc, garde les N premiers. Le score est un critère de tri interne :
        // il n'est pas exposé, chaque alerte est reconstruite sans lui.
        usort($alerts, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_map(
            static fn(array $a): array => [
                'titre' => $a['titre'],
                'phrase' => $a['phrase'],
                'severity' => $a['severity'],
            ],
            array_slice($alerts, 0, $limit)
        );
    }

    // ---- KPI helpers (1 par carte) ----

    /**
     * @param array<string, mixed> $last
     * @param array<string, mixed>|null $prev
     * @return Kpi
     */
    private function kpiQualityGate(array $last, ?array $prev): array
    {
        $val = $last['alert_status'] ?? null;
        $valU = $val === null ? null : strtoupper((string) $val);
        $sev = match ($valU) {
            'OK' => 'ok',
            'WARN' => 'warn',
            'ERROR' => 'error',
            default => 'na',
        };
        return [
            'label' => 'Quality Gate',
            'value' => $valU,
            'note' => null,
            'trend' => $this->trendByScale($prev['alert_status'] ?? null, $val, self::QG_SCALE),
            'delta_label' => $this->deltaLabelByScale($prev['alert_status'] ?? null, $val),
            'severity' => $sev,
        ];
    }

    /**
     * [Description for kpiCoverage]
     *
     * @param array<string, mixed> $last
     * @param array<string, mixed>|null $prev
     *
     * @return Kpi
     *
     * Created at: 14/07/2026 20:17:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function kpiCoverage(array $last, ?array $prev): array
    {
        $cov = $last['coverage'] ?? null;
        $covF = $cov === null ? null : (float) $cov;
        $sev = $covF === null ? 'na' : ($covF >= 80.0 ? 'ok' : ($covF >= 50.0 ? 'warn' : 'error'));
        $value = $covF === null ? null : number_format($covF, 1, ',', ' ') . ' %';

        $delta = null;
        $trend = 'na';
        $prevCov = $prev['coverage'] ?? null;
        if ($covF !== null && $prevCov !== null) {
            $diff = $covF - (float) $prevCov;
            $delta = sprintf('%s%s pts', $diff >= 0 ? '+' : '', number_format($diff, 1, ',', ' '));
            $trend = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'flat');
        }
        return [
            'label' => 'Couverture',
            'value' => $value,
            'note' => null,
            'trend' => $trend,
            'delta_label' => $delta,
            'severity' => $sev,
        ];
    }

    /**
     * [Description for kpiBugs]
     *
     * @param array<string, mixed> $last
     * @param array<string, mixed>|null $prev
     *
     * @return Kpi
     *
     * Created at: 14/07/2026 20:17:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function kpiBugs(array $last, ?array $prev): array
    {
        $val = $last['bug'] ?? null;
        $note = $this->toNote($last['reliability'] ?? null);
        $sev = $this->severityFromNote($note);
        $value = $val === null ? null : number_format((float) $val, 0, ',', ' ');
        return $this->kpiCount('Bugs', $val, $note, $value, $sev, $prev['bug'] ?? null, false);
    }

    /**
     * [Description for kpiVulnerabilities]
     *
     * @param array<string, mixed> $last
     * @param array<string, mixed>|null $prev
     *
     * @return Kpi
     *
     * Created at: 14/07/2026 20:18:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function kpiVulnerabilities(array $last, ?array $prev): array
    {
        $val = $last['faille'] ?? null;
        $note = $this->toNote($last['security'] ?? null);
        $sev = $this->severityFromNote($note);
        $value = $val === null ? null : number_format((float) $val, 0, ',', ' ');
        return $this->kpiCount('Vulnérabilités', $val, $note, $value, $sev, $prev['faille'] ?? null, false);
    }

    /**
     * [Description for kpiHotspots]
     *
     * @param array<string, mixed> $last
     * @param array<string, mixed>|null $prev
     *
     * @return Kpi
     *
     * Created at: 14/07/2026 20:18:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function kpiHotspots(array $last, ?array $prev): array
    {
        $val = $last['menace_potentielle_totale'] ?? null;
        $valI = ($val === null || (int) $val === -1) ? null : (int) $val;
        $note = $this->toNote($last['note_hotspot'] ?? null);
        $sev = $this->severityFromNote($note);
        $value = $valI === null ? null : number_format($valI, 0, ',', ' ');
        return $this->kpiCount('Hotspots', $valI, $note, $value, $sev, $prev['menace_potentielle_totale'] ?? null, false);
    }

    /**
     * [Description for kpiMaintainability]
     *
     * @param array<string, mixed> $last
     * @param array<string, mixed>|null $prev
     *
     * @return Kpi
     *
     * Created at: 14/07/2026 20:18:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function kpiMaintainability(array $last, ?array $prev): array
    {
        $val = $last['mauvaise_pratique'] ?? null;
        $note = $this->toNote($last['maintainability'] ?? null);
        $sev = $this->severityFromNote($note);
        $value = $val === null ? null : number_format((float) $val, 0, ',', ' ');
        return $this->kpiCount('Maintenabilité', $val, $note, $value, $sev, $prev['mauvaise_pratique'] ?? null, false);
    }

    // ---- Helpers ----

    /**
     * [Description for kpiCount]
     * Construit la map de KPI "compte + note", calcule la tendance par delta numerique
     * (lower is better : moins de bugs/vulns/hotspots/code smells).
     *
     * @param string $label
     * @param mixed $current
     * @param string|null $note
     * @param string|null $valueStr
     * @param Severity $sev
     * @param mixed $previous
     * @param bool $higherIsBetter
     *
     * @return Kpi
     *
     * Created at: 14/07/2026 20:19:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function kpiCount(string $label, mixed $current, ?string $note, ?string $valueStr, string $sev, mixed $previous, bool $higherIsBetter): array
    {
        $delta = null;
        $trend = 'na';
        if ($current !== null && $previous !== null) {
            $diff = (float) $current - (float) $previous;
            $delta = sprintf('%s%s', $diff >= 0 ? '+' : '', number_format($diff, 0, ',', ' '));
            if ($higherIsBetter) {
                $trend = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'flat');
            } else {
                $trend = $diff < 0 ? 'up' : ($diff > 0 ? 'down' : 'flat');
            }
        }
        $noteU = $note === null ? null : strtoupper($note);
        return [
            'label' => $label,
            'value' => $valueStr,
            'note' => $noteU,
            'trend' => $trend,
            'delta_label' => $delta,
            'severity' => $sev,
        ];
    }

    /**
     * [Description for toNote]
     * Normalise une valeur brute de la base (mixed) en note exploitable : une chaîne, ou null.
     *
     * @param mixed $value
     *
     * @return string|null
     *
     * Created at: 14/07/2026
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function toNote(mixed $value): ?string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }
        return (string) $value;
    }

    /**
     * [Description for severityFromNote]
     * Mapping note A-E -> sévérité (ok/warn/error). Les notes A, B = ok, C = warn, D, E = error.
     *
     * @param string|null $note
     *
     * @return Severity
     *
     * Created at: 14/07/2026 20:19:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function severityFromNote(?string $note): string
    {
        if ($note === null || $note === '') {
            return 'na';
        }
        $n = strtoupper($note);
        return match ($n) {
            'A', 'B' => 'ok',
            'C' => 'warn',
            'D', 'E' => 'error',
            default => 'na',
        };
    }

    /**
     * [Description for trendByScale]
     * Tendance entre 2 valeurs notées ou QG (lower is better dans les 2 échelles).
     *
     * @param array<string, int> $scale
     *
     * @return Trend
     *
     * Created at: 14/07/2026 20:20:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function trendByScale(mixed $first, mixed $last, array $scale): string
    {
        if ($first === null || $last === null) {
            return 'na';
        }
        $f = $scale[strtoupper((string) $first)] ?? null;
        $l = $scale[strtoupper((string) $last)] ?? null;
        if ($f === null || $l === null) {
            return 'na';
        }
        return $l < $f ? 'up' : ($l > $f ? 'down' : 'flat');
    }

    /**
     * [Description for deltaLabelByScale]
     *
     * @param mixed $first
     * @param mixed $last
     *
     * @return string|null
     *
     * Created at: 14/07/2026 20:20:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function deltaLabelByScale(mixed $first, mixed $last): ?string
    {
        if ($first === null || $last === null) {
            return null;
        }
        $f = strtoupper((string) $first);
        $l = strtoupper((string) $last);
        if ($f === $l) {
            return null;
        }
        return $f . ' → ' . $l;
    }
}
