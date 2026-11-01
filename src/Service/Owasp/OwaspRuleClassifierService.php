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

declare(strict_types=1);

namespace App\Service\Owasp;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * [Description OwaspRuleClassifierService]
 * 
 * MODIF 27/10/2026 : nouveau service, remplace la boucle par catégorie
 * (owaspTop10=aX) + le secours par tag (owasp-aXX) de BatchCollecteOwaspController 
 * pour les VULNÉRABILITÉS uniquement
 */
class OwaspRuleClassifierService
{
    private static string $beginRegEx = '/\b(?:';
    private static string $endRegEx = ')\b/i';

    public function __construct(
        private readonly ParameterBagInterface $params,
    ) {
    }

    /**
     * CORE_RULE_CATEGORIES
     *
    * Règles SonarSource (repo `java`) classées OWASP par SonarQube lui-même (bloc structuré `securityStandards`, lu directement dans le JSON de la règle). 65 règles.
    *
    * @var array<string, array{owasp2017: string[], owasp2021: string[]}>
     */
    /**
     * CORE_RULE_CATEGORIES
     *
     * @var mixed
     */
    private const CORE_RULE_CATEGORIES = [
        'java:S106' => ['owasp2017' => ['a3'], 'owasp2021' => ['a9']],
        'java:S1166' => ['owasp2017' => ['a10'], 'owasp2021' => ['a9']],
        'java:S1313' => ['owasp2017' => ['a3'], 'owasp2021' => ['a1']],
        'java:S1989' => ['owasp2017' => ['a3'], 'owasp2021' => []],
        'java:S2053' => ['owasp2017' => ['a3'], 'owasp2021' => ['a2']],
        'java:S2068' => ['owasp2017' => ['a2'], 'owasp2021' => ['a7']],
        'java:S2077' => ['owasp2017' => ['a1'], 'owasp2021' => ['a3']],
        'java:S2092' => ['owasp2017' => ['a3'], 'owasp2021' => ['a4', 'a5']],
        'java:S2115' => ['owasp2017' => ['a2', 'a3'], 'owasp2021' => ['a7']],
        'java:S2119' => ['owasp2017' => ['a6'], 'owasp2021' => []],
        'java:S2245' => ['owasp2017' => ['a3'], 'owasp2021' => ['a2']],
        'java:S2254' => ['owasp2017' => ['a2'], 'owasp2021' => ['a4']],
        'java:S2257' => ['owasp2017' => ['a3'], 'owasp2021' => ['a2']],
        'java:S2441' => ['owasp2017' => [], 'owasp2021' => ['a4']],
        'java:S2612' => ['owasp2017' => ['a5'], 'owasp2021' => ['a1', 'a4']],
        'java:S2647' => ['owasp2017' => ['a3'], 'owasp2021' => ['a4']],
        'java:S3329' => ['owasp2017' => ['a6', 'a3'], 'owasp2021' => ['a2']],
        'java:S3330' => ['owasp2017' => ['a7'], 'owasp2021' => ['a5']],
        'java:S3751' => ['owasp2017' => ['a6'], 'owasp2021' => []],
        'java:S3752' => ['owasp2017' => ['a5'], 'owasp2021' => ['a1', 'a4']],
        'java:S4036' => ['owasp2017' => ['a1'], 'owasp2021' => ['a8']],
        'java:S4347' => ['owasp2017' => ['a6'], 'owasp2021' => ['a2']],
        'java:S4423' => ['owasp2017' => ['a3', 'a6'], 'owasp2021' => ['a2', 'a7']],
        'java:S4426' => ['owasp2017' => ['a3', 'a6'], 'owasp2021' => ['a2']],
        'java:S4433' => ['owasp2017' => ['a2'], 'owasp2021' => ['a7']],
        'java:S4434' => ['owasp2017' => ['a8'], 'owasp2021' => ['a8']],
        'java:S4502' => ['owasp2017' => ['a6'], 'owasp2021' => ['a1']],
        'java:S4507' => ['owasp2017' => ['a3'], 'owasp2021' => ['a5']],
        'java:S4512' => ['owasp2017' => ['a1'], 'owasp2021' => ['a3', 'a8']],
        'java:S4544' => ['owasp2017' => ['a8'], 'owasp2021' => ['a8']],
        'java:S4601' => ['owasp2017' => ['a6'], 'owasp2021' => ['a1']],
        'java:S4684' => ['owasp2017' => ['a5'], 'owasp2021' => ['a8']],
        'java:S4790' => ['owasp2017' => ['a3', 'a6'], 'owasp2021' => ['a2']],
        'java:S4830' => ['owasp2017' => ['a6', 'a3'], 'owasp2021' => ['a2', 'a5', 'a7']],
        'java:S5042' => ['owasp2017' => ['a5', 'a6'], 'owasp2021' => ['a1', 'a5']],
        'java:S5122' => ['owasp2017' => ['a6'], 'owasp2021' => ['a5', 'a7']],
        'java:S5247' => ['owasp2017' => ['a7'], 'owasp2021' => ['a3']],
        'java:S5301' => ['owasp2017' => ['a8'], 'owasp2021' => ['a8']],
        'java:S5320' => ['owasp2017' => [], 'owasp2021' => ['a4']],
        'java:S5324' => ['owasp2017' => [], 'owasp2021' => ['a4']],
        'java:S5332' => ['owasp2017' => ['a3'], 'owasp2021' => ['a2']],
        'java:S5344' => ['owasp2017' => ['a3'], 'owasp2021' => ['a2', 'a4']],
        'java:S5443' => ['owasp2017' => ['a5', 'a3'], 'owasp2021' => ['a1']],
        'java:S5445' => ['owasp2017' => [], 'owasp2021' => ['a1']],
        'java:S5527' => ['owasp2017' => ['a3', 'a6'], 'owasp2021' => ['a2', 'a5', 'a7']],
        'java:S5542' => ['owasp2017' => ['a6', 'a3'], 'owasp2021' => ['a2']],
        'java:S5547' => ['owasp2017' => ['a3', 'a6'], 'owasp2021' => ['a2']],
        'java:S5659' => ['owasp2017' => ['a3'], 'owasp2021' => ['a2']],
        'java:S5679' => ['owasp2017' => ['a2'], 'owasp2021' => ['a7']],
        'java:S5689' => ['owasp2017' => ['a6'], 'owasp2021' => ['a5']],
        'java:S5693' => ['owasp2017' => ['a6'], 'owasp2021' => ['a5']],
        'java:S5804' => ['owasp2017' => ['a2'], 'owasp2021' => ['a1']],
        'java:S5808' => ['owasp2017' => ['a5'], 'owasp2021' => ['a1']],
        'java:S5852' => ['owasp2017' => [], 'owasp2021' => ['a4']],
        'java:S5876' => ['owasp2017' => ['a2'], 'owasp2021' => ['a7']],
        'java:S6288' => ['owasp2017' => [], 'owasp2021' => ['a4']],
        'java:S6293' => ['owasp2017' => [], 'owasp2021' => ['a7']],
        'java:S6301' => ['owasp2017' => ['a3', 'a6'], 'owasp2021' => ['a2', 'a4']],
        'java:S6362' => ['owasp2017' => ['a6', 'a7'], 'owasp2021' => ['a3']],
        'java:S6363' => ['owasp2017' => ['a3', 'a6'], 'owasp2021' => ['a1']],
        'java:S6418' => ['owasp2017' => ['a2'], 'owasp2021' => ['a7']],
        'java:S6432' => ['owasp2017' => ['a3'], 'owasp2021' => ['a2']],
        'java:S6437' => ['owasp2017' => ['a2'], 'owasp2021' => ['a7']],
        'java:S7409' => ['owasp2017' => [], 'owasp2021' => ['a5']],
        'java:S9146' => ['owasp2017' => [], 'owasp2021' => ['a8']],
    ];

