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

namespace App\Command\Maintenance;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

/**
 * Génère var/admin-stats.json avec :
 *   - les compteurs cloc par langage (code / commentaires / lignes vides)
 *   - le nombre de tests listés par suite PHPUnit (sans exécution)
 *
 * Usage : php bin/console app:admin:refresh-stats
 *         php bin/console app:admin:refresh-stats -v   (mode verbeux pour diagnostiquer)
 * Prérequis : cloc installé et dans le PATH (winget install AlDanial.Cloc).
 */
#[AsCommand(
    name: 'app:admin:refresh-stats',
    description: 'Génère var/admin-stats.json (cloc + PHPUnit --list-tests).'
)]
class GenerateCodeStatsCommand extends Command
{
    private string $statsFile;
    private string $committedStatsFile;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $projectDir
    ) {
        parent::__construct();
        $this->statsFile          = $this->projectDir . '/var/admin-stats.json';
        $this->committedStatsFile = $this->projectDir . '/migrations/admin-stats.json';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // ── 1. cloc ──────────────────────────────────────────────────────────────

        $io->section('Analyse code source (cloc)');
        $cloc = $this->runCloc($io);
        if ($cloc === null) {
            return Command::FAILURE;
        }

        // ── 2. PHPUnit --list-tests ───────────────────────────────────────────────

        $io->section('Comptage des tests (phpunit --list-tests)');
        $tests = $this->countTests($io);

        // ── 3. Écriture JSON ─────────────────────────────────────────────────────

        $payload = [
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'cloc'         => $cloc,
            'tests'        => $tests,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Écrit dans var/ (local, non commité) ET dans migrations/ (commité, survit au déploiement)
        file_put_contents($this->statsFile, $json);
        file_put_contents($this->committedStatsFile, $json);

        $io->success('Sauvegardé dans var/admin-stats.json + migrations/admin-stats.json');

        // ── 4. Résumé ─────────────────────────────────────────────────────────────

        $io->table(
            ['Langage', 'Fichiers', 'Code', 'Commentaires', 'Vides', 'Total'],
            array_map(
                fn(string $key, array $d) => [
                    strtoupper($key), $d['fichier'], $d['code'], $d['comment'], $d['vide'], $d['total'],
                ],
                array_keys($cloc),
                array_values($cloc)
            )
        );

        foreach ($tests as $suite => $info) {
            $count = $info['count'] ?? 'n/a';
            $io->writeln("  <info>$suite</info> : $count tests");
        }

        return Command::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * [Description for runCloc]
     *
     * @param SymfonyStyle $io
     *
     * @return array<string, array{fichier:int,code:int,comment:int,vide:int,total:int}>|null
     *
     * Created at: 22/07/2026 11:18:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function runCloc(SymfonyStyle $io): ?array
    {
        // Correspondance nom cloc → clé interne (insensible à la casse, sous-chaîne)
        $langMap = [
            'php'        => 'php',
            'html'       => 'html',
            'twig'       => 'html',
            'css'        => 'css',
            'javascript' => 'js',
            'markdown'   => 'md',
        ];

        // Passe 1 : code source principal — chemins relatifs à $projectDir
        [$ok, $data1] = $this->clocJson(['src', 'templates', 'assets'], 'passe-1', $io);
        if (!$ok) {
            $io->error(
                "cloc introuvable. Installez-le :\n" .
                "  Windows : winget install AlDanial.Cloc\n" .
                "  Linux   : sudo apt install cloc  (ou dnf/brew)"
            );
            return null;
        }

        // Passe 2 : scripts SQL d'initialisation — tout migrations/POSTGRESQL/ (récursif, capte
        // aussi 99_master_install.sql à la racine), sauf updates/ (compté à part en passe 3).
        // Une liste de sous-dossiers codée en dur serait
        // silencieusement incomplète au prochain ajout/renommage (cf. correction du 2026-07-21).
        [, $data2] = $this->clocJson(['migrations/POSTGRESQL'], 'passe-2', $io, ['--exclude-dir=updates']);

        // Passe 3 : scripts SQL d'updates
        [, $data3] = $this->clocJson(['migrations/POSTGRESQL/updates'], 'passe-3', $io);

        // Accumulation langages principaux (HTML + Twig fusionnés dans 'html')
        $cloc = [];
        foreach ($data1 as $langName => $langData) {
            $key = $this->mapLang((string)$langName, $langMap);
            if ($key === null) {
                continue;
            }
            if (!isset($cloc[$key])) {
                $cloc[$key] = ['fichier' => 0, 'code' => 0, 'comment' => 0, 'vide' => 0];
            }
            $cloc[$key]['fichier']  += (int)($langData['nFiles']  ?? 0);
            $cloc[$key]['code']     += (int)($langData['code']    ?? 0);
            $cloc[$key]['comment']  += (int)($langData['comment'] ?? 0);
            $cloc[$key]['vide']     += (int)($langData['blank']   ?? 0);
        }

        // SQL initialisation → clé 'sql'  (insensible à la casse : 'SQL', 'SQL Script', …)
        $cloc['sql']       = $this->extractSqlRow($data2);

        // SQL updates → clé 'migration'
        $cloc['migration'] = $this->extractSqlRow($data3);

        // Calcul du total = code + comment + vide
        foreach ($cloc as &$d) {
            $d['total'] = $d['code'] + $d['comment'] + $d['vide'];
        }
        unset($d);

        // Rapport console
        foreach ($cloc as $key => $d) {
            $io->writeln("  <info>$key</info> : {$d['fichier']} fichiers, {$d['code']} lignes code");
        }

        return $cloc;
    }

    /**
     * [Description for clocJson]
     *
     * @param string[] $relativePaths
     * @param string[] $extraArgs
     *
     * @return array{0:bool, 1:array<string, array<string, int>>}
     *
     * Created at: 22/07/2026 11:15:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function clocJson(array $relativePaths, string $passLabel, SymfonyStyle $io, array $extraArgs = []): array
    {
        // Vérifie l'existence via le chemin absolu, passe le chemin clsrelatif à cloc
        $existing = [];
        foreach ($relativePaths as $p) {
            $abs    = $this->projectDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $p);
            $exists = is_dir($abs);
            if ($io->isVerbose()) {
                $flag = $exists ? '<info>OK</info>' : '<error>ABSENT</error>';
                $io->writeln("  [$passLabel] is_dir($abs) → $flag");
            }
            if ($exists) {
                $existing[] = $p;   // chemin relatif passé à cloc
            }
        }

        if ($existing === []) {
            return [true, []];
        }

        $cmd     = array_merge(['cloc', '--json'], $extraArgs, $existing);
        $process = new Process($cmd);
        $process->setWorkingDirectory($this->projectDir);
        $process->setTimeout(120);
        $process->run();

        $out = trim($process->getOutput());

        if ($io->isVerbose()) {
            $io->writeln("  [$passLabel] CMD  : " . implode(' ', $cmd));
            $io->writeln("  [$passLabel] EXIT : " . $process->getExitCode());
            $io->writeln("  [$passLabel] STDOUT (200c) : " . substr($out, 0, 200));
            if ($err = $process->getErrorOutput()) {
                $io->writeln("  [$passLabel] STDERR : " . substr($err, 0, 200));
            }
        }

        if ($out === '') {
            // Stdout vide + code non nul → binaire absent ou erreur fatale (multi-langue)
            $exitCode = $process->getExitCode();
            if ($exitCode !== 0) {
                return [false, []];
            }
            // cloc présent mais aucun fichier reconnu dans le répertoire
            return [true, []];
        }

        $decoded = json_decode($out, true);
        if (!is_array($decoded)) {
            return [true, []];
        }

        unset($decoded['header'], $decoded['SUM']);

        if ($io->isVerbose()) {
            $io->writeln("  [$passLabel] KEYS : " . implode(', ', array_keys($decoded)));
        }

        return [true, $decoded];
    }

    /**
     * [Description for extractSqlRow]
     * Cherche n'importe quelle entrée dont le nom contient "sql" (insensible à la casse).
     *
     * @param array<int|string, array<string, mixed>> $data
     *
     * @return array{fichier: int, code: int, comment: int, vide: int, total: int}
     *
     * Created at: 22/07/2026 11:15:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function extractSqlRow(array $data): array
    {
        foreach ($data as $langName => $langData) {
            if (stripos((string)$langName, 'sql') !== false) {
                return [
                    'fichier' => (int)($langData['nFiles']  ?? 0),
                    'code'    => (int)($langData['code']    ?? 0),
                    'comment' => (int)($langData['comment'] ?? 0),
                    'vide'    => (int)($langData['blank']   ?? 0),
                    'total'   => 0,
                ];
            }
        }
        return ['fichier' => 0, 'code' => 0, 'comment' => 0, 'vide' => 0, 'total' => 0];
    }

    /**
     * [Description for mapLang]
     * Mappe un nom de langage cloc vers une clé interne.
     *
     * @param array<string,string> $map
     *
     * @return string|null
     *
     * Created at: 22/07/2026 11:16:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function mapLang(string $langName, array $map): ?string
    {
        return $map[strtolower($langName)] ?? null;
    }

    /**
     * [Description for sqlRow]
     *
     * @param array<string, int>|null $row
     *
     * @return array{fichier: int, code: int, comment: int, vide: int, total: int}
     *
     * Created at: 22/07/2026 11:16:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function sqlRow(?array $row): array
    {
        return [
            'fichier' => (int)($row['nFiles']  ?? 0),
            'code'    => (int)($row['code']    ?? 0),
            'comment' => (int)($row['comment'] ?? 0),
            'vide'    => (int)($row['blank']   ?? 0),
            'total'   => 0,
        ];
    }

    /**
     * [Description for countTests]
     *
     * @param SymfonyStyle $io
     *
     * @return array<string, array{count:int|null}>
     *
     * Created at: 22/07/2026 11:17:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function countTests(SymfonyStyle $io): array
    {
        $result  = [];
        $phpunit = $this->projectDir . '/vendor/bin/phpunit';

        foreach (['unit', 'integration'] as $suite) {
            $process = new Process(['php', $phpunit, '--testsuite', $suite, '--list-tests']);
            $process->setWorkingDirectory($this->projectDir);
            $process->setTimeout(120);
            $process->run();

            if (!$process->isSuccessful()) {
                $io->warning("Suite \"$suite\" : phpunit --list-tests a échoué, compteur ignoré.");
                $result[$suite] = ['count' => null];
                continue;
            }

            $count          = (int)preg_match_all('/^ - /m', $process->getOutput());
            $result[$suite] = ['count' => $count];
            $io->writeln("  <info>$suite</info> : $count tests listés");
        }

        return $result;
    }
}
