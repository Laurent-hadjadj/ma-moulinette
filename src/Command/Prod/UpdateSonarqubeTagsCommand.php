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

/* MODIF 2026-05-21 : converti depuis update_sonarqube_tags_cli.php
   vers Symfony Command (namespace App\Command\Prod, CommandTester, LoggerInterface). */
namespace App\Command\Prod;

use App\Exception\SonarCommandException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * MODIF 2026-05-21 : conversion du script CLI
 * `update_sonarqube_tags_cli.php` (v1.1.0) en Symfony Command.
 *
 * Met à jour les tags SonarQube de tous les projets :
 *   1. Récupère la liste complète des projets via /api/components/search_projects
 *   2. Efface les tags existants (setTag="aucun")
 *   3. Détecte le groupe de permissions du projet (critère : permissions
 *      {codeviewer,securityhotspotadmin,user} + description contient "2021")
 *   4. Si groupe "Archive" → tag="archive" ; sinon tag=sanitize(groupName)
 *   5. Sauvegarde le mapping maven_key→groupe dans mapping_projets_groupes.json
 *
 * Config SSL/proxy : variables d'environnement SONAR_CLI_* (voir buildSslConfig).
 *
 * Usage :
 *   php bin/console app:sonar:update-tags --url="https://sonar.exemple.com" --token="token sonarQube"
 *   php bin/console app:sonar:update-tags --url=... --token=... --dry-run
 */
