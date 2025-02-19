<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Repository;

use App\Entity\Repartition;
use App\DataFixtures\RepartitionFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


/**
 * [Description RepartitionRepositoryTest]
 */
class RepartitionRepositoryTest extends KernelTestCase
{

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $name = 'ma-moulinette';
    private static $bugBlocker = 0;
    private static $bugCritical = 0;
    private static $bugMajor = 1843;
    private static $bugMinor = 29;
    private static $bugInfo = 0;
    private static $vulnerabilityBlocker = 0;
    private static $vulnerabilityCritical = 0;
    private static $vulnerabilityMajor = 0;
    private static $vulnerabilityMinor = 1427;
    private static $vulnerabilityInfo = 3;
    private static $codeSmellBlocker = 0;
    private static $codeSmellCritical = 1194;
    private static $codeSmellMajor = 13272;
    private static $codeSmellMinor = 8207;
    private static $codeSmellInfo = 13632;
    private static $frontend = 0;
    private static $frontendBugBlocker = 0;
    private static $frontendBugCritical = 0;
    private static $frontendBugMajor = 1232;
    private static $frontendBugMinor = 21;
    private static $frontendBugInfo = 0;
    private static $frontendVulnerabilityBlocker = 0;
    private static $frontendVulnerabilityCritical = 0;
    private static $frontendVulnerabilityMajor = 0;
    private static $frontendVulnerabilityMinor = 898;
    private static $frontendVulnerabilityInfo = 3;
    private static $frontendCodeSmellBlocker = 0;
    private static $frontendCodeSmellCritical = 554;
    private static $frontendCodeSmellMajor = 4441;
    private static $frontendCodeSmellMinor = 6603;
    private static $frontendCodeSmellInfo = 4009;
    private static $backend = 0;
    private static $backendBugBlocker = 0;
    private static $backendBugCritical = 0;
    private static $backendBugMajor = 611;
    private static $backendBugMinor = 8;
    private static $backendBugInfo = 0;
    private static $backendVulnerabilityBlocker = 0;
    private static $backendVulnerabilityCritical = 0;
    private static $backendVulnerabilityMajor = 0;
    private static $backendVulnerabilityMinor = 529;
    private static $backendVulnerabilityInfo = 0;
    private static $backendCodeSmellBlocker = 0;
    private static $backendCodeSmellCritical = 640;
    private static $backendCodeSmellMajor = 5559;
    private static $backendCodeSmellMinor = 3396;
    private static $backendCodeSmellInfo = 4155;
    private static $autre = 0;
    private static $autreBugBlocker = 0;
    private static $autreBugCritical = 0;
    private static $autreBugMajor = 0;
    private static $autreBugMinor = 0;
    private static $autreBugInfo = 0;
    private static $autreVulnerabilityBlocker = 0;
    private static $autreVulnerabilityCritical = 0;
    private static $autreVulnerabilityMajor = 0;
    private static $autreVulnerabilityMinor = 0;
    private static $autreVulnerabilityInfo = 0;
    private static $autreCodeSmellBlocker = 0;
    private static $autreCodeSmellCritical = 0;
    private static $autreCodeSmellMajor = 0;
    private static $autreCodeSmellMinor = 0;
    private static $autreCodeSmellInfo = 0;
    private static $inconnue = 0;
    private static $inconnueBugBlocker = 0;
    private static $inconnueBugCritical = 0;
    private static $inconnueBugMajor = 0;
    private static $inconnueBugMinor = 0;
    private static $inconnueBugInfo = 0;
    private static $inconnueVulnerabilityBlocker = 0;
    private static $inconnueVulnerabilityCritical = 0;
    private static $inconnueVulnerabilityMajor = 0;
    private static $inconnueVulnerabilityMinor = 0;
    private static $inconnueVulnerabilityInfo = 0;
    private static $inconnueCodeSmellBlocker = 0;
    private static $inconnueCodeSmellCritical = 0;
    private static $inconnueCodeSmellMajor = 0;
    private static $inconnueCodeSmellMinor = 1;
    private static $inconnueCodeSmellInfo = 43;
    private static $control = 'complet (100%)';
    private static $setup = '1739816022572';
    private static $modeCollecte = 'COLLECTE';
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $dateEnregistrement = '2025-02-17 19:13:59';
    private static $erreurCode200 = 'Erreur le code retour doit être 200.';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform('SET search_path TO ma_moulinette_test');

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform) {
            $sequence = 'ma_moulinette.repartition_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new RepartitionFixtures()]);
    }

    public function testDeleteRepartitionMavenKey(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => static::$mavenKey, ];

        // Appel de la méthode
        $notesRepository = $entityManager->getRepository(Repartition::class);
        $r = $notesRepository->deleteRepartitionMavenKey($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectOrUpdateRepartitionInitialUpdate(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = [
          'maven_key' => static::$mavenKey,
          'name' => static::$name,
          'bug_blocker' => static::$bugBlocker,
          'bug_critical' => static::$bugCritical,
          'bug_major' => static::$bugMajor,
          'bug_minor' => static::$bugMinor,
          'bug_info' => static::$bugInfo,
          'vulnerability_blocker' => static::$vulnerabilityBlocker,
          'vulnerability_critical' => static::$vulnerabilityCritical,
          'vulnerability_major' => static::$vulnerabilityMajor,
          'vulnerability_minor' => static::$vulnerabilityMinor,
          'vulnerability_info' => static::$vulnerabilityInfo,
          'code_smell_blocker' => static::$codeSmellBlocker,
          'code_smell_critical' => static::$codeSmellCritical,
          'code_smell_major' => static::$codeSmellMajor,
          'code_smell_minor' => static::$codeSmellMinor,
          'code_smell_info' => static::$codeSmellInfo,
          'setup' => static::$setup,
          'mode_collecte' => static::$modeCollecte,
          'utilisateur_collecte' => static::$utilisateurCollecte,
          'date_enregistrement'=> new \DateTimeImmutable(static::$dateEnregistrement)];

        // Appel de la méthode
        $repartitionRepository = $entityManager->getRepository(Repartition::class);
        $r = $repartitionRepository->SelectOrUpdateRepartitionInitial($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectOrUpdateRepartitionInitialInsert(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $date = new \DateTime('now');
        $timestamp = $date->getTimestamp();

        $map = [
          'maven_key' => static::$mavenKey,
          'name' => static::$name,
          'bug_blocker' => static::$bugBlocker,
          'bug_critical' => static::$bugCritical,
          'bug_major' => static::$bugMajor,
          'bug_minor' => static::$bugMinor,
          'bug_info' => static::$bugInfo,
          'vulnerability_blocker' => static::$vulnerabilityBlocker,
          'vulnerability_critical' => static::$vulnerabilityCritical,
          'vulnerability_major' => static::$vulnerabilityMajor,
          'vulnerability_minor' => static::$vulnerabilityMinor,
          'vulnerability_info' => static::$vulnerabilityInfo,
          'code_smell_blocker' => static::$codeSmellBlocker,
          'code_smell_critical' => static::$codeSmellCritical,
          'code_smell_major' => static::$codeSmellMajor,
          'code_smell_minor' => static::$codeSmellMinor,
          'code_smell_info' => static::$codeSmellInfo,
          'setup' => $timestamp,
          'mode_collecte' => static::$modeCollecte,
          'utilisateur_collecte' => static::$utilisateurCollecte,
          'date_enregistrement'=> new \DateTimeImmutable(static::$dateEnregistrement)];

        // Appel de la méthode
        $repartitionRepository = $entityManager->getRepository(Repartition::class);
        $r = $repartitionRepository->SelectOrUpdateRepartitionInitial($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testUpdateRepartition(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $date = new \DateTime('now');
        $timestamp = $date->getTimestamp();

        $map = [
          'maven_key' => static::$mavenKey,
          'name' => static::$name,
          'frontend' => static::$frontend,
          'frontend_bug_blocker' => static::$frontendBugBlocker,
          'frontend_bug_critical' => static::$frontendBugCritical,
          'frontend_bug_major' => static::$frontendBugMajor,
          'frontend_bug_minor' => static::$frontendBugMinor,
          'frontend_bug_info' => static::$frontendBugInfo,
          'frontend_vulnerability_blocker' => static::$frontendVulnerabilityBlocker,
          'frontend_vulnerability_critical' => static::$frontendVulnerabilityCritical,
          'frontend_vulnerability_major' => static::$frontendVulnerabilityMajor,
          'frontend_vulnerability_minor' => static::$frontendVulnerabilityMinor,
          'frontend_vulnerability_info' => static::$frontendVulnerabilityInfo,
          'frontend_code_smell_blocker' => static::$frontendCodeSmellBlocker,
          'frontend_code_smell_critical' => static::$frontendCodeSmellCritical,
          'frontend_code_smell_major' => static::$frontendCodeSmellMajor,
          'frontend_code_smell_minor' => static::$frontendCodeSmellMinor,
          'frontend_code_smell_info' => static::$frontendCodeSmellInfo,
          'backend' => static::$backend,
          'backend_bug_blocker' => static::$backendBugBlocker,
          'backend_bug_critical' => static::$backendBugCritical,
          'backend_bug_major' => static::$backendBugMajor,
          'backend_bug_minor' => static::$backendBugMinor,
          'backend_bug_info' => static::$backendBugInfo,
          'backend_vulnerability_blocker' => static::$backendVulnerabilityBlocker,
          'backend_vulnerability_critical' => static::$backendVulnerabilityCritical,
          'backend_vulnerability_major' => static::$backendVulnerabilityMajor,
          'backend_vulnerability_minor' => static::$backendVulnerabilityMinor,
          'backend_vulnerability_info' => static::$backendVulnerabilityInfo,
          'backend_code_smell_blocker' => static::$backendCodeSmellBlocker,
          'backend_code_smell_critical' => static::$backendCodeSmellCritical,
          'backend_code_smell_major' => static::$backendCodeSmellMajor,
          'backend_code_smell_minor' => static::$backendCodeSmellMinor,
          'backend_code_smell_info' => static::$backendCodeSmellInfo,
          'autre' => static::$autre,
          'autre_bug_blocker' => static::$autreBugBlocker,
          'autre_bug_critical' => static::$autreBugCritical,
          'autre_bug_major' => static::$autreBugMajor,
          'autre_bug_minor' => static::$autreBugMinor,
          'autre_bug_info' => static::$autreBugInfo,
          'autre_vulnerability_blocker' => static::$autreVulnerabilityBlocker,
          'autre_vulnerability_critical' => static::$autreVulnerabilityCritical,
          'autre_vulnerability_major' => static::$autreVulnerabilityMajor,
          'autre_vulnerability_minor' => static::$autreVulnerabilityMinor,
          'autre_vulnerability_info' => static::$autreVulnerabilityInfo,
          'autre_code_smell_blocker' => static::$autreCodeSmellBlocker,
          'autre_code_smell_critical' => static::$autreCodeSmellCritical,
          'autre_code_smell_major' => static::$autreCodeSmellMajor,
          'autre_code_smell_minor' => static::$autreCodeSmellMinor,
          'autre_code_smell_info' => static::$autreCodeSmellInfo,
          'inconnue' => static::$inconnue,
          'inconnue_bug_blocker' => static::$inconnueBugBlocker,
          'inconnue_bug_critical' => static::$inconnueBugCritical,
          'inconnue_bug_major' => static::$inconnueBugMajor,
          'inconnue_bug_minor' => static::$inconnueBugMinor,
          'inconnue_bug_info' => static::$inconnueBugInfo,
          'inconnue_vulnerability_blocker' => static::$inconnueVulnerabilityBlocker,
          'inconnue_vulnerability_critical' => static::$inconnueVulnerabilityCritical,
          'inconnue_vulnerability_major' => static::$inconnueVulnerabilityMajor,
          'inconnue_vulnerability_minor' => static::$inconnueVulnerabilityMinor,
          'inconnue_vulnerability_info' => static::$inconnueVulnerabilityInfo,
          'inconnue_code_smell_blocker' => static::$inconnueCodeSmellBlocker,
          'inconnue_code_smell_critical' => static::$inconnueCodeSmellCritical,
          'inconnue_code_smell_major' => static::$inconnueCodeSmellMajor,
          'inconnue_code_smell_minor' => static::$inconnueCodeSmellMinor,
          'inconnue_code_smell_info' => static::$inconnueCodeSmellInfo,
          'control' => static::$control,
          'setup' => $timestamp,
          'mode_collecte' => static::$modeCollecte,
          'utilisateur_collecte' => static::$utilisateurCollecte,
          'date_enregistrement'=> new \DateTimeImmutable(static::$dateEnregistrement)];

        // Appel de la méthode
        $repartitionRepository = $entityManager->getRepository(Repartition::class);
        $r = $repartitionRepository->updateRepartition($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // On se déconnecte pour éviter des problèmes de mémoires
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $entityManager->close();
        $entityManager = null;
    }

}
