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

/* MODIF 2026-05-21 : converti depuis run_ma-moulinette_collecte_cli.php
   (v1.4.1) vers Symfony Command (namespace App\Command\Prod, CommandTester, LoggerInterface). */
namespace App\Command\Prod;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * MODIF 2026-05-21 : conversion du script CLI
 * `run_ma-moulinette_collecte_cli.php` (v1.4.1) en Symfony Command.
 *
 * Lance les traitements automatiques Ma-Moulinette :
 *   1. Récupère la liste des traitements non démarrés
 *      via POST /api/public/traitement/automatique/liste
 *   2. Démarre chaque traitement
 *      via POST /api/public/traitement/automatique/start
 *   3. Retourne Command::SUCCESS si aucune erreur, Command::FAILURE sinon
 *      (compatible VTOM : code de retour exploitable par l'ordonnanceur)
 *
 * Usage :
 *   php bin/console app:collecte:run --url="https://ma.moulinette.fr" --token="La clé est celle de API_CLIENT_TOKEN"
 *   php bin/console app:collecte:run --url=... --token=... --flush=5 --dry-run
 */
#[AsCommand(
    name: 'app:collecte:run',
    description: 'Lance les traitements automatiques Ma-Moulinette (batch collecte SonarQube).',
)]
class RunMoulinetteCollecteCommand extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly int $mamoulCliVerifyPeer,
        private readonly int $mamoulCliVerifyHost,
        private readonly string $mamoulCliCiphers,
        private readonly int $mamoulCliUseProxy,
        private readonly string $mamoulCliProxyUrl,
        private readonly string $mamoulCliProxyUserPwd,
        private readonly string $mamoulCliNoProxy
    ) {
        parent::__construct();
    }

    /**
     * [Description for configure]
     *
     * @return void
     *
     * Created at: 24/05/2026 09:49:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function configure(): void
    {
        $this
            ->addOption('url',          null, InputOption::VALUE_REQUIRED, 'URL du serveur Ma-Moulinette (obligatoire).')
            ->addOption('token',        null, InputOption::VALUE_REQUIRED, "Token d'accès Ma-Moulinette (obligatoire).")
            ->addOption('flush',        null, InputOption::VALUE_OPTIONAL, 'Nombre max de traitements à envoyer.', 0)
            // MODIF 2026-07-21 : --keep-days/--max-log-size supprimées — déclarées
            // mais jamais lues (aucun getOption() correspondant), et absentes de
            // l'appel cron (docker/etc/cron/ma-moulinette) : options mortes.
            ->addOption('dry-run',      null, InputOption::VALUE_NONE,     'Simulation sans modification réelle.')
            ->addOption('debug',        null, InputOption::VALUE_NONE,     'Affiche les détails des appels API.');
    }

    /**
     * [Description for execute]
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * Created at: 24/05/2026 09:50:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $url        = (string) ($input->getOption('url') ?? '');
        $token      = (string) ($input->getOption('token') ?? '');
        $flush      = max(0, (int) $input->getOption('flush'));
        $dryRun     = (bool) $input->getOption('dry-run');
        $debug      = (bool) $input->getOption('debug');

        if ($url === '') {
            $io->error("L'option --url est obligatoire.");
            return Command::FAILURE;
        }
        if ($token === '') {
            $io->error("L'option --token est obligatoire.");
            return Command::FAILURE;
        }

        $url         = rtrim($url, '/');
        $sslConfig   = $this->buildSslConfig();
        $proxyConfig = $this->buildProxyConfig();

        if ($dryRun) {
            $io->note('Mode simulation activé (aucune modification ne sera envoyée).');
        }
        $this->logger->info('[Collecte] Démarrage batch.', ['url' => $url, 'dry_run' => $dryRun]);

        // Récupère les traitements disponibles
        try {
            $traitements = $this->getTraitementsAutomatiques($url, $token, $sslConfig, $proxyConfig, $debug, $io);
        } catch (\Throwable $e) {
            $io->error('Impossible de récupérer les traitements : ' . $e->getMessage());
            $this->logger->error('[Collecte] Erreur récupération traitements.', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
        if (count($traitements) === 0) {
            $io->text('Aucun traitement automatique disponible. Fin du batch.');
            $this->logger->info('[Collecte] Aucun traitement disponible.');
            return Command::SUCCESS;
        }

        if ($flush > 0 && count($traitements) > $flush) {
            $io->warning(sprintf('Mode flush : envoi des %d premiers traitements sur %d.', $flush, count($traitements)));
            $traitements = array_slice($traitements, 0, $flush);
        }

        $hasError = false;
        $t0       = microtime(true);

        foreach ($traitements as $traitement) {
            $nom     = (string) ($traitement['nom_traitement'] ?? '(inconnu)');
            $projets = $traitement['nombre_projet'] ?? '?';

            $io->text(sprintf('Traitement : %s (%s projets)', $nom, $projets));

            $tStart = microtime(true);
            try {
                $result = $this->startTraitement($url, $token, $sslConfig, $proxyConfig, $traitement, $dryRun, $debug, $io);
            } catch (\Throwable $e) {
                $io->error(sprintf('Exception PHP pendant %s : %s', $nom, $e->getMessage()));
                $this->logger->error('[Collecte] Exception traitement.', ['nom' => $nom, 'error' => $e->getMessage()]);
                $hasError = true;
                continue;
            }

            $duration = round(microtime(true) - $tStart, 2);
            $code     = $result['code'] ?? 0;
            $msg      = $result['message'] ?? '(aucun message)';

            if ($code === 200) {
                $io->text(sprintf('  ✓ %s | %.2fs | %s', $nom, $duration, $msg));
                $this->logger->info('[Collecte] Traitement OK.', ['nom' => $nom, 'duration' => $duration]);
            } else {
                $io->warning(sprintf('  ✗ %s | code=%d | %s', $nom, $code, $msg));
                $this->logger->warning('[Collecte] Traitement KO.', ['nom' => $nom, 'code' => $code, 'msg' => $msg]);
                $hasError = true;
            }
        }

        $totalTime = round(microtime(true) - $t0, 2);
        $io->text(sprintf('Batch terminé en %.2fs.', $totalTime));

        if ($hasError) {
            $io->error('Fin du batch avec anomalies (code de retour 1).');
            $this->logger->error('[Collecte] Batch terminé avec erreurs.');
            return Command::FAILURE;
        }

        $io->success('Batch terminé sans erreur.');
        $this->logger->info('[Collecte] Batch terminé.', ['duration' => $totalTime]);
        return Command::SUCCESS;
    }

    /**
     * [Description for buildSslConfig]
     *
     * @return array{verify_peer: int, verify_host: int, ciphers: string}
     *
     * Created at: 24/05/2026 09:50:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function buildSslConfig(): array
    {
        /* MODIF 2026-07-21 : verify_peer/verify_host sont typés int non-nullables
         * (résolus par Symfony via %env(int:MAMOUL_CLI_VERIFY_*)%, qui échoue au
         * démarrage si la variable est absente) — un "?: 0" ici ne pouvait donc
         * jamais retomber sur un défaut sûr, il ne faisait que réécrire la même
         * valeur en donnant l'illusion trompeuse d'un fallback. Voir
         * docker/.env.template pour les valeurs par défaut réellement sûres. */
        return [
            'verify_peer' => $this->mamoulCliVerifyPeer,
            'verify_host' => $this->mamoulCliVerifyHost,
            'ciphers'     => $this->mamoulCliCiphers ?: 'DEFAULT:!DH',
        ];
    }

    /**
     * [Description for buildProxyConfig]
     *
     * @return array{use_proxy: bool, proxy_url: string, proxy_userpwd: string, no_proxy: string[]}
     *
     * Created at: 24/05/2026 09:50:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function buildProxyConfig(): array
    {
        $noProxyEnv = $this->mamoulCliNoProxy;
        $noProxy = trim($noProxyEnv) === ''
            ? ['localhost', '127.0.0.1']
            : array_values(array_filter(array_map('trim', explode(',', $noProxyEnv))));
        return [
            'use_proxy'     => (bool) ($this->mamoulCliUseProxy ?: false),
            'proxy_url'     => $this->mamoulCliProxyUrl ?: 'http://proxy.ma-moulinette.fr:8080',
            'proxy_userpwd' => $this->mamoulCliProxyUserPwd ?: '',
            'no_proxy'      => $noProxy,
        ];
    }

    /**
     * [Description for callApi]
     * Appel cURL vers l'API Ma-Moulinette.
     * Protected pour permettre le mock dans les tests.
     *
     * @param string $baseUrl
     * @param array<string, mixed> $sslConfig
     * @param array<string, mixed> $proxyConfig
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     * @param string $endpoint
     * @param string $method
     * @param bool $debug
     * @param SymfonyStyle $io
     * @param int $timeout
     *
     * @return array<string, mixed>
     *
     * Created at: 24/05/2026 09:53:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function callApi(
        string $baseUrl,
        array $sslConfig,
        array $proxyConfig,
        string $endpoint,
        array $params,
        string $method,
        bool $debug,
        SymfonyStyle $io,
        int $timeout = 600
    ): array {
        $url     = $baseUrl . $endpoint;
        $ch      = curl_init();
        $headers = ['Accept: application/json'];

        if ($method === 'POST') {
            $payload = json_encode($params, JSON_UNESCAPED_UNICODE);
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => $sslConfig['verify_host'],
            CURLOPT_SSL_VERIFYPEER => $sslConfig['verify_peer'],
            CURLOPT_SSL_CIPHER_LIST=> $sslConfig['ciphers'],
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if ($proxyConfig['use_proxy'] && $proxyConfig['proxy_url'] !== '') {
            curl_setopt($ch, CURLOPT_PROXY, $proxyConfig['proxy_url']);
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
            if ($proxyConfig['proxy_userpwd'] !== '') {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyConfig['proxy_userpwd']);
            }
        }

        if (!empty($proxyConfig['no_proxy'])) {
            curl_setopt($ch, CURLOPT_NOPROXY, implode(',', $proxyConfig['no_proxy']));
        }

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['code' => 500, 'message' => 'Erreur cURL : ' . $error];
        }
        curl_close($ch);

        if ($debug) {
            $io->text(sprintf('[DEBUG] %s %s → %d', $method, $url, $status));
        }

        $json = json_decode((string) $response, true);
        if (!is_array($json)) {
            return ['code' => $status, 'message' => 'Invalid JSON', 'response' => (string) $response];
        }
        return $json;
    }

    /**
     * [Description for getTraitementsAutomatiques]
     *
     * @param string $url
     * @param string $token
     * @param array<string, mixed> $sslConfig
     * @param array<string, mixed> $proxyConfig
     * @param bool $debug
     * @param SymfonyStyle $io
     *
     * @return array<int, array<string, mixed>>
     *
     * Created at: 24/05/2026 09:55:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getTraitementsAutomatiques(
        string $url,
        string $token,
        array $sslConfig,
        array $proxyConfig,
        bool $debug,
        SymfonyStyle $io
    ): array {
        $data = $this->callApi($url, $sslConfig, $proxyConfig,
            '/api/public/traitement/automatique/liste', ['token' => $token], 'POST', $debug, $io);

        if (!isset($data['code']) || $data['code'] !== 200) {
            $this->logger->error('[Collecte] Erreur récupération traitements.', ['response' => $data]);
            return [];
        }
        return (array) ($data['liste_traitement'] ?? []);
    }

    /**
     * [Description for startTraitement]
     *
     * @param string $url
     * @param string $token
     * @param array<string, mixed> $sslConfig
     * @param array<string, mixed> $proxyConfig
     * @param array<string, mixed> $traitement
     * @param bool $dryRun
     * @param bool $debug
     * @param SymfonyStyle $io
     *
     * @return array<string, mixed>
     *
     * Created at: 24/05/2026 09:57:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function startTraitement(
        string $url,
        string $token,
        array $sslConfig,
        array $proxyConfig,
        array $traitement,
        bool $dryRun,
        bool $debug,
        SymfonyStyle $io
    ): array {
        if ($dryRun) {
            $io->text(sprintf('  [DRY-RUN] Simulation : démarrage du traitement %s', $traitement['nom_traitement'] ?? ''));
            return ['code' => 200, 'message' => 'Simulation réussie'];
        }

        $params = [
            'token'           => $token,
            'nom_traitement'  => (string) ($traitement['nom_traitement']  ?? ''),
            'portefeuille'    => (string) ($traitement['portefeuille']    ?? ''),
            'traitement_id'   => (string) ($traitement['traitement_id']   ?? ''),
        ];

        return $this->callApi($url, $sslConfig, $proxyConfig,
            '/api/public/traitement/automatique/start', $params, 'POST', $debug, $io);
    }
}
