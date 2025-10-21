<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Controller\Accueil;

use App\Repository\UtilisateurRepository;
use App\Repository\PropertiesRepository;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Properties;
use App\service\ClientService;
use Symfony\Bundle\SecurityBundle\Security;

use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Persistence\ObjectManager;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * [Description AccueilControllerTest]
 */
class AccueilControllerTest extends WebTestCase
{
    private static $aurelie = 'aurelie.petit-coeur@ma-moulinette.fr';
    private static $page = '/accueil';
    private static $calloutBorderAlert = '.alert-callout-border.alert';
    private static $http503a = "Erreur 503 - Le service est actuellement indisponible. Impossible d'établir une connexion.";
    private static $http503b = "Erreur 503 - L'adresse définit pour le proxy n'est pas correcte.";

    public function loadFixturesManuellement(ObjectManager $manager): void
    {
        $loader = new Loader();

        $loader->addFixture(new \App\DataFixtures\UtilisateurFixtures());
        $loader->addFixture(new \App\DataFixtures\PropertiesFixtures());
        $loader->addFixture(new \App\DataFixtures\MaMoulinetteFixtures());
        $loader->addFixture(new \App\DataFixtures\HistoriqueFixtures());

        $purger = new ORMPurger();
        $executor = new ORMExecutor($manager, $purger);

        $executor->execute($loader->getFixtures());
    }

    public function loadFixturesDiff(ObjectManager $manager): void
    {
        $loader = new Loader();

        $loader->addFixture(new \App\DataFixtures\UtilisateurFixtures());
        $loader->addFixture(new \App\DataFixtures\PropertiesDiffFixtures());
        $loader->addFixture(new \App\DataFixtures\MaMoulinetteFixtures());
        $loader->addFixture(new \App\DataFixtures\HistoriqueFixtures());

        $purger = new ORMPurger();
        $executor = new ORMExecutor($manager, $purger);

        $executor->execute($loader->getFixtures());
    }

    public function loadOldPropertiesFixtures(ObjectManager $manager): void
    {
        $loader = new Loader();

        $loader->addFixture(new \App\DataFixtures\UtilisateurFixtures());
        $loader->addFixture(new \App\DataFixtures\PropertiesOldFixtures());
        $loader->addFixture(new \App\DataFixtures\MaMoulinetteFixtures());
        $loader->addFixture(new \App\DataFixtures\HistoriqueFixtures());

        $purger = new ORMPurger();
        $executor = new ORMExecutor($manager, $purger);

        $executor->execute($loader->getFixtures());
    }

