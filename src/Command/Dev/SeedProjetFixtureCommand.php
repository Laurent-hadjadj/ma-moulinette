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

/* MODIF 2026-05-21 : déplacé dans App\Command\Dev. */
namespace App\Command\Dev;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument,InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * MODIF 2026-05-15 : commande de seed E2E pour
 * vérifier la chaîne complète de collecte → peinture → enregistrement.
 *
 * Crée un projet test `fr.test:projet-fixture` (par défaut, override en arg)
 * et remplit TOUTES les tables de collecte avec des valeurs distinctes,
 * jamais 0 ni NULL. Permet ensuite à l'user de :
 *   1. Lancer la peinture sur ce projet via l'UI
 *   2. Déclencher l'enregistrement
 *   3. Vérifier que TOUTES les colonnes de historique sont peuplées
 *      (toute colonne null/0 = bug de mapping JS ↔ Controller ↔ SQL).
 *
 * Idempotent : DELETE des rows existantes pour le maven_key avant chaque INSERT.
 *
 * Usage :
 *   php bin/console app:seed:projet-fixture
 *   php bin/console app:seed:projet-fixture autre.maven:key
 */
#[AsCommand(
    name: 'app:seed:projet-fixture',
    description: 'Seed un projet test (toutes tables de collecte peuplées) pour valider la chaîne peinture→enregistrement.',
)]
class SeedProjetFixtureCommand extends Command
{
    private const DEFAULT_MAVEN_KEY = 'fr.test:projet-fixture';
    private const FIXTURE_USER_EMAIL = 'fixture-tester@ma-moulinette.fr';
    private const FIXTURE_GROUPE = 'fr-test';