    /**
     * FINDSECBUGS_RULE_CATEGORIES_2017
     *
    * Règles FindSecBugs (repo `findsecbugs`) classées uniquement via leur tag `owasp-aX` (OWASP 2013/2017, jamais 2021). 61 règles.
    *
    * @var array<string, string[]>
    */
    private const FINDSECBUGS_RULE_CATEGORIES_2017 = [
        'findsecbugs:ANDROID_WEB_VIEW_JAVASCRIPT' => ['a3'],
        'findsecbugs:AWS_QUERY_INJECTION' => ['a1'],
        'findsecbugs:BLOWFISH_KEY_SIZE' => ['a6'],
        'findsecbugs:CIPHER_INTEGRITY' => ['a6'],
        'findsecbugs:COMMAND_INJECTION' => ['a1'],
        'findsecbugs:CRLF_INJECTION_LOGS' => ['a1'],
        'findsecbugs:CUSTOM_MESSAGE_DIGEST' => ['a6'],
        'findsecbugs:DEFAULT_HTTP_CLIENT' => ['a6'],
        'findsecbugs:DES_USAGE' => ['a6'],
        'findsecbugs:ECB_MODE' => ['a6'],
        'findsecbugs:EL_INJECTION' => ['a1'],
        'findsecbugs:FILE_UPLOAD_FILENAME' => ['a4'],
        'findsecbugs:GROOVY_SHELL' => ['a1'],
        'findsecbugs:HARD_CODE_PASSWORD' => ['a6'],
        'findsecbugs:HAZELCAST_SYMMETRIC_ENCRYPTION' => ['a6'],
        'findsecbugs:HTTP_RESPONSE_SPLITTING' => ['a1'],
        'findsecbugs:INFORMATION_EXPOSURE_THROUGH_AN_ERROR_MESSAGE' => ['a1'],
        'findsecbugs:INSECURE_SMTP_SSL' => ['a6'],
        'findsecbugs:LDAP_ENTRY_POISONING' => ['a1'],
        'findsecbugs:LDAP_INJECTION' => ['a1'],
        'findsecbugs:MALICIOUS_XSLT' => ['a1', 'a4'],
        'findsecbugs:NULL_CIPHER' => ['a6'],
        'findsecbugs:OGNL_INJECTION' => ['a1'],
        'findsecbugs:PADDING_ORACLE' => ['a6'],
        'findsecbugs:PATH_TRAVERSAL_IN' => ['a4'],
        'findsecbugs:PATH_TRAVERSAL_OUT' => ['a4'],
        'findsecbugs:POTENTIAL_XML_INJECTION' => ['a1'],
        'findsecbugs:RSA_KEY_SIZE' => ['a6'],
        'findsecbugs:RSA_NO_PADDING' => ['a6'],
        'findsecbugs:SCRIPT_ENGINE_INJECTION' => ['a1'],
        'findsecbugs:SEAM_LOG_INJECTION' => ['a1'],
        'findsecbugs:SERVLET_PARAMETER' => ['a1'],
        'findsecbugs:SMTP_HEADER_INJECTION' => ['a1'],
        'findsecbugs:SPEL_INJECTION' => ['a1'],
        'findsecbugs:SQL_INJECTION' => ['a1'],
        'findsecbugs:SQL_INJECTION_ANDROID' => ['a1'],
        'findsecbugs:SQL_INJECTION_HIBERNATE' => ['a1'],
        'findsecbugs:SQL_INJECTION_JDBC' => ['a1'],
        'findsecbugs:SQL_INJECTION_JDO' => ['a1'],
        'findsecbugs:SQL_INJECTION_JPA' => ['a1'],
        'findsecbugs:SQL_INJECTION_SPRING_JDBC' => ['a1'],
        'findsecbugs:SQL_INJECTION_TURBINE' => ['a1'],
        'findsecbugs:SQL_INJECTION_VERTX' => ['a1'],
        'findsecbugs:SSL_CONTEXT' => ['a6'],
        'findsecbugs:STATIC_IV' => ['a6'],
        'findsecbugs:TDES_USAGE' => ['a6'],
        'findsecbugs:TEMPLATE_INJECTION_FREEMARKER' => ['a1'],
        'findsecbugs:TEMPLATE_INJECTION_PEBBLE' => ['a1'],
        'findsecbugs:TEMPLATE_INJECTION_VELOCITY' => ['a1'],
        'findsecbugs:UNENCRYPTED_SERVER_SOCKET' => ['a6'],
        'findsecbugs:UNENCRYPTED_SOCKET' => ['a6'],
        'findsecbugs:UNSAFE_HASH_EQUALS' => ['a6'],
        'findsecbugs:WEAK_FILENAMEUTILS' => ['a1'],
        'findsecbugs:WEAK_HOSTNAME_VERIFIER' => ['a6'],
        'findsecbugs:WEAK_MESSAGE_DIGEST_MD5' => ['a6'],
        'findsecbugs:WEAK_MESSAGE_DIGEST_SHA1' => ['a6'],
        'findsecbugs:WEAK_TRUST_MANAGER' => ['a6'],
        'findsecbugs:WICKET_XSS1' => ['a3'],
        'findsecbugs:XPATH_INJECTION' => ['a1'],
        'findsecbugs:XSS_REQUEST_WRAPPER' => ['a3'],
        'findsecbugs:XSS_SERVLET' => ['a3'],
    ];

