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
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * MODIF 2026-05-21 : conversion du script CLI
 * `update_sonarqube_tags_cli.php` (v1.1.0) en Symfony Command.
 *
 * Met à jour les tags SonarQube des projets appartenant au périmètre de
 * groupes défini par sonar_tags.target_groups (SONAR_TAGS_TARGET_GROUPS,
 * .env.local) :
 *   1. Récupère la liste complète des projets via /api/components/search_projects
 *   2. Détecte le groupe du projet par nom direct parmi sonar_tags.target_groups.
 *      Si target_groups N'EST PAS vide, un projet dont aucun groupe ne figure
 *      dans la liste est ignoré sans modification ("Ignorés"). Si target_groups
 *      EST vide, aucun projet ne peut jamais matcher (liste vide) : chaque
 *      projet non archivé reçoit le tag "aucun" avec un avertissement
 *      ("Sans tag"), rien n'est ignoré silencieusement.
 *   3. Efface les tags existants (setTag="aucun")
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
    /** @var string[] */
    private readonly array $sonarTagsTargetGroups;

    /**
     * @param string[] $sonarTagsTargetGroups
     * @param string[] $sonarTagsBusinessTagFileCandidates
     */
    public function __construct(
        /* MODIF 2026-10-15 : log dédié aux commandes de la crontab Docker
         * (cf. config/packages/monolog.yaml, channel "cron"). */
        #[Autowire(service: 'monolog.logger.cron')]
        private readonly LoggerInterface $logger,
        private readonly int $mamoulCliVerifyPeer,
        private readonly int $mamoulCliVerifyHost,
        private readonly string $mamoulCliCiphers,
        private readonly int $mamoulCliUseProxy,
        private readonly string $mamoulCliProxyUrl,
        private readonly string $mamoulCliProxyUserPwd,
        private readonly string $mamoulCliNoProxy,
        #[Autowire(param: 'sonar_tags.target_groups')]
        array $sonarTagsTargetGroups = [],
        #[Autowire(param: 'sonar_tags.business_tag_file_candidates')]
        private readonly array $sonarTagsBusinessTagFileCandidates = [],
    ) {
        parent::__construct();
        $this->sonarTagsTargetGroups = array_values(array_unique(array_filter(
            array_map(static fn (string $g): string => trim($g), $sonarTagsTargetGroups),
            static fn (string $g): bool => $g !== ''
        )));
    }

    /**
     * [Description for configure]
     *
     * @return void
     *
     * Created at: 24/05/2026 10:04:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
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
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
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

        if ($this->sonarTagsTargetGroups !== []) {
            $io->note(sprintf(
                'Périmètre restreint à %d groupe(s) : %s. Les autres projets sont ignorés (aucune modification).',
                count($this->sonarTagsTargetGroups),
                implode(', ', $this->sonarTagsTargetGroups)
            ));
        }

        [$businessTags, $businessTagFile] = $this->loadBusinessTagMap();
        if ($businessTagFile !== null) {
            $io->note(sprintf(
                'Tags métier : %d correspondance(s) chargée(s) depuis %s.',
                count($businessTags),
                $businessTagFile
            ));
        } else {
            $io->text('Tags métier : aucun fichier trouvé, étape ignorée.');
        }

        $archive       = 0;
        $proceeded     = 0;
        $warning       = 0;
        $skipped       = 0;
        $businessTagged = 0;
        $mapping       = [];

        foreach ($projects as $key) {
            try {
                /** 
                 * MODIF 2026-11-22 : la détection du groupe (déjà nécessaire pour le
                 * tag) sert aussi de critère de périmètre — un seul appel API
                 * /api/permissions/groups par projet (au lieu de 2 : archive + groupe
                 * interrogeaient déjà la même route séparément). Avec un périmètre
                 * explicite (sonar_tags.target_groups), getProjectGroup() ne peut
                 * renvoyer qu'un groupe de cette liste (ou null) — un $groupName non
                 * null y figure donc toujours forcément. */
                $groups    = $this->fetchProjectGroups($sonarUrl, $auth, $sslConfig, $proxyConfig, $key, $debug, $io);
                $groupData = $this->getProjectGroup($groups);
                $groupName = $groupData['name'] ?? null;

                if ($this->sonarTagsTargetGroups !== [] && $groupName === null) {
                    $skipped++;
                    $this->logger->info('[SonarTags] Projet ignoré (hors périmètre groupes cibles).', [
                        'key' => $key,
                    ]);
                    continue;
                }

                $this->setProjectTag($sonarUrl, $auth, $sslConfig, $proxyConfig, $key, 'aucun', $dryRun, $debug, $io);

                $isArchived = $this->isArchivedProject($groups);
                if ($isArchived) {
                    $groupTag = 'archive';
                } elseif ($groupData !== null) {
                    $groupTag = $this->sanitizeTag($groupData['name']);
                } else {
                    $groupTag = 'aucun';
                    $io->warning(sprintf('Projet %s : aucun groupe valide, tag "aucun" conservé.', $key));
                    $this->logger->warning('[SonarTags] Projet sans groupe valide.', ['key' => $key]);
                }

                $businessTag = $businessTags[$key] ?? null;
                $finalTags   = $businessTag !== null ? [$groupTag, $businessTag] : [$groupTag];

                $this->setProjectTags($sonarUrl, $auth, $sslConfig, $proxyConfig, $key, $finalTags, $dryRun, $debug, $io);

                /**
                 * MODIF 2026-11-22 : compteurs incrémentés seulement APRÈS le succès
                 * de l'appel de tag ci-dessus — pas avant. Un projet dont le clean
                 * "aucun" passe mais dont le retag échoue (403 "Insufficient privileges"
                 * SonarQube, ex. permission manquante) tombait dans le catch ci-dessous
                 * SANS annuler l'incrément déjà fait, et se retrouvait compté "Tagué"
                 * dans le résumé final alors que son tag de groupe n'avait en réalité
                 * jamais été appliqué. */
                if ($isArchived) {
                    $archive++;
                } elseif ($groupData !== null) {
                    $proceeded++;
                } else {
                    $warning++;
                }
                if ($businessTag !== null) {
                    $businessTagged++;
                }

                $mapping[] = ['maven_key' => $key, 'groupe' => $groupTag, 'tag_metier' => $businessTag];

            } catch (\Throwable $e) {
                $io->error(sprintf('Erreur sur %s : %s', $key, $e->getMessage()));
                $this->logger->error('[SonarTags] Erreur traitement projet.', [
                    'key' => $key, 'error' => $e->getMessage(),
                ]);
            }
        }

        /* MODIF 2026-05-24 : chemin var/ + méthode protégée mockable. */
        $mappingFile = 'var/mapping_projets_groupes.json';
        /**
         * MODIF 2026-07-21 : cette écriture était inconditionnelle — le fichier
         * était donc bien réécrit sur disque même en --dry-run, alors que l'option
         * annonce explicitement "aucune modification réelle" (seuls les appels de
         * tag SonarQube étaient jusqu'ici simulés). */
        if ($dryRun) {
            $io->text(sprintf('  [DRY-RUN] Mapping non écrit : %s', $mappingFile));
        } else {
            $this->writeMappingFile($mappingFile, $mapping);
        }

        $io->success(sprintf(
            'Traitement terminé. Archivés: %d | Tagués: %d | Tag métier: %d | Sans tag: %d | Ignorés: %d | Mapping: %s',
            $archive, $proceeded, $businessTagged, $warning, $skipped, $dryRun ? $mappingFile . ' (non écrit)' : $mappingFile
        ));
        $this->logger->info('[SonarTags] Terminé.', [
            'archive' => $archive, 'proceeded' => $proceeded, 'business_tagged' => $businessTagged,
            'warning' => $warning, 'skipped' => $skipped,
        ]);

        return Command::SUCCESS;
    }

    /**
     * [Description for buildSslConfig]
     *
     * @return array{verify_peer: int, verify_host: int, ciphers: non-empty-string}
     *
     * Created at: 24/05/2026 10:04:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    private function buildSslConfig(): array
    {
        /**
         * MODIF 2026-07-21 : verify_peer/verify_host sont typés int non-nullables
         * (résolus par Symfony via %env(int:MAMOUL_CLI_VERIFY_*)%, qui échoue au
         * démarrage si la variable est absente) — un "?: 0" ici ne pouvait donc
         * jamais retomber sur un défaut sûr, il ne faisait que réécrire la même
         * valeur en donnant l'illusion trompeuse d'un fallback. Voir
         * docker/.env.template pour les valeurs par défaut réellement sûres. */
        return [
            'verify_peer' => $this->mamoulCliVerifyPeer,
            'verify_host' => $this->mamoulCliVerifyHost,
            'ciphers'     => $this->mamoulCliCiphers ?: 'DEFAULT:!DH'
        ];
    }

    /**
     * [Description for buildProxyConfig]
     *
     * @return array{use_proxy: int, proxy_url: string, proxy_userpwd: string, no_proxy: string[]}
     *
     * Created at: 24/05/2026 10:04:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
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
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    private function sanitizeTag(string $tag): string
    {
        $tag = strtolower($tag);
        $tag = preg_replace('/[^a-z0-9]/', '-', $tag) ?? '';
        return $tag !== '' ? $tag : 'aucun';
    }

    /**
     * [Description for loadBusinessTagMap]
     * Phase 3 : charge la correspondance maven_key → tag métier depuis le
     * premier fichier existant de sonar_tags.business_tag_file_candidates.
     * Fichier optionnel : aucun candidat trouvé n'est pas une erreur, le
     * tag métier est alors simplement omis pour tous les projets.
     *
     * Format attendu : tableau JSON d'objets {"project_key": "...",
     * "code_apli": "..."}. Une entrée invalide (champ manquant/vide une fois
     * trimmé) est ignorée silencieusement ; un fichier illisible ou un JSON
     * invalide produit un avertissement loggé et un résultat vide (comme
     * "fichier non trouvé", pas une erreur bloquante pour le reste de la
     * commande).
     *
     * @return array{0: array<string, string>, 1: string|null}
     *
     * Created at: 22/11/2026
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    private function loadBusinessTagMap(): array
    {
        $path = null;
        foreach ($this->sonarTagsBusinessTagFileCandidates as $candidate) {
            if ($candidate !== '' && is_file($candidate)) {
                $path = $candidate;
                break;
            }
        }
        if ($path === null) {
            return [[], null];
        }

        try {
            $content = file_get_contents($path);
            if ($content === false) {
                throw new \RuntimeException('Lecture impossible.');
            }
            $rows = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($rows)) {
                throw new \RuntimeException('Structure JSON inattendue (tableau attendu).');
            }
        } catch (\Throwable $e) {
            $this->logger->warning('[SonarTags] Fichier de tags métier illisible, étape ignorée.', [
                'path' => $path, 'error' => $e->getMessage(),
            ]);
            return [[], null];
        }

        $map = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $mavenKey  = trim((string) ($row['project_key'] ?? ''));
            $codeAppli = trim((string) ($row['code_apli'] ?? ''));
            if ($mavenKey === '' || $codeAppli === '') {
                continue;
            }
            $map[$mavenKey] = $this->sanitizeTag($codeAppli);
        }

        return [$map, $path];
    }

    /**
     * [Description for callApi]
     * Appel cURL vers l'API SonarQube.
     * Protected pour permettre le mock dans les tests.
     *
     * @param string $sonarUrl
     * @param string $auth
     * @param array{verify_peer: int, verify_host: int, ciphers: non-empty-string} $sslConfig
     * @param array{use_proxy: int, proxy_url: string, proxy_userpwd: string, no_proxy: string[]} $proxyConfig
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
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
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
        if ($url === '') {
            throw new \InvalidArgumentException('URL cURL vide (sonarUrl + endpoint).');
        }
        if ($auth === '') {
            throw new \InvalidArgumentException('Identifiants cURL vides.');
        }
        $ch  = curl_init();

        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $auth,
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_SSL_VERIFYHOST => $sslConfig['verify_host'] === 0 ? 0 : 2,
            CURLOPT_SSL_VERIFYPEER => (bool) $sslConfig['verify_peer'],
            CURLOPT_SSL_CIPHER_LIST=> $sslConfig['ciphers'],
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
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
            $proxyUserPwd = $proxyConfig['proxy_userpwd'];
            if ($proxyUserPwd !== '') {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyUserPwd);
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
     * @param array{verify_peer: int, verify_host: int, ciphers: non-empty-string} $sslConfig
     * @param array{use_proxy: int, proxy_url: string, proxy_userpwd: string, no_proxy: string[]} $proxyConfig
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
     * @param array{verify_peer: int, verify_host: int, ciphers: non-empty-string} $sslConfig
     * @param array{use_proxy: int, proxy_url: string, proxy_userpwd: string, no_proxy: string[]} $proxyConfig
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
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
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
        $this->setProjectTags($sonarUrl, $auth, $sslConfig, $proxyConfig, $projectKey, [$tagValue], $dryRun, $debug, $io);
    }

    /**
     * [Description for setProjectTags]
     * Variante de setProjectTag() pour plusieurs tags combinés en un seul
     * appel (ex. tag de groupe + tag métier, phase 3) : l'API SonarQube
     * remplace la liste complète des tags d'un projet à chaque appel, donc
     * chaque tag doit être sanitizé individuellement AVANT d'être joint par
     * une virgule — sanitizer la chaîne déjà jointe transformerait la
     * virgule elle-même en "-" et fusionnerait les tags en un seul.
     *
     * @param array{verify_peer: int, verify_host: int, ciphers: non-empty-string} $sslConfig
     * @param array{use_proxy: int, proxy_url: string, proxy_userpwd: string, no_proxy: string[]} $proxyConfig
     * @param string[] $tags
     *
     * Created at: 22/11/2026
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    private function setProjectTags(
        string $sonarUrl,
        string $auth,
        array $sslConfig,
        array $proxyConfig,
        string $projectKey,
        array $tags,
        bool $dryRun,
        bool $debug,
        SymfonyStyle $io
    ): void {
        $sanitized = array_map(fn (string $tag): string => $this->sanitizeTag($tag), $tags);
        $sanitized = array_values(array_unique(array_filter(
            $sanitized,
            static fn (string $tag): bool => $tag !== ''
        )));
        $tagValue = $sanitized !== [] ? implode(',', $sanitized) : 'aucun';

        if ($dryRun) {
            $io->text(sprintf('  [DRY-RUN] Tag simulé : "%s" → %s', $tagValue, $projectKey));
            return;
        }
        $this->callApi($sonarUrl, $auth, $sslConfig, $proxyConfig,
            '/api/project_tags/set', ['project' => $projectKey, 'tags' => $tagValue], 'POST', $debug, $io);
    }

    /**
     * [Description for fetchProjectGroups]
     * Récupère la liste brute des groupes de permissions d'un projet.
     * Point d'entrée API unique, réutilisé par getProjectGroup() et
     * isArchivedProject() (auparavant 2 appels séparés vers la même route).
     *
     * @param string $sonarUrl
     * @param string $auth
     * @param array{verify_peer: int, verify_host: int, ciphers: non-empty-string} $sslConfig
     * @param array{use_proxy: int, proxy_url: string, proxy_userpwd: string, no_proxy: string[]} $proxyConfig
     * @param string $projectKey
     * @param bool $debug
     * @param SymfonyStyle $io
     *
     * @return array<int, array{name?: string, permissions?: string[], description?: string}>
     *
     * Created at: 22/11/2026
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    private function fetchProjectGroups(
        string $sonarUrl,
        string $auth,
        array $sslConfig,
        array $proxyConfig,
        string $projectKey,
        bool $debug,
        SymfonyStyle $io
    ): array {
        $data = $this->callApi($sonarUrl, $auth, $sslConfig, $proxyConfig,
            '/api/permissions/groups', ['projectKey' => $projectKey], 'GET', $debug, $io);
        return is_array($data) ? (array) ($data['groups'] ?? []) : [];
    }

    /**
     * [Description for getProjectGroup]
     * Détermine le groupe "projet" parmi les groupes déjà récupérés via
     * fetchProjectGroups() : celui dont le nom figure exactement dans
     * `sonar_tags.target_groups` (recherche directe par nom).
     *
     * MODIF 2026-11-22 : l'ancien critère de repli (permissions
     * {codeviewer,securityhotspotadmin,user} + marqueur de description
     * "2021", utilisé pour deviner le "bon" groupe quand aucune liste
     * explicite n'existait) a été retiré — les groupes cibles sont
     * désormais toujours explicites, cette convention de nommage n'a plus
     * d'utilité. Périmètre vide = aucun groupe ne peut jamais matcher, donc
     * toujours null (voir le docblock de la classe pour ce que ça implique
     * côté appelant).
     *
     * BUG CORRIGÉ 2026-11-22 :
     * `/api/permissions/groups` renvoie TOUJOURS tous les groupes de
     * l'instance SonarQube (pas seulement ceux qui ont un droit sur ce
     * projet précis) — `permissions` est un tableau VIDE pour un groupe qui
     * n'a en réalité aucun droit sur ce projet. Sans la vérification
     * `$permissions !== []` ci-dessous, un projet dont le vrai groupe est
     * hors périmètre se faisait rattacher au premier groupe du périmètre 
     * trouvé dans la liste malgré un tableau `permissions` vide pour ce groupe —
     * c'est-à-dire un groupe auquel le projet n'appartient en réalité pas du tout.
     *
     * @param array<int, array{name?: string, permissions?: string[], description?: string}> $groups
     *
     * @return array{name: string, permissions: string[]}|null
     *
     * Created at: 24/05/2026 10:08:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    private function getProjectGroup(array $groups): ?array
    {
        foreach ($groups as $g) {
            $name        = (string) ($g['name'] ?? '');
            $permissions = (array) ($g['permissions'] ?? []);
            if ($name !== '' && $permissions !== [] && in_array($name, $this->sonarTagsTargetGroups, true)) {
                return ['name' => $name, 'permissions' => $permissions];
            }
        }
        return null;
    }

    /**
     * Écriture du fichier de mapping — méthode protégée pour permettre le mock en tests.
     *
     * @param array<int, array<string, string>> $mapping
     */
    protected function writeMappingFile(string $path, array $mapping): void
    {
        file_put_contents($path, json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * [Description for isArchivedProject]
     * Détermine si un projet est archivé parmi les groupes déjà récupérés
     * via fetchProjectGroups().
     *
     * @param array<int, array{name?: string, permissions?: string[], description?: string}> $groups
     *
     * @return bool
     *
     * Created at: 24/05/2026 10:11:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    private function isArchivedProject(array $groups): bool
    {
        foreach ($groups as $g) {
            if (($g['name'] ?? null) === 'Archive' && !empty($g['permissions'])) {
                return true;
            }
        }
        return false;
    }
}