    public function testCountProjetSonarFlashError(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = $client->getContainer();

        $manager = $container->get('doctrine')->getManager();
        $this->loadFixturesManuellement($manager);

        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')->willReturn([
            'code' => 503,
            'erreur' => static::$http503a
        ]);
        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Utilisateur connecté (comme dans ton autre test)
        $userRepo = $container->get(\App\Repository\UtilisateurRepository::class);
        $testUser = $userRepo->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Appel à l'action
        $client->request('GET', static::$page);

        $this->assertSelectorExists(static::$calloutBorderAlert);
        $this->assertSelectorTextContains('.alert-callout-border', static::$http503a);
    }

    public function testCountProfilSonarFlashError(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = $client->getContainer();

        $manager = $container->get('doctrine')->getManager();
        $this->loadFixturesManuellement($manager);

        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
            // Premier appel - code 200
            [
                'code' => 200,
                'erreur' => null
            ],
            // Deuxième appel - code 503
            [
                'code' => 503,
                'erreur' => static::$http503b
            ]
        );

        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Utilisateur connecté (comme dans ton autre test)
        $userRepo = $container->get(\App\Repository\UtilisateurRepository::class);
        $testUser = $userRepo->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Appel à l'action
        $client->request('GET', static::$page);
        $this->assertSelectorExists(static::$calloutBorderAlert);
        $this->assertSelectorTextContains('.alert-callout-border', static::$http503b);
    }

    public function testCountProjetSonarCountsOnlyNonArchivedProjects(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = $client->getContainer();

        $manager = $container->get('doctrine')->getManager();
        $this->loadFixturesManuellement($manager);

        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')
        ->willReturn([
            'code' => 200,
            'json' => [
                'components' => [
                    ['project' => 'fr.domaine:mon-app-1'],
                    ['project' => 'fr.domaine:mon-app-2-SVN'],
                    ['project' => 'fr.domaine:mon-app-3'],
                    ['project' => 'fr.domaine:archived-SVN'],
                ]
            ]
        ]);

        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Utilisateur connecté (comme dans ton autre test)
        $userRepo = $container->get(\App\Repository\UtilisateurRepository::class);
        $testUser = $userRepo->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Exécution
        $crawler = $client->request('GET', static::$page);

        /**
         * 4 projets déclarés : 2 inclus, 2 exclus, 0 en BD donc +2 à mettre à jour.
         */
        $span = $crawler->filterXPath('//*[@id="js-nombre-projet"]');
        $this->assertEquals('0', $span->text(), 'Aucun projet en base de données.');
        $span = $crawler->filterXPath('//*[@id="js-moins"]');
        $this->assertEquals(1, $span->count());
        $span = $crawler->filterXPath('//*[@id="js-moins"]');
        $this->assertEquals('+2', $span->text());
        $span = $crawler->filterXPath('//*[@id="js-plus"]');
        $this->assertEquals(0, $span->count());

    }

    public function testCountProfilSonarCount(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = $client->getContainer();

        $manager = $container->get('doctrine')->getManager();
        $this->loadFixturesManuellement($manager);

        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
            // Premier appel - count = 1 projet
            [
                'code' => 200,
            'json' => [
                'components' => [
                    ['project' => 'fr.domaine:mon-app-1'],
                    ['project' => 'fr.domaine:mon-app-2-SVN'],
                    ['project' => 'fr.domaine:mon-app-3-SVN'],
                    ['project' => 'fr.domaine:archived-SVN'],
                ]
            ]
            ],
            // Deuxième appel -  count = 1 profil
            [
                'code' => 200,
                "json" => [
                    "profiles" => [
                        [
                            "key" => "AY7sNLkf7sdbiQdhRxT0",
                            "name" => "Ma-Petite-Entreprise V1.0.0 (2024)",
                            "language" => "css",
                            "languageName" => "CSS",
                            "isInherited" => false,
                            "isDefault" => true,
                            "activeRuleCount" => 24,
                            "activeDeprecatedRuleCount" => 0,
                            "rulesUpdatedAt" => "2024-04-17T13:26:18+0000",
                            "lastUsed" => "2024-09-25T09:38:26+0200",
                            "userUpdatedAt" => "2024-04-17T15:26:18+0200",
                            "isBuiltIn" => false,
                            "actions" => [
                                "edit" => false,
                                "setAsDefault" => false,
                                "copy" => false,
                                "associateProjects" => false,
                                "delete" => false,
                            ]
                        ]
                    ]
                ]
            ]
        );

        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Utilisateur connecté (comme dans ton autre test)
        $userRepo = $container->get(\App\Repository\UtilisateurRepository::class);
        $testUser = $userRepo->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Appel à l'action
        $crawler = $client->request('GET', static::$page);

        $span = $crawler->filterXPath('//*[@id="profil-bd"]');
        $this->assertEquals('0', $span->text(), 'Pas de profil en local.');
        $span = $crawler->filterXPath('//*[@id="profil-en-moins"]');
        $this->assertEquals('+1', $span->text(), "Un profil SonarQube n'est pas présent en local");
        $span = $crawler->filterXPath('//*[@id="profil-plus-moins"]');
        $this->assertEquals(0, $span->count(), 'Le noeud ne doit pas exister.');
    }

    public function testGetPropertiesEmpty(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = $client->getContainer();

        $manager = $container->get('doctrine')->getManager();
        $this->loadFixturesManuellement($manager);

        $params = $container->get(ParameterBagInterface::class);
        $httpClient = $container->get(Client::class);

        // Mock du ListeProjetRepository
        $mockPropertiesRepository = $this->createMock(PropertiesRepository::class);
        $mockPropertiesRepository->method('getProperties')
            ->willReturn(['code' => 200, 'request' => [] ]);
        static::getContainer()->set(PropertiesRepository::class, $mockPropertiesRepository);

        /** @var \Doctrine\ORM\EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject $mockEm */
        $mockEm = $this->createMock(EntityManagerInterface::class);
        $mockEm->method('getRepository')
                ->with(Properties::class)
                ->willReturn($mockPropertiesRepository);

        // Utilisateur connecté (comme dans ton autre test)
        $userRepo = $container->get(\App\Repository\UtilisateurRepository::class);
        $testUser = $userRepo->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // ⚠️ Instanciation manuelle du contrôleur après avoir injecté les mocks
        $controller = new \App\Controller\Accueil\AccueilController(
            $mockEm, $httpClient, $params,
        );

        // 🧪 Test de la méthode publique qui expose la méthode privée
        $properties = $controller->getGetProperties();

        $this->assertSame(0, $properties['projet_bd']);
        $this->assertSame(0, $properties['projet_sonar']);
        $this->assertSame(0, $properties['profil_bd']);
        $this->assertSame(0, $properties['profil_sonar']);

        $this->assertInstanceOf(\DateTimeImmutable::class, $properties['date_modification_projet']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $properties['date_modification_profil']);
    }

    public function testProjetMajProjetAndProfil(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = $client->getContainer();

        $manager = $container->get('doctrine')->getManager();
        $this->loadFixturesDiff($manager);

        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
            // Premier appel - code 200
            [
                'code' => 200,
                'erreur' => null
            ],
            // Deuxième appel -  count = 1 profil
            [
                'code' => 200,
                "json" => [
                    "profiles" => [
                        [
                            "key" => "AY7sNLkf7sdbiQdhRxT0",
                            "name" => "Ma-Petite-Entreprise V1.0.0 (2024)",
                            "language" => "css",
                            "languageName" => "CSS",
                            "isInherited" => false,
                            "isDefault" => true,
                            "activeRuleCount" => 24,
                            "activeDeprecatedRuleCount" => 0,
                            "rulesUpdatedAt" => "2024-04-17T13:26:18+0000",
                            "lastUsed" => "2024-09-25T09:38:26+0200",
                            "userUpdatedAt" => "2024-04-17T15:26:18+0200",
                            "isBuiltIn" => false,
                            "actions" => [
                                "edit" => false,
                                "setAsDefault" => false,
                                "copy" => false,
                                "associateProjects" => false,
                                "delete" => false,
                            ]
                        ]
                    ]
                ]
            ]
        );

        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);
        // Utilisateur connecté (comme dans ton autre test)
        $userRepo = $container->get(\App\Repository\UtilisateurRepository::class);
        $testUser = $userRepo->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Exécution
        $crawler = $client->request('GET', static::$page);

        $span = $crawler->filterXPath('//*[@id="js-nombre-projet"]');
        $this->assertEquals('1', $span->text(), '1 projet en local.');
        $span = $crawler->filterXPath('//*[@id="js-moins"]');
        $this->assertEquals('+999', $span->text(), '999 projets sur le serveur.');

        $span = $crawler->filterXPath('//*[@id="profil-bd"]');
        $this->assertEquals('1', $span->text(), 'Un profil est en local.');
        $span = $crawler->filterXPath('//*[@id="profil-en-moins"]');
        $this->assertEquals('+999', $span->text(), "999 sur le serveur.");
        $span = $crawler->filterXPath('//*[@id="profil-plus-moins"]');
        $this->assertEquals(0, $span->count(), 'Le noeud ne doit pas exister.');
    }

    public function testVersionElse(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = $client->getContainer();

        $manager = $container->get('doctrine')->getManager();
        $this->loadFixturesManuellement($manager);

        // Utilisateur connecté (comme dans ton autre test)
        $userRepo = $container->get(\App\Repository\UtilisateurRepository::class);
        $testUser = $userRepo->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Exécution
        $crawler = $client->request('GET', static::$page);

        // Si le message d'indisponibilité est affiché, on ignore le test
        if (
            $crawler->filter(static::$calloutBorderAlert)->count() > 0 &&
            str_contains($crawler->filter(static::$calloutBorderAlert)->text(), 'Le service est actuellement indisponible')
        ) {
            $this->markTestSkipped('SonarQube est indisponible, test ignoré.');
        }

        // Vérifications si SonarQube est disponible
        $this->assertSelectorExists('.alert-callout-border.warning');
        $this->assertSelectorTextContains('.alert-callout-border.warning', 'La base de données est en version');
    }

    public function testProjetsFavorisVersionFavorisVide()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = $client->getContainer();

        $manager = $container->get('doctrine')->getManager();
        $this->loadFixturesManuellement($manager);

        // Utilisateur connecté (comme dans ton autre test)
        $userRepo = $container->get(\App\Repository\UtilisateurRepository::class);
        $testUser = $userRepo->findOneByCourriel(static::$aurelie);
        $testUser->setPreference([
            'statut' => ['favori_projet' => false, 'favori_version' => false],
            'favori_projet' => [],
            'favori_version' => [],
        ]);
        $manager->flush();
        $client->loginUser($testUser);

        // Exécution
        $client->request('GET', static::$page);
        $this->assertSelectorExists('fieldset.js-vide');
        $this->assertSelectorTextContains('fieldset.js-vide legend.open-sans', 'Mes favoris (0).');
    }

    public function testProjetsFavoris()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = $client->getContainer();

        $manager = $container->get('doctrine')->getManager();
        $this->loadFixturesManuellement($manager);

        // Utilisateur connecté (comme dans ton autre test)
        $userRepo = $container->get(\App\Repository\UtilisateurRepository::class);
        $testUser = $userRepo->findOneByCourriel(static::$aurelie);
        $testUser->setPreference([
            'statut' => ['favori_projet' => true, 'favori_version' => false],
            'favori_projet' => ['fr.ma-petite-entreprise:ma-moulinette'],
            'favori_version' => [],
        ]);
        $manager->flush();
        $client->loginUser($testUser);

        // Exécution
        $client->request('GET', static::$page);
        $this->assertSelectorExists('fieldset.js-projets');
        $this->assertSelectorTextContains('fieldset.js-projets legend.open-sans', 'Mes projets favoris (1).');
    }

    public function testProjetsVersions()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = $client->getContainer();

        $manager = $container->get('doctrine')->getManager();
        $this->loadFixturesManuellement($manager);

        // Utilisateur connecté (comme dans ton autre test)
        $userRepo = $container->get(\App\Repository\UtilisateurRepository::class);
        $testUser = $userRepo->findOneByCourriel(static::$aurelie);
        $testUser->setPreference([
            'statut' => ['favori_projet' => false, 'favori_version' => true],
            'favori_projet' => ['fr.ma-petite-entreprise:ma-moulinette'],
            'favori_version' => [['fr.ma-petite-entreprise:ma-moulinette' => ["1.2.0-RELEASE","1.2.3-RELEASE"]]],
        ]);
        $manager->flush();
        $client->loginUser($testUser);

        // Exécution
        $client->request('GET', static::$page);
        $this->assertSelectorExists('fieldset.js-versions');
        $this->assertSelectorTextContains('fieldset.js-versions legend.open-sans', 'Mes projets par version (1).');
    }

    public function testIndexPageLoadsSuccessfully()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = $client->getContainer();

        $manager = $container->get('doctrine')->getManager();
        $this->loadFixturesManuellement($manager);

        $userRepo = $container->get(UtilisateurRepository::class);
        $testUser = $userRepo->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $crawler = $client->request('GET', static::$page);
        //dd($crawler);
        // 1. Titre principal
        $this->assertSelectorTextContains('main.grid-container > h1.h4.claire-hand', 'Informations et outils.');

        // 2. Nombre de projets SonarQube local (id js-nombre-projet)
        $this->assertSelectorTextSame('#js-nombre-projet', '0');

        // 3. Nombre de profils (id nombre-profil)
        $this->assertSelectorTextSame('#nombre-profil .stat', '0');

        // 4. Tags : projet et nombre de tags
        $this->assertSelectorTextSame('#js-tag-projet', '0');
        $this->assertSelectorTextSame('#js-tag-nombre', '0');

        // 5. Visibilité : public et privé
        $this->assertSelectorTextSame('#js-public', '0');
        $this->assertSelectorTextSame('#js-private', '0');

        // Vérification des callouts “Mes projets par favori”
        $callouts = $crawler->filterXPath(
        '//fieldset[legend[contains(normalize-space(.),"Mes projets favoris")]]'
        . '//div[contains(@class,"callout-information")]'
        );
        $this->assertCount(1, $callouts, 'Doit y avoir 1 blocs favoris sous le fieldset “Mes projets favoris”.');

        // 7. Pour chaque bloc favori, on recrée un Crawler et on fait nos assertions
        foreach ($callouts as $domElement) {
            $node = new \Symfony\Component\DomCrawler\Crawler($domElement);

            // Nom du projet
            $this->assertNotEmpty(
                $node->filter('.result-favori.nom-normal')->text(),
                'Chaque bloc doit afficher un nom de projet.'
            );

            // Version (ex : 1.2.3-RELEASE)
            $versionNode = $node->filterXPath(
                './/p[contains(normalize-space(.),"Version")]/span'
            );
            $this->assertMatchesRegularExpression(
                '/\d+\.\d+\.\d+-RELEASE/',
                $versionNode->text(),
                'La version doit suivre le format X.Y.Z-RELEASE.'
            );

            // Date (ex : 18/08/2024)
            $dateNode = $node->filterXPath(
                './/p[contains(normalize-space(.),"Date")]/span'
            );
            $this->assertMatchesRegularExpression(
                '/\d{2}\/\d{2}\/\d{4}/',
                $dateNode->text(),
                'La date doit être au format JJ/MM/AAAA.'
            );
        }

        // 8. Présence de la modale d’information sur les tags
        $this->assertSelectorExists('#modal-information-tag');
        $this->assertSelectorTextContains('#modal-information-tag h1', 'Information.');
        $this->assertSelectorTextContains('#modal-information-tag button#fermer-info-tag', 'Fermer');

        // Vérification finale
        $this->assertResponseIsSuccessful();
    }
}