    /**
     * [Description for allRuleKeys]
     * Toutes les clés de règle couvertes (core + findsecbugs), pour construire le paramètre `rules=` de l'appel unique `/api/issues/search`.
     *
     * @return string[]
     *
     * Created at: 28/10/2026 07:58:42 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function allRuleKeys(): array
    {
        return array_merge(
            array_keys(self::CORE_RULE_CATEGORIES),
            array_keys(self::FINDSECBUGS_RULE_CATEGORIES_2017)
        );
    }

    /**
     * [Description for ruleCount]
     * Nombre de règles locales couvrant ce référentiel (2017 ou 2021) — donnée statique (ne dépend pas d'un projet collecté), utilisée pour le callout d'information de la page OWASP.
     *
     * @param int $referential
     *
     * @return int
     *
     * Created at: 28/10/2026 07:59:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function ruleCount(int $referential): int
    {
        if ($referential === 2021) {
            return count(array_filter(
                self::CORE_RULE_CATEGORIES,
                static fn(array $rule): bool => $rule['owasp2021'] !== []
            ));
        }

        $core2017 = count(array_filter(
            self::CORE_RULE_CATEGORIES,
            static fn(array $rule): bool => $rule['owasp2017'] !== []
        ));

        return $core2017 + count(self::FINDSECBUGS_RULE_CATEGORIES_2017);
    }

    /**
     * [Description for classify]
     * Classe une liste brute d'issues SonarQube (`rule`, `component`, `line`, `severity`, `message`) par référentiel/catégorie/sévérité/module, avec la `source` de classification par catégorie (`core`, `findsecbugs` ou `mixed`).
     *
     * MODIF 28/10/2026 : construit en plus, dans la même boucle (pas de second
     * passage sur $issues), le détail individuel de chaque issue classée
     * (clé `details`) — nécessaire pour le tableau de détail des
     * vulnérabilités OWASP (`owasp_details`), qui n'avait jusqu'ici aucune
     * trace individuelle, seulement les compteurs agrégés par catégorie.
     *
     * @param array<int, array{rule: string, component: string, severity: string, line: ?int, message: string}> $issues
     *
     * @return array{
     *     2017: array<string, array{total: int, blocker: int, critical: int, major: int, minor: int, info: int, source: string}>,
     *     2021: array<string, array{total: int, blocker: int, critical: int, major: int, minor: int, info: int, source: string}>,
     *     module: array{2017: array{frontend: int, backend: int, autre: int}, 2021: array{frontend: int, backend: int, autre: int}},
     *     details: array{
     *         2017: array<int, array{rule: string, categories: string, severity: string, source: string, component: string, module: string, line: ?int, message: string}>,
     *         2021: array<int, array{rule: string, categories: string, severity: string, source: string, component: string, module: string, line: ?int, message: string}>
     *     }
     * }
     *
     * Created at: 28/10/2026 07:59:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function classify(array $issues): array
    {
        $severityKey = [
            'BLOCKER' => 'blocker',
            'CRITICAL' => 'critical',
            'MAJOR' => 'major',
            'MINOR' => 'minor',
            'INFO' => 'info',
        ];

        $categories = [2017 => [], 2021 => []];
        $categorySources = [2017 => [], 2021 => []];
        $details = [2017 => [], 2021 => []];
        /* MODIF 28/10/2026 : BUG CORRIGÉ — la répartition par module était comptée
         * UNE SEULE FOIS globalement (toute issue matchant au moins un référentiel),
         * puis cette même valeur globale était stockée telle quelle sur la ligne
         * `owasp` de 2017 ET sur celle de 2021 (BatchCollecteOwaspController::$buildRow).
         * Conséquence constatée en conditions réelles : sur un projet où FindSecBugs
         * classe 541 issues en 2017 mais seules 83 règles core matchent 2021, le PDF/la
         * page web du référentiel 2021 affichait "Répartition par module" sommant à 541
         * au lieu de 83 — aucun rapport avec le total affiché juste au-dessus pour ce
         * référentiel. Compté désormais par référentiel, uniquement pour les issues qui
         * matchent CE référentiel précis. */
        $module = [
            2017 => ['frontend' => 0, 'backend' => 0, 'autre' => 0],
            2021 => ['frontend' => 0, 'backend' => 0, 'autre' => 0],
        ];
        $regexModules = $this->buildRegexModules();