#[AsCommand(
    name: 'app:sonar:update-tags',
    description: 'Met à jour les tags SonarQube de tous les projets selon leur groupe de permissions.',
)]
class UpdateSonarqubeTagsCommand extends Command
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
     * Created at: 24/05/2026 10:04:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function configure(): void
    {
        $this
            ->addOption('url',      null, InputOption::VALUE_REQUIRED, 'URL du serveur SonarQube (obligatoire).')
            ->addOption('token',    null, InputOption::VALUE_OPTIONAL, "Token d'accès SonarQube.")
            ->addOption('login',    null, InputOption::VALUE_OPTIONAL, 'Compte utilisateur (alternative au token).')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Mot de passe (alternative au token).')
            ->addOption('dry-run',  null, InputOption::VALUE_NONE,     'Simulation sans modification réelle.')
            ->addOption('debug',    null, InputOption::VALUE_NONE,     'Affiche les détails des appels API.');
    }

    /**
     * [Description for execute]
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * Created at: 24/05/2026 10:04:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sonarUrl = (string) ($input->getOption('url') ?? '');
        $token    = (string) ($input->getOption('token') ?? '');
        $login    = (string) ($input->getOption('login') ?? '');
        $password = (string) ($input->getOption('password') ?? '');
        $dryRun   = (bool) $input->getOption('dry-run');
        $debug    = (bool) $input->getOption('debug');

        if ($sonarUrl === '') {
            $io->error('L\'option --url est obligatoire.');
            return Command::FAILURE;
        }
        if ($token === '' && ($login === '' || $password === '')) {
            $io->error('Il faut soit --token, soit --login et --password.');
            return Command::FAILURE;
        }

        $sonarUrl = rtrim($sonarUrl, '/');
        $auth     = $token !== '' ? $token . ':' : $login . ':' . $password;

        $sslConfig   = $this->buildSslConfig();
        $proxyConfig = $this->buildProxyConfig();

        if ($dryRun) {
            $io->note('Mode simulation activé (aucune modification ne sera envoyée).');
        }
        $this->logger->info('[SonarTags] Connexion.', ['url' => $sonarUrl, 'dry_run' => $dryRun]);

        try {
            $projects = $this->getAllProjects($sonarUrl, $auth, $sslConfig, $proxyConfig, $debug, $io);
        } catch (\Throwable $e) {
            $io->error('Impossible de récupérer les projets : ' . $e->getMessage());
            $this->logger->error('[SonarTags] Erreur récupération projets.', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }

        $io->text(sprintf('Projets récupérés : %d', count($projects)));
        $this->logger->info('[SonarTags] Projets récupérés.', ['count' => count($projects)]);

        $archive   = 0;
        $proceeded = 0;
        $warning   = 0;
        $mapping   = [];

        foreach ($projects as $key) {
            try {
                $this->setProjectTag($sonarUrl, $auth, $sslConfig, $proxyConfig, $key, 'aucun', $dryRun, $debug, $io);

                if ($this->isArchivedProject($sonarUrl, $auth, $sslConfig, $proxyConfig, $key, $debug, $io)) {
                    $groupTag = 'archive';
                    $archive++;
                } else {
                    $groupData = $this->getProjectGroup($sonarUrl, $auth, $sslConfig, $proxyConfig, $key, $debug, $io);
                    if ($groupData !== null) {
                        $groupTag = $this->sanitizeTag($groupData['name']);
                        $proceeded++;
                    } else {
                        $groupTag = 'aucun';
                        $io->warning(sprintf('Projet %s : aucun groupe valide, tag "aucun" conservé.', $key));
                        $this->logger->warning('[SonarTags] Projet sans groupe valide.', ['key' => $key]);
                        $warning++;
                    }
                }

                $this->setProjectTag($sonarUrl, $auth, $sslConfig, $proxyConfig, $key, $groupTag, $dryRun, $debug, $io);
                $mapping[] = ['maven_key' => $key, 'groupe' => $groupTag];

            } catch (\Throwable $e) {
                $io->error(sprintf('Erreur sur %s : %s', $key, $e->getMessage()));
                $this->logger->error('[SonarTags] Erreur traitement projet.', [
                    'key' => $key, 'error' => $e->getMessage(),
                ]);
            }
        }

        /* MODIF 2026-05-24 : chemin var/ + méthode protégée mockable. */
        $mappingFile = 'var/mapping_projets_groupes.json';
        $this->writeMappingFile($mappingFile, $mapping);

        $io->success(sprintf(
            'Traitement terminé. Archivés: %d | Tagués: %d | Sans tag: %d | Mapping: %s',
            $archive, $proceeded, $warning, $mappingFile
        ));
        $this->logger->info('[SonarTags] Terminé.', [
            'archive' => $archive, 'proceeded' => $proceeded, 'warning' => $warning,
        ]);

        return Command::SUCCESS;
    }

    /**
     * [Description for buildSslConfig]
     *
     * @return array{verify_peer: int, verify_host: int, ciphers: string}
     *
     * Created at: 24/05/2026 10:04:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function buildSslConfig(): array
    {
        return [
            'verify_peer' => (int) $this->mamoulCliVerifyPeer ?: 0,
            'verify_host' => (int) $this->mamoulCliVerifyHost ?: 0,
            'ciphers'     => (string) $this->mamoulCliCiphers ?: 'DEFAULT:!DH'
        ];
    }

    /**
     * [Description for buildProxyConfig]
     *
     * @return array{use_proxy: int, proxy_url: string, proxy_userpwd: string, no_proxy: string[]}
     *
     * Created at: 24/05/2026 10:04:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function buildProxyConfig(): array
    {
        $noProxyEnv = $this->mamoulCliNoProxy ?: false;
        $noProxy = $noProxyEnv === false || trim($noProxyEnv) === ''
            ? ['localhost', '127.0.0.1']
            : array_values(array_filter(array_map('trim', explode(',', $noProxyEnv))));
        return [
            'use_proxy'    => $this->mamoulCliUseProxy ?: 0,
            'proxy_url'    => $this->mamoulCliProxyUrl ?: 'https://proxy.example.com:8080',
            'proxy_userpwd'=> $this->mamoulCliProxyUserPwd ?: 'utilisateur:motdepasse',
            'no_proxy'     => $noProxy,
        ];
    }

    /**
     * [Description for sanitizeTag]
     *
     * @param string $tag
     *
     * @return string
     *
     * Created at: 24/05/2026 10:06:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function sanitizeTag(string $tag): string
    {
        $tag = strtolower($tag);
        $tag = preg_replace('/[^a-z0-9]/', '-', $tag) ?? '';
        return $tag !== '' ? $tag : 'aucun';
    }

    /**
     * [Description for callApi]
     * Appel cURL vers l'API SonarQube.
     * Protected pour permettre le mock dans les tests.
     *
     * @param string $sonarUrl
     * @param string $auth
     * @param array<string, mixed> $sslConfig
     * @param array<string, mixed> $proxyConfig
     * @param array<string, mixed> $params
     * @param string $endpoint
     * @param string $method
     * @param bool $debug
     * @param SymfonyStyle $io
     *
     * @return mixed
     * @throws SonarCommandException sur erreur cURL ou HTTP hors 2xx
     *
     * Created at: 24/05/2026 10:06:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function callApi(
        string $sonarUrl,
        string $auth,
        array $sslConfig,
        array $proxyConfig,
        string $endpoint,
        array $params,
        string $method,
        bool $debug,
        SymfonyStyle $io
    ): mixed {
        $url = $sonarUrl . $endpoint;
        $ch  = curl_init();

        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $auth,
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_SSL_VERIFYHOST => $sslConfig['verify_host'],
            CURLOPT_SSL_VERIFYPEER => $sslConfig['verify_peer'],
            CURLOPT_SSL_CIPHER_LIST=> $sslConfig['ciphers'],
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT        => 90,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        if ($proxyConfig['use_proxy'] === 1 && $proxyConfig['proxy_url'] !== '') {
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
            throw new SonarCommandException('Erreur cURL : ' . $error);
        }
        curl_close($ch);

        if ($status === 401) {
            throw new SonarCommandException(sprintf('Erreur 401 : accès non autorisé à %s', $url));
        }
        if ($status < 200 || $status >= 300) {
            throw new SonarCommandException(sprintf('Erreur API %d sur %s : %s', $status, $url, (string) $response));
        }

        if ($debug) {
            $io->text(sprintf('[DEBUG] %s %s → %d', $method, $url, $status));
        }

        $json = json_decode((string) $response, true);
        return $json !== null ? $json : $response;
    }

    /**
     * @param array<string, mixed> $sslConfig
     * @param array<string, mixed> $proxyConfig
     * @return string[]
     */
    private function getAllProjects(
        string $sonarUrl,
        string $auth,
        array $sslConfig,
        array $proxyConfig,
        bool $debug,
        SymfonyStyle $io
    ): array {
        $projects = [];
        $page = 1;
        while (true) {
            $data = $this->callApi($sonarUrl, $auth, $sslConfig, $proxyConfig,
                '/api/components/search_projects', ['p' => $page, 'ps' => 100], 'GET', $debug, $io);
            if (!is_array($data) || !isset($data['components'])) {
                break;
            }
            foreach ($data['components'] as $comp) {
                $projects[] = (string) $comp['key'];
            }
            if (count($data['components']) < 100) {
                break;
            }
            $page++;
        }
        return $projects;
    }

    /**
     * [Description for setProjectTag]
     *
     * @param string $sonarUrl
     * @param string $auth
     * @param array<string, mixed> $sslConfig
     * @param array<string, mixed> $proxyConfig
     * @param string $projectKey
     * @param string $tagValue
     * @param bool $dryRun
     * @param bool $debug
     * @param SymfonyStyle $io
     *
     * @return void
     *
     * Created at: 24/05/2026 10:08:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function setProjectTag(
        string $sonarUrl,
        string $auth,
        array $sslConfig,
        array $proxyConfig,
        string $projectKey,
        string $tagValue,
        bool $dryRun,
        bool $debug,
        SymfonyStyle $io
    ): void {
        $tagValue = $this->sanitizeTag($tagValue);
        if ($dryRun) {
            $io->text(sprintf('  [DRY-RUN] Tag simulé : "%s" → %s', $tagValue, $projectKey));
            return;
        }
        $this->callApi($sonarUrl, $auth, $sslConfig, $proxyConfig,
            '/api/project_tags/set', ['project' => $projectKey, 'tags' => $tagValue], 'POST', $debug, $io);
    }

    /**
     * [Description for getProjectGroup]
     *
     * @param string $sonarUrl
     * @param string $auth
     * @param array<string, mixed> $sslConfig
     * @param array<string, mixed> $proxyConfig
     * @param string $projectKey
     * @param bool $debug
     * @param SymfonyStyle $io
     *
     * @return array{name: string, permissions: string[]}|null
     *
     * Created at: 24/05/2026 10:08:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getProjectGroup(
        string $sonarUrl,
        string $auth,
        array $sslConfig,
        array $proxyConfig,
        string $projectKey,
        bool $debug,
        SymfonyStyle $io
    ): ?array {
        $data = $this->callApi($sonarUrl, $auth, $sslConfig, $proxyConfig,
            '/api/permissions/groups', ['projectKey' => $projectKey], 'GET', $debug, $io);
        if (!is_array($data) || !isset($data['groups'])) {
            return null;
        }
        foreach ($data['groups'] as $g) {
            $permissions = $g['permissions'] ?? [];
            $desc        = $g['description'] ?? '';
            if (!empty($permissions)
                && count(array_diff($permissions, ['codeviewer', 'securityhotspotadmin', 'user'])) === 0
                && str_contains($desc, '2021')) {
                return ['name' => (string) $g['name'], 'permissions' => (array) $permissions];
            }
        }
        return null;
    }

    /**
     * [Description for isArchivedProject]
     *
     * @param string $sonarUrl
     * @param string $auth
     * @param array<string, mixed> $sslConfig
     * @param array<string, mixed> $proxyConfig
     * @param string $projectKey
     * @param bool $debug
     * @param SymfonyStyle $io
     *
     * @return bool
     *
     * Created at: 24/05/2026 10:11:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    /**
     * Écriture du fichier de mapping — méthode protégée pour permettre le mock en tests.
     *
     * @param array<int, array<string, string>> $mapping
     */
    protected function writeMappingFile(string $path, array $mapping): void
    {
        file_put_contents($path, json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function isArchivedProject(
        string $sonarUrl,
        string $auth,
        array $sslConfig,
        array $proxyConfig,
        string $projectKey,
        bool $debug,
        SymfonyStyle $io
    ): bool {
        $data   = $this->callApi($sonarUrl, $auth, $sslConfig, $proxyConfig,
            '/api/permissions/groups', ['projectKey' => $projectKey], 'GET', $debug, $io);
        $groups = is_array($data) ? ($data['groups'] ?? []) : [];
        foreach ($groups as $g) {
            if ($g['name'] === 'Archive' && !empty($g['permissions'])) {
                return true;
            }
        }
        return false;
    }
}