    public function __construct(
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.environment%')]
        private readonly string $appEnv,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'maven_key',
            InputArgument::OPTIONAL,
            'Maven key du projet de test',
            self::DEFAULT_MAVEN_KEY
        );
    }

    /**
     * [Description for execute]
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * Created at: 15/05/2026 08:21:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // MODIF 2026-05-21 : garde-fou env=dev (seed données fictives).
        $io = new SymfonyStyle($input, $output);
        if ($this->appEnv !== 'dev') {
            $io->error(sprintf('❌ Cette commande est réservée à env=dev (env courant : %s).', $this->appEnv));
            return Command::FAILURE;
        }

        $mavenKey = (string) $input->getArgument('maven_key');
        $parts    = explode(':', $mavenKey, 2);
        /* MODIF 2026-05-20 : offset 0 garanti par explode (non-empty-list). */
        $group    = $parts[0] ?: 'fr.test';
        $artifact = $parts[1] ?? 'projet-fixture';
        $version  = '1.0.0-RELEASE';
        $analyseKey = 'AY_fixture_' . substr(md5($mavenKey), 0, 16);
        $dateAnalyse  = (new \DateTimeImmutable('-2 hours'))->format('Y-m-d H:i:sO');
        $dateEnreg    = (new \DateTimeImmutable())->format('Y-m-d H:i:sO');
        $modeCollecte = 'COLLECTE';

        $conn = $this->em->getConnection();

        $output->writeln('');
        $output->writeln('====================================================================');
        $output->writeln(" Seed projet fixture : $mavenKey");
        $output->writeln('====================================================================');

        $conn->beginTransaction();
        try {
            $this->purge($conn, $mavenKey, $output);

            $this->seedUtilisateurEtGroupe($conn, $output);
            $this->seedListeProjet($conn, $mavenKey, $artifact, $output);
            $this->seedInformationProjet($conn, $mavenKey, $analyseKey, $version, $dateAnalyse, $dateEnreg, $modeCollecte, $output);
            $this->seedMesures($conn, $mavenKey, $artifact, $dateEnreg, $modeCollecte, $output);
            $this->seedNoSonar($conn, $mavenKey, $dateEnreg, $modeCollecte, $output);
            $this->seedTodo($conn, $mavenKey, $dateEnreg, $modeCollecte, $output);
            $this->seedLogger($conn, $mavenKey, $dateEnreg, $modeCollecte, $output);
            $this->seedLoggerDetails($conn, $mavenKey, $version, $dateEnreg, $modeCollecte, $output);
            $this->seedAnomalie($conn, $mavenKey, $artifact, $dateEnreg, $modeCollecte, $output);
            $this->seedAnomalieDetails($conn, $mavenKey, $artifact, $dateEnreg, $modeCollecte, $output);
            $this->seedHotspots($conn, $mavenKey, $version, $dateAnalyse, $dateEnreg, $modeCollecte, $output);
            $this->seedHotspotDetails($conn, $mavenKey, $version, $dateAnalyse, $dateEnreg, $modeCollecte, $output);
            $this->seedHotspotOwasp($conn, $mavenKey, $version, $dateAnalyse, $dateEnreg, $modeCollecte, $output);
            $this->seedCleanCode($conn, $mavenKey, $artifact, $dateEnreg, $modeCollecte, $output);

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            $output->writeln('');
            $output->writeln(' ❌ ERREUR : ' . $e->getMessage());
            $output->writeln(' (rollback effectue)');
            return Command::FAILURE;
        }

        $output->writeln('');
        $output->writeln('--------------------------------------------------------------------');
        $output->writeln(' ✅ Seed termine.');
        $output->writeln(" 1. Connecte-toi avec : " . self::FIXTURE_USER_EMAIL . " (mdp inchangé si user existant)");
        $output->writeln(" 2. Lance la peinture sur le projet : $mavenKey");
        $output->writeln(' 3. Click "Enregistrement"');
        $output->writeln(" 4. Verification SQL :");
        $output->writeln("    SELECT * FROM ma_moulinette.historique WHERE maven_key='$mavenKey'");
        $output->writeln('    -> Toute colonne NULL ou 0 = drift JS↔Controller↔SQL a investiguer.');
        $output->writeln('--------------------------------------------------------------------');

        return Command::SUCCESS;
    }

    /**
     * [Description for purge]
     *
     * @param Connection $conn
     * @param string $mavenKey
     * @param OutputInterface $output
     *
     * @return void
     *
     * Created at: 15/05/2026 08:21:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function purge(Connection $conn, string $mavenKey, OutputInterface $output): void
    {
        $output->writeln(' [PURGE] DELETE existantes pour maven_key...');
        foreach ([
            'logger_details', 'logger',
            'no_sonar', 'todo',
            'anomalie_details', 'anomalie',
            'hotspot_details', 'hotspot_owasp', 'hotspots',
            'mesures', 'information_projet',
            'clean_code',
            'liste_projet',
        ] as $table) {
            $conn->executeStatement(
                "DELETE FROM ma_moulinette.$table WHERE maven_key = :mk",
                ['mk' => $mavenKey]
            );
        }
    }

    /**
     * [Description for seedUtilisateurEtGroupe]
     *
     * @param Connection $conn
     * @param OutputInterface $output
     *
     * @return void
     *
     * Created at: 15/05/2026 08:22:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function seedUtilisateurEtGroupe(Connection $conn, OutputInterface $output): void
    {
        $output->writeln(' [SEED] utilisateur + groupe_fonctionnel (idempotent)...');

        // groupe_fonctionnel : colonnes réelles = (id, groupe_fonctionnel, description, date_modification, date_enregistrement).
        // Pas de contrainte UNIQUE explicite -> on SELECT puis INSERT si absent.
        $existsGF = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM ma_moulinette.groupe_fonctionnel WHERE groupe_fonctionnel = :gf',
            ['gf' => self::FIXTURE_GROUPE]
        );
        if ($existsGF === 0) {
            $conn->executeStatement(<<<SQL
                INSERT INTO ma_moulinette.groupe_fonctionnel (groupe_fonctionnel, description, date_enregistrement)
                VALUES (:gf, :desc, NOW())
            SQL, [
                'gf'   => self::FIXTURE_GROUPE,
                'desc' => 'Groupe créé par app:seed:projet-fixture pour les tests E2E.',
            ]);
        }

        // utilisateur : si absent, on le crée avec mot de passe `fixture123` (bcrypt).
        // Si présent, UPDATE des champs ACL uniquement (mot de passe conservé).
        $exists = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM ma_moulinette.utilisateur WHERE courriel = :mail',
            ['mail' => self::FIXTURE_USER_EMAIL]
        );

        if ($exists === 0) {
            $output->writeln('   → création utilisateur fixture (mdp : fixture123)');
            $passwordHash = password_hash('fixture123', PASSWORD_BCRYPT, ['cost' => 13]);
            // groupe_id VARCHAR(26) NOT NULL — pas de FK explicite, on génère un identifiant unique.
            $groupeId = strtolower(bin2hex(random_bytes(13))); // 26 chars hex
            $conn->executeStatement(<<<SQL
                INSERT INTO ma_moulinette.utilisateur
                    (prenom, nom, courriel, password, roles, groupe_id, liste_groupe_fonctionnel, actif, preference, date_enregistrement)
                VALUES
                    (:prenom, :nom, :mail, :pwd, :roles, :gid, :groupes, true, :pref, NOW())
            SQL, [
                'prenom'  => 'Fixture',
                'nom'     => 'Tester',
                'mail'    => self::FIXTURE_USER_EMAIL,
                'pwd'     => $passwordHash,
                'roles'   => json_encode(['ROLE_UTILISATEUR', 'ROLE_COLLECTE', 'ROLE_SECURITY']),
                'gid'     => $groupeId,
                'groupes' => json_encode([self::FIXTURE_GROUPE]),
                'pref'    => json_encode((object) []),
            ]);
        } else {
            $output->writeln('   → MAJ utilisateur fixture existant (mdp conservé)');
            $conn->executeStatement(<<<SQL
                UPDATE ma_moulinette.utilisateur
                SET liste_groupe_fonctionnel = :groupes,
                    roles = :roles,
                    actif = true
                WHERE courriel = :mail
            SQL, [
                'mail'    => self::FIXTURE_USER_EMAIL,
                'roles'   => json_encode(['ROLE_UTILISATEUR', 'ROLE_COLLECTE', 'ROLE_SECURITY']),
                'groupes' => json_encode([self::FIXTURE_GROUPE]),
            ]);
        }
    }

    /**
     * [Description for seedListeProjet]
     *
     * @param Connection $conn
     * @param string $mavenKey
     * @param string $artifact
     * @param OutputInterface $output
     *
     * @return void
     *
     * Created at: 15/05/2026 08:22:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function seedListeProjet(Connection $conn, string $mavenKey, string $artifact, OutputInterface $output): void
    {
        $output->writeln(' [SEED] liste_projet...');
        // tags est de type JSON (pas Postgres array). Format : '["fr-test-acl"]'.
        $conn->executeStatement(<<<SQL
            INSERT INTO ma_moulinette.liste_projet (maven_key, name, visibility, tags)
            VALUES (:mk, :name, :vis, :tags)
        SQL, [
            'mk'   => $mavenKey,
            'name' => $artifact,
            'vis'  => 'public',
            'tags' => json_encode([self::FIXTURE_GROUPE . '-acl']),
        ]);
    }

    /**
     * [Description for seedInformationProjet]
     *
     * @param Connection $conn
     * @param string $mavenKey
     * @param string $analyseKey
     * @param string $version
     * @param string $dateAnalyse
     * @param string $dateEnreg
     * @param string $modeCollecte
     * @param OutputInterface $output
     *
     * @return void
     *
     * Created at: 15/05/2026 08:22:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function seedInformationProjet(
        Connection $conn,
        string $mavenKey,
        string $analyseKey,
        string $version,
        string $dateAnalyse,
        string $dateEnreg,
        string $modeCollecte,
        OutputInterface $output
    ): void {
        $output->writeln(' [SEED] information_projet...');
        $conn->executeStatement(<<<SQL
            INSERT INTO ma_moulinette.information_projet
                (maven_key, analyse_key, date_analyse, project_version, type_analyse,
                version_sonar, version_release_sonar, version_snapshot_sonar, version_autre_sonar,
                mode_collecte, utilisateur_collecte, date_enregistrement)
            VALUES (:mk, :ak, :da, :v, :ta, :vs, :vr, :vss, :va, :mc, :uc, :de)
        SQL, [
            'mk'  => $mavenKey,
            'ak'  => $analyseKey,
            'da'  => $dateAnalyse,
            'v'   => $version,
            'ta'  => 'release',
            'vs'  => 12,    // total versions Sonar
            'vr'  => 4,     // release
            'vss' => 7,     // snapshot
            'va'  => 1,     // autre
            'mc'  => $modeCollecte,
            'uc'  => self::FIXTURE_USER_EMAIL,
            'de'  => $dateEnreg,
        ]);
    }

    /**
     * [Description for seedMesures]
     *
     * @param Connection $conn
     * @param string $mavenKey
     * @param string $artifact
     * @param string $dateEnreg
     * @param string $modeCollecte
     * @param OutputInterface $output
     *
     * @return void
     *
     * Created at: 15/05/2026 08:22:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function seedMesures(
        Connection $conn,
        string $mavenKey,
        string $artifact,
        string $dateEnreg,
        string $modeCollecte,
        OutputInterface $output
    ): void {
        $output->writeln(' [SEED] mesures (~84 colonnes peuplées, valeurs distinctes)...');

        $cols = [
            'maven_key'                                          => $mavenKey,
            'project_name'                                       => $artifact,
            'alert_status'                                       => 'OK',
            'lines'                                              => 18430,
            'ncloc'                                              => 17240,
            'ncloc_language_distribution'                        => 'java=12000;js=3200;xml=2040',
            'files'                                              => 86,
            'classes'                                            => 78,
            'functions'                                          => 612,
            'statements'                                         => 5840,
            'comment_lines'                                      => 2350,
            'comment_lines_density'                              => 11.32,
            'comment_lines_rating'                               => '2',
            'coverage'                                           => 73.45,
            'branch_coverage'                                    => 64.21,
            'line_coverage'                                      => 78.92,
            'lines_to_cover'                                     => 14200,
            'conditions_to_cover'                                => 3120,
            'uncovered_conditions'                               => 1116,
            'coverage_rating'                                    => '2',  // -> 'B' après normalize
            'tests'                                              => 412,
            'test_execution_time'                                => 18420,
            'test_errors'                                        => 3,
            'test_failures'                                      => 5,
            'skipped_tests'                                      => 7,
            'test_success_density'                               => 98.06,
            'duplicated_files'                                   => 9,
            'duplicated_blocks'                                  => 14,
            'duplicated_lines'                                   => 482,
            'duplicated_lines_density'                           => 2.79,
            'duplicated_lines_rating'                            => '2',
            'complexity'                                         => 1240,
            'complexity_rating'                                  => '3',
            'cognitive_complexity'                               => 980,
            'cognitive_complexity_rating'                        => '2',
            'complexity_ratio'                                   => 7.19,
            'cognitive_complexity_ratio'                         => 5.68,
            'open_issues'                                        => 47,
            'reopened_issues'                                    => 2,
            'confirmed_issues'                                   => 12,
            'false_positive_issues'                              => 4,
            'accepted_issues'                                    => 18,
            'high_impact_accepted_issues'                        => 3,
            'violations'                                         => 89,
            'blocker_violations'                                 => 1,
            'critical_violations'                                => 8,
            'major_violations'                                   => 27,
            'minor_violations'                                   => 41,
            'info_violations'                                    => 12,
            'software_quality_blocker_issues'                    => 1,
            'software_quality_high_issues'                       => 9,
            'software_quality_medium_issues'                     => 25,
            'software_quality_low_issues'                        => 38,
            'software_quality_info_issues'                       => 16,
            'code_smells'                                        => 56,
            'maintainability_issues'                             => 'mainta',
            'sqale_index'                                        => 4320,
            'sqale_debt_ratio'                                   => 3.72,
            'sqale_rating'                                       => '2',
            'effort_to_reach_maintainability_rating_a'           => 1240,
            'software_quality_maintainability_issues'            => 'sq_mainta',
            'software_quality_maintainability_rating'            => '2',
            'software_quality_maintainability_debt_ratio'        => 3.91,
            'software_quality_maintainability_remediation_effort'=> 4520,
            'effort_to_reach_software_quality_maintainability_rating_a' => 1180,
            'bugs'                                               => 12,
            'reliability_issues'                                 => 'rel',
            'reliability_rating'                                 => '3',
            'reliability_remediation_effort'                     => 720,
            'software_quality_reliability_issues'                => 'sq_rel',
            'software_quality_reliability_rating'                => '3',
            'software_quality_reliability_remediation_effort'    => 780,
            'vulnerabilities'                                    => 5,
            'security_issues'                                    => 'sec',
            'security_rating'                                    => '2',
            'security_remediation_effort'                        => 480,
            'software_quality_security_issues'                   => 'sq_sec',
            'software_quality_security_rating'                   => '2',
            'software_quality_security_remediation_effort'       => 510,
            'security_hotspots'                                  => 23,
            'security_review_rating'                              => '2',
            'security_hotspots_reviewed'                          => 65.22,
            'mode_collecte'                                       => $modeCollecte,
            'utilisateur_collecte'                                => self::FIXTURE_USER_EMAIL,
            'date_enregistrement'                                 => $dateEnreg,
        ];

        $colNames = implode(', ', array_keys($cols));
        $placeholders = implode(', ', array_map(static fn (string $k): string => ":$k", array_keys($cols)));
        $conn->executeStatement(
            "INSERT INTO ma_moulinette.mesures ($colNames) VALUES ($placeholders)",
            $cols
        );
    }

    /**
     * [Description for seedNoSonar]
     *
     * @param Connection $conn
     * @param string $mavenKey
     * @param string $dateEnreg
     * @param string $modeCollecte
     * @param OutputInterface $output
     *
     * @return void
     *
     * Created at: 15/05/2026 08:22:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function seedNoSonar(Connection $conn, string $mavenKey, string $dateEnreg, string $modeCollecte, OutputInterface $output): void
    {
        $output->writeln(' [SEED] no_sonar (6 rules differentes)...');
        $rules = [
            ['rule' => 'java:S1309',    'component' => 'src/main/java/com/example/A.java',  'line' => 12],
            ['rule' => 'java:S1310',    'component' => 'src/main/java/com/example/B.java',  'line' => 47],
            ['rule' => 'java:S1315',    'component' => 'src/main/java/com/example/C.java',  'line' => 83],
            ['rule' => 'java:NoSonar',  'component' => 'src/main/java/com/example/D.java',  'line' => 19],
            ['rule' => 'python:NoSonar','component' => 'src/main/python/util.py',           'line' => 35],
            ['rule' => 'php:NoSonar',   'component' => 'src/main/php/controller.php',       'line' => 124],
        ];
        foreach ($rules as $r) {
            $conn->executeStatement(<<<SQL
                INSERT INTO ma_moulinette.no_sonar (maven_key, rule, component, line, mode_collecte, utilisateur_collecte, date_enregistrement)
                VALUES (:mk, :rule, :comp, :line, :mc, :uc, :de)
            SQL, [
                'mk' => $mavenKey, 'rule' => $r['rule'], 'comp' => $r['component'], 'line' => $r['line'],
                'mc' => $modeCollecte, 'uc' => self::FIXTURE_USER_EMAIL, 'de' => $dateEnreg,
            ]);
        }
    }

    /**
     * [Description for seedTodo]
     *
     * @param Connection $conn
     * @param string $mavenKey
     * @param string $dateEnreg
     * @param string $modeCollecte
     * @param OutputInterface $output
     *
     * @return void
     *
     * Created at: 15/05/2026 08:22:57 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function seedTodo(Connection $conn, string $mavenKey, string $dateEnreg, string $modeCollecte, OutputInterface $output): void
    {
        $output->writeln(' [SEED] todo (8 langages : java, python, php, xml, web, javascript, typescript, ruby)...');
        $rules = [
            'java:S1135', 'python:S1135', 'php:S1135', 'xml:S1135',
            'web:S1135', 'javascript:S1135', 'typescript:S1135', 'ruby:S1135',
        ];
        $line = 10;
        foreach ($rules as $rule) {
            $conn->executeStatement(<<<SQL
                INSERT INTO ma_moulinette.todo (maven_key, rule, component, line, mode_collecte, utilisateur_collecte, date_enregistrement)
                VALUES (:mk, :rule, :comp, :line, :mc, :uc, :de)
            SQL, [
                'mk' => $mavenKey, 'rule' => $rule,
                'comp' => 'src/test/' . str_replace(':', '_', $rule) . '.txt',
                'line' => $line, 'mc' => $modeCollecte, 'uc' => self::FIXTURE_USER_EMAIL, 'de' => $dateEnreg,
            ]);
            $line += 5;
        }
    }

    /**
     * [Description for seedLogger]
     *
     * @param Connection $conn
     * @param string $mavenKey
     * @param string $dateEnreg
     * @param string $modeCollecte
     * @param OutputInterface $output
     *
     * @return void
     *
     * Created at: 15/05/2026 08:23:08 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function seedLogger(Connection $conn, string $mavenKey, string $dateEnreg, string $modeCollecte, OutputInterface $output): void
    {
        $output->writeln(' [SEED] logger (compteurs aggregate)...');
        $conn->executeStatement(<<<SQL
            INSERT INTO ma_moulinette.logger (maven_key, logger_info, logger_warn, logger_error, logger_debug, mode_collecte, utilisateur_collecte, date_enregistrement)
            VALUES (:mk, :info, :warn, :err, :debug, :mc, :uc, :de)
        SQL, [
            'mk' => $mavenKey,
            'info' => 14, 'warn' => 3, 'err' => 33, 'debug' => 6,
            'mc' => $modeCollecte, 'uc' => self::FIXTURE_USER_EMAIL, 'de' => $dateEnreg,
        ]);
    }

    private function seedLoggerDetails(Connection $conn, string $mavenKey, string $version, string $dateEnreg, string $modeCollecte, OutputInterface $output): void
    {
        $output->writeln(' [SEED] logger_details (mix SLF4J + Commons Logging + null)...');
        // 56 occurrences au total : 14 info + 3 warn + 33 error + 6 debug
        // Mix frameworks pour valider le breakdown
        $rows = [];
        $frameworks = ['SLF4J', 'SLF4J', 'SLF4J', 'Commons Logging']; // 75% SLF4J / 25% Commons
        $files = [
            'src/main/java/com/example/Service.java',
            'src/main/java/com/example/Controller.java',
            'src/main/java/com/example/Repository.java',
        ];
        $i = 0;
        foreach (['info' => 14, 'warn' => 3, 'error' => 33, 'debug' => 6] as $level => $count) {
            for ($n = 0; $n < $count; $n++) {
                $fw = $frameworks[$i % 4];
                $fp = $files[$i % 3];
                $rows[] = [
                    'maven_key' => $mavenKey, 'project_version' => $version,
                    'level' => $level, 'framework' => $fw,
                    'file_path' => $fp,
                    'file_name' => basename($fp),
                    'class_name' => pathinfo($fp, PATHINFO_FILENAME),
                    'line_number' => 10 + $n,
                    'sonar_issue_key' => substr(md5("$level-$n-$fp"), 0, 36),
                    'mode_collecte' => $modeCollecte,
                    'utilisateur_collecte' => self::FIXTURE_USER_EMAIL,
                    'date_enregistrement' => $dateEnreg,
                ];
                $i++;
            }
        }
        foreach ($rows as $r) {
            $conn->executeStatement(<<<SQL
                INSERT INTO ma_moulinette.logger_details
                    (maven_key, project_version, level, framework, file_path, file_name, class_name, line_number, sonar_issue_key, mode_collecte, utilisateur_collecte, date_enregistrement)
                VALUES (:maven_key, :project_version, :level, :framework, :file_path, :file_name, :class_name, :line_number, :sonar_issue_key, :mode_collecte, :utilisateur_collecte, :date_enregistrement)
            SQL, $r);
        }
    }

    /**
     * [Description for seedAnomalie]
     *
     * @param Connection $conn
     * @param string $mavenKey
     * @param string $artifact
     * @param string $dateEnreg
     * @param string $modeCollecte
     * @param OutputInterface $output
     *
     * @return void
     *
     * Created at: 15/05/2026 08:23:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function seedAnomalie(Connection $conn, string $mavenKey, string $artifact, string $dateEnreg, string $modeCollecte, OutputInterface $output): void
    {
        $output->writeln(' [SEED] anomalie (aggregate)...');
        $conn->executeStatement(<<<SQL
            INSERT INTO ma_moulinette.anomalie
                (maven_key, project_name, anomalie_total, dette_minute, dette_reliability_minute, dette_vulnerability_minute, dette_code_smell_minute,
                dette_reliability, dette_vulnerability, dette, dette_code_smell,
                frontend, backend, autre, inconnu,
                blocker, critical, major, info, minor, bug, vulnerability, code_smell,
                mode_collecte, utilisateur_collecte, date_enregistrement)
            VALUES (:mk, :pn, :total, :dm, :drm, :dvm, :dcsm,
                    :dr, :dv, :d, :dcs,
                    :fe, :be, :au, :in,
                    :bl, :ct, :mj, :inf, :mn, :bug, :vuln, :cs,
                    :mc, :uc, :de)
        SQL, [
            'mk' => $mavenKey, 'pn' => $artifact,
            'total' => 89,
            'dm' => 4320, 'drm' => 720, 'dvm' => 480, 'dcsm' => 3120,
            'dr' => '12h', 'dv' => '8h', 'd' => '3j 2h', 'dcs' => '2j 4h',
            'fe' => 24, 'be' => 51, 'au' => 9, 'in' => 5,
            'bl' => 1, 'ct' => 8, 'mj' => 27, 'inf' => 12, 'mn' => 41,
            'bug' => 12, 'vuln' => 5, 'cs' => 56,
            'mc' => $modeCollecte, 'uc' => self::FIXTURE_USER_EMAIL, 'de' => $dateEnreg,
        ]);
    }

    /**
     * [Description for seedAnomalieDetails]
     *
     * @param Connection $conn
     * @param string $mavenKey
     * @param string $artifact
     * @param string $dateEnreg
     * @param string $modeCollecte
     * @param OutputInterface $output
     *
     * @return void
     *
     * Created at: 15/05/2026 08:23:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function seedAnomalieDetails(Connection $conn, string $mavenKey, string $artifact, string $dateEnreg, string $modeCollecte, OutputInterface $output): void
    {
        $output->writeln(' [SEED] anomalie_details (severity breakdown)...');
        $conn->executeStatement(<<<SQL
            INSERT INTO ma_moulinette.anomalie_details
                (maven_key, name,
                bug_blocker, bug_critical, bug_info, bug_major, bug_minor,
                vulnerability_blocker, vulnerability_critical, vulnerability_info, vulnerability_major, vulnerability_minor,
                code_smell_blocker, code_smell_critical, code_smell_info, code_smell_major, code_smell_minor,
                mode_collecte, utilisateur_collecte, date_enregistrement)
            VALUES (:mk, :name,
                    :bb, :bc, :bi, :bma, :bmi,
                    :vb, :vc, :vi, :vma, :vmi,
                    :csb, :csc, :csi, :csma, :csmi,
                    :mc, :uc, :de)
        SQL, [
            'mk' => $mavenKey, 'name' => $artifact,
            'bb' => 1, 'bc' => 3, 'bi' => 1, 'bma' => 5, 'bmi' => 2,
            'vb' => 1, 'vc' => 2, 'vi' => 1, 'vma' => 1, 'vmi' => 0,
            'csb' => 0, 'csc' => 3, 'csi' => 10, 'csma' => 21, 'csmi' => 22,
            'mc' => $modeCollecte, 'uc' => self::FIXTURE_USER_EMAIL, 'de' => $dateEnreg,
        ]);
    }

    /**
     * [Description for seedHotspots]
     *
     * @param Connection $conn
     * @param string $mavenKey
     * @param string $version
     * @param string $dateAnalyse
     * @param string $dateEnreg
     * @param string $modeCollecte
     * @param OutputInterface $output
     *
     * @return void
     *
     * Created at: 15/05/2026 08:23:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function seedHotspots(Connection $conn, string $mavenKey, string $version, string $dateAnalyse, string $dateEnreg, string $modeCollecte, OutputInterface $output): void
    {
        $output->writeln(' [SEED] hotspots (5 occurrences, severities variees)...');
        // niveau INT NOT NULL : 1=HIGH, 2=MEDIUM, 3=LOW.
        $rows = [
            ['cat' => 'auth',         'rule' => 'java:S2092', 'prob' => 'HIGH',   'st' => 'TO_REVIEW', 'res' => 'PENDING', 'niv' => 1],
            ['cat' => 'encryption',   'rule' => 'java:S4423', 'prob' => 'HIGH',   'st' => 'TO_REVIEW', 'res' => 'PENDING', 'niv' => 1],
            ['cat' => 'sql_injection','rule' => 'java:S2077', 'prob' => 'MEDIUM', 'st' => 'TO_REVIEW', 'res' => 'PENDING', 'niv' => 2],
            ['cat' => 'log_injection','rule' => 'java:S5145', 'prob' => 'LOW',    'st' => 'REVIEWED',  'res' => 'FIXED',   'niv' => 3],
            ['cat' => 'others',       'rule' => 'java:S2068', 'prob' => 'MEDIUM', 'st' => 'REVIEWED',  'res' => 'FIXED',   'niv' => 2],
        ];
        foreach ($rows as $i => $r) {
            $conn->executeStatement(<<<SQL
                INSERT INTO ma_moulinette.hotspots
                    (maven_key, version, date_version, hotspot_key, security_category, rule_key, probability, status, resolution, niveau, mode_collecte, utilisateur_collecte, date_enregistrement)
                VALUES (:mk, :v, :dv, :hk, :cat, :rk, :prob, :st, :res, :niv, :mc, :uc, :de)
            SQL, [
                'mk' => $mavenKey, 'v' => $version, 'dv' => $dateAnalyse,
                'hk' => 'HS-' . substr(md5("$mavenKey-$i"), 0, 12),
                'cat' => $r['cat'], 'rk' => $r['rule'], 'prob' => $r['prob'],
                'st' => $r['st'], 'res' => $r['res'], 'niv' => $r['niv'],
                'mc' => $modeCollecte, 'uc' => self::FIXTURE_USER_EMAIL, 'de' => $dateEnreg,
            ]);
        }
    }

    /**
     * [Description for seedHotspotDetails]
     *
     * @param Connection $conn
     * @param string $mavenKey
     * @param string $version
     * @param string $dateAnalyse
     * @param string $dateEnreg
     * @param string $modeCollecte
     * @param OutputInterface $output
     *
     * @return void
     *
     * Created at: 15/05/2026 08:23:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function seedHotspotDetails(Connection $conn, string $mavenKey, string $version, string $dateAnalyse, string $dateEnreg, string $modeCollecte, OutputInterface $output): void
    {
        $output->writeln(' [SEED] hotspot_details (5 occurrences avec contexte fichier/ligne)...');
        // niveau INT NOT NULL (1=HIGH, 2=MEDIUM, 3=LOW).
        $rows = [
            ['cat' => 'auth',         'sev' => 'HIGH',   'niv' => 1],
            ['cat' => 'encryption',   'sev' => 'HIGH',   'niv' => 1],
            ['cat' => 'sql_injection','sev' => 'MEDIUM', 'niv' => 2],
            ['cat' => 'log_injection','sev' => 'LOW',    'niv' => 3],
            ['cat' => 'others',       'sev' => 'MEDIUM', 'niv' => 2],
        ];
        foreach ($rows as $i => $r) {
            $conn->executeStatement(<<<SQL
                INSERT INTO ma_moulinette.hotspot_details
                    (maven_key, version, date_version, security_category, rule_key, rule_name, severity, status, resolution, niveau, frontend, backend, autre, file_name, file_path, line, message, hotspot_key, mode_collecte, utilisateur_collecte, date_enregistrement)
                VALUES (:mk, :v, :dv, :cat, :rk, :rn, :sev, :st, :res, :niv, :fe, :be, :au, :fn, :fp, :line, :msg, :hk, :mc, :uc, :de)
            SQL, [
                'mk' => $mavenKey, 'v' => $version, 'dv' => $dateAnalyse,
                'cat' => $r['cat'], 'rk' => 'java:S' . (2000 + $i),
                'rn'  => 'Rule label ' . $r['cat'],
                'sev' => $r['sev'],
                'st'  => 'TO_REVIEW', 'res' => 'PENDING', 'niv' => $r['niv'],
                'fe' => $i === 0 ? 1 : 0, 'be' => $i % 2,'au' => 0,
                'fn' => 'File' . $i . '.java',
                'fp' => 'src/main/java/com/example/File' . $i . '.java',
                'line' => 30 + $i * 7,
                'msg' => 'Detail hotspot ' . $r['cat'],
                'hk'  => 'HS-' . substr(md5("$mavenKey-$i"), 0, 12),
                'mc' => $modeCollecte, 'uc' => self::FIXTURE_USER_EMAIL, 'de' => $dateEnreg,
            ]);
        }
    }

    /**
     * [Description for seedHotspotOwasp]
     *
     * @param Connection $conn
     * @param string $mavenKey
     * @param string $version
     * @param string $dateAnalyse
     * @param string $dateEnreg
     * @param string $modeCollecte
     * @param OutputInterface $output
     *
     * @return void
     *
     * Created at: 15/05/2026 08:23:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function seedHotspotOwasp(Connection $conn, string $mavenKey, string $version, string $dateAnalyse, string $dateEnreg, string $modeCollecte, OutputInterface $output): void
    {
        $output->writeln(' [SEED] hotspot_owasp (5 entries OWASP 2021)...');
        // referential_owasp INT NOT NULL (2017 ou 2021), niveau INT NOT NULL.
        // menace VARCHAR(8) NOT NULL : on met A1..A5 (court).
        $cats = ['A01-2021', 'A02-2021', 'A03-2021', 'A04-2021', 'A07-2021'];
        $probs = ['HIGH', 'HIGH', 'MEDIUM', 'LOW', 'MEDIUM'];
        $nivs  = [1, 1, 2, 3, 2];
        foreach ($cats as $i => $cat) {
            $conn->executeStatement(<<<SQL
                INSERT INTO ma_moulinette.hotspot_owasp
                    (referential_owasp, maven_key, version, date_version, menace, security_category, rule_key, probability, status, resolution, niveau, mode_collecte, utilisateur_collecte, date_enregistrement)
                VALUES (:ref, :mk, :v, :dv, :m, :cat, :rk, :prob, :st, :res, :niv, :mc, :uc, :de)
            SQL, [
                'ref' => 2021, 'mk' => $mavenKey, 'v' => $version, 'dv' => $dateAnalyse,
                'm'   => 'A' . ($i + 1),
                'cat' => $cat, 'rk' => 'java:S' . (3000 + $i),
                'prob' => $probs[$i],
                'st' => 'TO_REVIEW', 'res' => 'PENDING', 'niv' => $nivs[$i],
                'mc' => $modeCollecte, 'uc' => self::FIXTURE_USER_EMAIL, 'de' => $dateEnreg,
            ]);
        }
    }

    private function seedCleanCode(Connection $conn, string $mavenKey, string $artifact, string $dateEnreg, string $modeCollecte, OutputInterface $output): void
    {
        $output->writeln(' [SEED] clean_code (15 indicateurs SonarQube 10+, valeurs distinctes)...');
        $conn->executeStatement(<<<SQL
            INSERT INTO ma_moulinette.clean_code
                (maven_key, project_name, issue_total,
                cc_consistent, cc_intentional, cc_adaptable, cc_responsible,
                quality_maintainability, quality_reliability, quality_security,
                impact_blocker, impact_high, impact_medium, impact_low, impact_info,
                owasp_top10, sans_top25, cwe,
                mode_collecte, utilisateur_collecte, date_enregistrement)
            VALUES
                (:mk, :pn, :total,
                :cc_con, :cc_int, :cc_ada, :cc_res,
                :q_mai, :q_rel, :q_sec,
                :imp_blo, :imp_hig, :imp_med, :imp_low, :imp_inf,
                :owasp, :sans, :cwe,
                :mc, :uc, :de)
        SQL, [
            'mk'    => $mavenKey,
            'pn'    => $artifact,
            'total' => 124,
            // Clean Code Attribute Categories
            'cc_con' => 42,
            'cc_int' => 31,
            'cc_ada' => 28,
            'cc_res' => 23,
            // Impact Software Qualities
            'q_mai'  => 56,
            'q_rel'  => 38,
            'q_sec'  => 30,
            // Impact Severities
            'imp_blo' => 4,
            'imp_hig' => 18,
            'imp_med' => 47,
            'imp_low' => 38,
            'imp_inf' => 17,
            // Standards
            'owasp'  => 12,
            'sans'   => 8,
            'cwe'    => 15,
            'mc'     => $modeCollecte,
            'uc'     => self::FIXTURE_USER_EMAIL,
            'de'     => $dateEnreg,
        ]);
    }
}