        foreach ($issues as $issue) {
            $severity = $severityKey[$issue['severity']] ?? null;
            if ($severity === null) {
                continue;
            }

            $matches = $this->categoriesForRule($issue['rule']);
            if ($matches[2017] === [] && $matches[2021] === []) {
                continue;
            }

            $moduleResult = $this->classifieModule($issue['component'], $regexModules);

            foreach ([2017, 2021] as $referential) {
                if ($matches[$referential] === []) {
                    continue;
                }

                match ($moduleResult) {
                    'frontend' => $module[$referential]['frontend']++,
                    'backend' => $module[$referential]['backend']++,
                    default => $module[$referential]['autre']++,
                };

                foreach ($matches[$referential] as $category) {
                    $categories[$referential][$category] ??= [
                        'total' => 0, 'blocker' => 0, 'critical' => 0, 'major' => 0, 'minor' => 0, 'info' => 0, 'source' => '',
                    ];
                    $categories[$referential][$category]['total']++;
                    $categories[$referential][$category][$severity]++;
                    $categorySources[$referential][$category][$matches['origin']] = true;
                }

                $details[$referential][] = [
                    'rule' => $issue['rule'],
                    'categories' => implode(', ', array_map('strtoupper', $matches[$referential])),
                    'severity' => $issue['severity'],
                    'source' => $matches['origin'],
                    'component' => $issue['component'],
                    'module' => $moduleResult,
                    'line' => $issue['line'],
                    'message' => $issue['message'],
                ];
            }
        }

        foreach ([2017, 2021] as $referential) {
            foreach ($categories[$referential] as $category => $data) {
                $origins = array_keys($categorySources[$referential][$category] ?? []);
                $categories[$referential][$category]['source'] = match (true) {
                    count($origins) > 1 => 'mixed',
                    $origins === ['findsecbugs'] => 'findsecbugs',
                    default => 'core',
                };
            }
        }

        return [
            2017 => $categories[2017],
            2021 => $categories[2021],
            'module' => $module,
            'details' => [2017 => $details[2017], 2021 => $details[2021]],
        ];
    }

    /**
     * [Description for categoriesForRule]
     *
     * @param string $ruleKey
     *
     * @return array{2017: string[], 2021: string[], origin: string}
     *
     * Created at: 28/10/2026 08:00:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function categoriesForRule(string $ruleKey): array
    {
        if (isset(self::CORE_RULE_CATEGORIES[$ruleKey])) {
            $rule = self::CORE_RULE_CATEGORIES[$ruleKey];
            return [2017 => $rule['owasp2017'], 2021 => $rule['owasp2021'], 'origin' => 'core'];
        }

        if (isset(self::FINDSECBUGS_RULE_CATEGORIES_2017[$ruleKey])) {
            return [2017 => self::FINDSECBUGS_RULE_CATEGORIES_2017[$ruleKey], 2021 => [], 'origin' => 'findsecbugs'];
        }

        return [2017 => [], 2021 => [], 'origin' => ''];
    }

    /**
     * [Description for buildRegexModules]
     * Construit les 3 regex de classification par module, à partir des mêmes paramètres (`module.frontend`/`module.backend`/`module.autre`) que BuildMeasuresRciService::buildRegexModules().
     *
     * @return array{0: string, 1: string, 2: string}
     *
     * Created at: 28/10/2026 08:00:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function buildRegexModules(): array
    {
        $frontendKeywords = array_map('strtolower', array_map('trim', explode(',', (string) $this->params->get('module.frontend'))));
        $backendKeywords = array_map('strtolower', array_map('trim', explode(',', (string) $this->params->get('module.backend'))));
        $autreKeywords = array_map('strtolower', array_map('trim', explode(',', (string) $this->params->get('module.autre'))));

        return [
            self::$beginRegEx . implode('|', array_map('preg_quote', $frontendKeywords)) . self::$endRegEx,
            self::$beginRegEx . implode('|', array_map('preg_quote', $backendKeywords)) . self::$endRegEx,
            self::$beginRegEx . implode('|', array_map('preg_quote', $autreKeywords)) . self::$endRegEx,
        ];
    }

    /**
     * [Description for classifieModule]
     * Classe un chemin de fichier dans frontend/backend/autre/inconnu selon les 3 regex fournies — même logique que BuildMeasuresRciService::classifieModule().
     *
     * @param array{0: string, 1: string, 2: string} $regexModules
     *
     * @return string
     *
     * Created at: 28/10/2026 08:00:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function classifieModule(string $path, array $regexModules): string
    {
        [$regexFrontend, $regexBackend, $regexAutre] = $regexModules;

        if (preg_match($regexFrontend, $path)) {
            return 'frontend';
        }
        if (preg_match($regexBackend, $path)) {
            return 'backend';
        }
        if (preg_match($regexAutre, $path)) {
            return 'autre';
        }

        return 'inconnu';
    }
}
