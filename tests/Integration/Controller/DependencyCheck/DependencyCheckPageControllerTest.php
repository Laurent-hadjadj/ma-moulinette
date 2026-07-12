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

namespace App\Tests\Integration\Controller\DependencyCheck;

use App\Entity\{DcCve, DcDependency, DcFinding, DcScan, ListeProjet, Utilisateur};
use App\Service\DependencyCheck\DcLatestVersionUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * MODIF 2026-05-08 : tests smoke pages DC.
 *
 * Couvre :
 *  - /dependency-check                          -> index liste projets
 *  - /dependency-check/dashboard                -> vue agrégée
 *  - /dependency-check/projet/{g}/{a}/{v}       -> vue detail projet (avec et sans donnees)
 *
 * Stratégie : login user admin existant, insertion fixture minimale (1 scan + 1 finding),
 * validation rendu HTTP 200 et mots-clés clés dans le HTML.
 */
class DependencyCheckPageControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private const DEPENDENCY_CHECK = '/dependency-check';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);

        // Login user existant : on prend emma (ROLE_UTILISATEUR) car admin a
        // ROLE_ADMIN qui n’hérite pas ROLE_UTILISATEUR (cf role_hierarchy security.yaml).
        // KernelBrowser::loginUser() bypass la verification actif=true.
        $user = $this->em->getRepository(Utilisateur::class)
            ->findOneBy(['courriel' => 'emma.durand@ma-moulinette.fr']);
        $this->assertNotNull($user, 'Fixtures Utilisateur doivent être chargées (emma.durand@ma-moulinette.fr).');

        /* MODIF 2026-05-10 : on doit donner a emma
         * un groupe_fonctionnel pour que les routes DC project-scoped (qui
         * vérifient l'ACL) acceptent les requêtes. Sans groupe, isProjectAccessible
         * retourne false et toute URL /projet/* renvoie un 403. */
        $user->setListeGroupeFonctionnel(['fr-test']);
        /* MODIF 2026-05-10 : le controller exige aussi
         * ROLE_SECURITY au niveau classe. ROLE_UTILISATEUR par défaut
         * d'emma ne suffit plus. */
        $user->setRoles(['ROLE_UTILISATEUR', 'ROLE_SECURITY']);
        $this->em->flush();

        $this->client->loginUser($user);

        // Cleanup tables DC pour isolation
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.dc_finding');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.dc_scan');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.dc_dependency');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.dc_cve');
        /* Le projet `fr.ma-moulinette:demo` doit aussi être dans liste_projet pour passer
         * l'ACL de MesProjets (filtre par tag LIKE prefix%). */
        $this->em->getConnection()->executeStatement(
            "DELETE FROM ma_moulinette.liste_projet WHERE maven_key IN ('fr.ma-moulinette:demo', 'fr.hors-perimetre:projet-externe')"
        );
    }

    public function testIndexReturns200WhenEmpty(): void
    {
        $this->client->request('GET', self::DEPENDENCY_CHECK);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Projets scannes', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Aucun projet', $this->client->getResponse()->getContent());
    }

    public function testIndexListsScannedProjects(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', self::DEPENDENCY_CHECK);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('demo', $body, 'Le projet "demo" doit apparaitre');
        $this->assertStringContainsString('1.0', $body, 'La version 1.0 doit apparaitre');
    }

    /* MODIF 2026-05-11 : la liste dc_index doit respecter le
     * périmètre groupe_fonctionnel de l'utilisateur. On insère 2 scans :
     * `fr.ma-moulinette:demo` (périmètre emma) et `fr.hors-perimetre:projet-externe` (hors
     * périmètre). La page ne doit afficher QUE `demo`. */
    public function testIndexFiltersOutOfScopeProjects(): void
    {
        $this->seedMinimalFixture();
        $this->seedOutOfScopeScan();

        $this->client->request('GET', self::DEPENDENCY_CHECK);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('demo', $body, 'Le projet du perimetre user doit apparaitre');
        $this->assertStringNotContainsString('other-app', $body, 'Le projet hors perimetre ne doit PAS apparaitre');
        $this->assertStringNotContainsString('fr.hors-perimetre', $body, 'Le group hors perimetre ne doit PAS apparaitre');
    }

    /* MODIF 2026-05-11 : utilisateur sans groupe_fonctionnel
     * -> liste vide + flash info. Vérifie le chemin null du helper
     * allowedMavenKeys(). */
    public function testIndexEmptyWhenUserHasNoGroups(): void
    {
        // On retire les groupes à emma (en plus du seed minimal qui existerait)
        $user = $this->em->getRepository(Utilisateur::class)
            ->findOneBy(['courriel' => 'emma.durand@ma-moulinette.fr']);
        $user->setListeGroupeFonctionnel([]);
        $this->em->flush();

        $this->seedMinimalFixture(); // scan existe en base, mais hors périmètre user

        $this->client->request('GET', self::DEPENDENCY_CHECK);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('demo', $body, 'Aucun projet ne doit etre liste sans groupe');
        $this->assertStringContainsString(
            'Aucun projet accessible',
            $body,
            'Le flash info doit indiquer perimetre vide'
        );
    }

    public function testDashboardReturns200WhenEmpty(): void
    {
        $this->client->request('GET', '/dependency-check/dashboard');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Dashboard cross-projets', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Aucune donnee', $this->client->getResponse()->getContent());
    }

    public function testDashboardShowsAggregatedDataWithFixture(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/dependency-check/dashboard');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        // Note : la section "CVE critiques cross-projets" filtre >= 2 projets,
        // donc avec 1 seul scan la CVE n’apparaît pas la. On valide par contre
        // les sections "top deps" et "top CWE" qui n'ont pas de seuil.
        $this->assertStringContainsString('CWE-400', $body, 'top CWE doit lister CWE-400');
        $this->assertStringContainsString('amqp-client', $body, 'top deps doit lister amqp-client');
    }

    /* MODIF 2026-05-10 : projet hors périmètre
     * groupe_fonctionnel -> 403 (anti-IDOR). x.y:unknown n'est pas dans
     * liste_projet donc MesProjets ne le retourne pas pour emma. */
    public function testProjetReturns403ForProjectOutsideAcl(): void
    {
        $this->client->request('GET', '/dependency-check/projet/x.y/unknown/0.0');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testProjetShowsScanDetails(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/dependency-check/projet/fr.ma-moulinette/demo/1.0');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('CVE-2023-46120', $body);
        $this->assertStringContainsString('amqp-client', $body);
        $this->assertStringContainsString('HIGH', $body);
        $this->assertStringContainsString('CWE-400', $body);
    }

    public function testProjetPdfReturnsValidPdf(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/dependency-check/projet/fr.ma-moulinette/demo/1.0/pdf');
        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'rapport-dc-demo-1.0.pdf',
            $response->headers->get('Content-Disposition')
        );

        // Magic bytes PDF : "%PDF-"
        $body = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $body, 'Le contenu doit commencer par les magic bytes PDF');
        $this->assertGreaterThan(1000, strlen($body), 'Un PDF valide fait au moins quelques KB');
    }

    /* MODIF 2026-05-10 : meme règle 403 sur le PDF
     * project-scoped (anti-IDOR). */
    public function testProjetPdfReturns403ForProjectOutsideAcl(): void
    {
        $this->client->request('GET', '/dependency-check/projet/x.y/unknown/0.0/pdf');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    /* MODIF 2026-05-10 : projet dans le périmètre ACL
     * mais aucun scan pour cette version -> 200 + page rendue avec
     * structure '---' (cf. template projet.html.twig). */
    public function testProjetReturns200WithDashesWhenScanMissing(): void
    {
        $this->seedMinimalFixture(); // insère fr.ma-moulinette:demo dans liste_projet

        $this->client->request('GET', '/dependency-check/projet/fr.ma-moulinette/demo/9.9.9-INEXISTANT');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('---', $body, 'La page doit afficher --- en l\'absence de scan');
        $this->assertStringContainsString('Aucune CVE détectée', $body);
    }

    /**
     * Insère une fixture minimale : 1 scan, 1 dep, 1 cve, 1 finding.
     * MODIF 2026-05-10 : insère aussi un row
     * ListeProjet pour que `fr.ma-moulinette:demo` soit dans le périmètre ACL d'emma
     * (groupe `fr-test` -> tag prefix `fr-test%`).
     */
    private function seedMinimalFixture(): void
    {
        $listeProjet = new ListeProjet(
            mavenKey: 'fr.ma-moulinette:demo',
            name: 'demo',
            visibility: 'public',
            tags: ['fr-test-acl']
        );
        $this->em->persist($listeProjet);

        $cve = (new DcCve())
            ->setCveId('CVE-2023-46120')
            ->setSource('NVD')
            ->setSeverity(DcCve::SEVERITY_HIGH)
            ->setCvssV3Score('7.5')
            ->setCvssV3Severity('HIGH')
            ->setCvssV3AttackVector('NETWORK')
            ->setCwes(['CWE-400'])
            ->setDescription('Test CVE description for amqp-client.')
            ->setCveReferences([['source' => 'NVD', 'url' => 'http://x', 'name' => 'ADV']]);
        $this->em->persist($cve);

        $dep = (new DcDependency())
            ->setSha1(str_repeat('a', 40))
            ->setSha256(str_repeat('b', 64))
            ->setFileName('amqp-client-5.9.0.jar')
            ->setPkgCoordinates('pkg:maven/com.rabbitmq/amqp-client@5.9.0')
            ->setVendor('com.rabbitmq')
            ->setProduct('amqp-client')
            ->setVersion('5.9.0');
        $this->em->persist($dep);

        $scan = (new DcScan())
            ->setMavenKey('fr.ma-moulinette:demo')
            ->setProjectGroup('fr.ma-moulinette')
            ->setProjectArtifact('demo')
            ->setProjectVersion('1.0')
            ->setScanDate(new \DateTimeImmutable('2026-05-08T08:00:00+02:00'))
            ->setEngineVersion('12.2.0')
            ->setReportSchema('1.1')
            ->setDepCountTotal(50)
            ->setDepCountVulnerable(1)
            ->setCveCountHigh(1)
            ->setCveCountTotal(1);
        $this->em->persist($scan);
        $this->em->flush();

        $finding = (new DcFinding())
            ->setScan($scan)->setDependency($dep)->setCve($cve)
            ->setSeverityAtScan('HIGH')->setCvssAtScan('7.5');
        $this->em->persist($finding);
        $this->em->flush();

        // MODIF 2026-05-15 : recompute des flags is_latest_*
        // pour que listLatestPerCoupleForView retourne ce scan (sinon
        // is_latest_overall=is_latest_release=FALSE par defaut -> scan
        // invisible dans les agregats depuis le lot [dc-latest-version-filter]).
        $updater = static::getContainer()->get(DcLatestVersionUpdater::class);
        $updater->recomputeFor('fr.ma-moulinette', 'demo');
    }

    /**
     * MODIF 2026-05-11 : insère un scan d'un projet HORS du
     * périmètre groupe_fonctionnel d'emma (groupe `fr-test`). Tag
     * `autre-groupe-acl` ne matche pas le prefix `fr-test%`, donc le projet
     * sera invisible pour emma via MesProjets::liste().
     */
    private function seedOutOfScopeScan(): void
    {
        $listeProjet = new ListeProjet(
            mavenKey: 'fr.hors-perimetre:projet-externe',
            name: 'other-app',
            visibility: 'public',
            tags: ['autre-groupe-acl']
        );
        $this->em->persist($listeProjet);

        $scan = (new DcScan())
            ->setMavenKey('fr.hors-perimetre:projet-externe')
            ->setProjectGroup('fr.hors-perimetre')
            ->setProjectArtifact('other-app')
            ->setProjectVersion('2.0')
            ->setScanDate(new \DateTimeImmutable('2026-05-09T08:00:00+02:00'))
            ->setEngineVersion('12.2.0')
            ->setReportSchema('1.1')
            ->setDepCountTotal(10)
            ->setDepCountVulnerable(0)
            ->setCveCountTotal(0);
        $this->em->persist($scan);
        $this->em->flush();

        // MODIF 2026-05-15 : recompute is_latest_* idem seedMinimalFixture.
        $updater = static::getContainer()->get(DcLatestVersionUpdater::class);
        $updater->recomputeFor('fr.hors-perimetre', 'projet-externe');
    }

    /* ====================================================================
     * MODIF 2026-05-16 : tests history + executive.
     * Couvre les 2 routes orphelines en 9 cas (ACL 403, données vides,
     * happy path, multi-scans, diff intra/inter-versions).
     * ==================================================================== */

    public function testHistoryReturns403ForProjectOutsideAcl(): void
    {
        $this->client->request('GET', '/dependency-check/projet/x.y/unknown/history');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testHistoryReturns200WhenNoScans(): void
    {
        // Projet dans ACL (liste_projet) mais 0 scan en base
        $listeProjet = new ListeProjet(
            mavenKey: 'fr.ma-moulinette:demo',
            name: 'demo',
            visibility: 'public',
            tags: ['fr-test-acl']
        );
        $this->em->persist($listeProjet);
        $this->em->flush();

        $this->client->request('GET', '/dependency-check/projet/fr.ma-moulinette/demo/history');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Historique', $body);
        $this->assertStringContainsString('Aucun scan trouvé pour ce projet', $body);
    }

    public function testHistoryReturns200WithSingleScan(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/dependency-check/projet/fr.ma-moulinette/demo/history');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Scans cumulés', $body);
        $this->assertStringContainsString('Versions distinctes', $body);
        $this->assertStringContainsString('Période couverte', $body);
        // 1 scan => 1 version distincte, et la version 1.0 doit apparaître dans le tableau
        $this->assertStringContainsString('1.0', $body);
    }

    public function testHistoryReturns200WithMultipleScansAndDistinctVersions(): void
    {
        $this->seedMinimalFixture();
        // Ajoute un 2e scan d'une autre version : v2.0 plus récent
        $this->seedAdditionalScan('fr.ma-moulinette', 'demo', '2.0', '2026-05-12T08:00:00+02:00', critical: 1, high: 2);

        $this->client->request('GET', '/dependency-check/projet/fr.ma-moulinette/demo/history');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        // Versions distinctes >= 2 (le compteur est dans une cell font-size-15)
        $this->assertStringContainsString('1.0', $body);
        $this->assertStringContainsString('2.0', $body);
        // Section chart présente
        $this->assertStringContainsString('dc-history-chart', $body);
    }

    public function testHistoryShowsDeltasForFollowupScans(): void
    {
        $this->seedMinimalFixture(); // v1.0 : critical=0, high=1
        // 2e scan v1.0 ré-scané avec plus de CVE => delta_high doit être calculé
        $this->seedAdditionalScan('fr.ma-moulinette', 'demo', '1.0', '2026-05-14T08:00:00+02:00', critical: 2, high: 3);

        $this->client->request('GET', '/dependency-check/projet/fr.ma-moulinette/demo/history');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        // Au moins 2 scans
        $this->assertStringContainsString('Scans cumulés', $body);
        // Les libellés de section "Delta" sont exposés dans le callout d'aide
        $this->assertStringContainsString('Delta', $body);
    }

    public function testExecutiveReturns403ForProjectOutsideAcl(): void
    {
        $this->client->request('GET', '/dependency-check/projet/x.y/unknown/0.0/executive');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testExecutiveReturns200WhenScanMissing(): void
    {
        // ACL OK (projet en liste_projet) mais pas de scan pour la version ciblée
        $listeProjet = new ListeProjet(
            mavenKey: 'fr.ma-moulinette:demo',
            name: 'demo',
            visibility: 'public',
            tags: ['fr-test-acl']
        );
        $this->em->persist($listeProjet);
        $this->em->flush();

        $this->client->request('GET', '/dependency-check/projet/fr.ma-moulinette/demo/9.9.9-INEXISTANT/executive');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Aucun scan trouvé pour ce projet', $body);
    }

    public function testExecutiveReturns200WithFindings(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/dependency-check/projet/fr.ma-moulinette/demo/1.0/executive');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Contexte', $body);
        $this->assertStringContainsString('CVE-2023-46120', $body);
        $this->assertStringContainsString('Plan de remédiation', $body);
        // En l'absence d'antériorité (1 seul scan), la section intra-version
        // affiche le callout "Aucun re-scan antérieur"
        $this->assertStringContainsString('Aucun re-scan antérieur', $body);
    }

    public function testExecutiveShowsSameVersionDiffWhenPreviousScanExists(): void
    {
        $this->seedMinimalFixture(); // v1.0 du 2026-05-08
        // Re-scan v1.0 plus récent
        $this->seedAdditionalScan('fr.ma-moulinette', 'demo', '1.0', '2026-05-14T08:00:00+02:00', critical: 0, high: 1);

        $this->client->request('GET', '/dependency-check/projet/fr.ma-moulinette/demo/1.0/executive');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        // Au moins une des 2 branches (success "Pas de changement" si findings identiques,
        // ou diff non-vide). On vérifie que la section a été rendue.
        $this->assertStringContainsString('Comparaison vs scan précédent', $body);
    }

    public function testExecutiveShowsAnyVersionDiffWhenPreviousVersionExists(): void
    {
        $this->seedMinimalFixture(); // v1.0
        // v2.0 plus récent, la requête cible v2.0 -> previous_any = v1.0
        $this->seedAdditionalScan('fr.ma-moulinette', 'demo', '2.0', '2026-05-14T08:00:00+02:00', critical: 0, high: 0);

        $this->client->request('GET', '/dependency-check/projet/fr.ma-moulinette/demo/2.0/executive');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Comparaison vs version antérieure', $body);
        $this->assertStringContainsString('1.0', $body, 'La version précédente doit apparaître');
    }

    /* ====================================================================
     * MODIF 2026-05-16 : tests kpi + comparer
     * + mutualisables. Couvre les 3 routes analytics multi-projets
     * en ~10 cas (vide, fixture, filtres vue/sort/dir, ACL).
     * ==================================================================== */

    public function testKpiReturns200WhenEmpty(): void
    {
        $this->client->request('GET', '/dependency-check/kpi');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        // Garde nb_scans == 0 du template : callout "Aucun projet" masque les sections.
        $this->assertStringContainsString('Aucun projet dans votre périmètre', $body);
        // Toggle prod/dev toujours rendu (avant la garde)
        $this->assertStringContainsString('Pilotage (prod)', $body);
    }

    public function testKpiReturns200WithFixtureShowsScoreAndTopLists(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/dependency-check/kpi');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Top 5 apps les plus à risque', $body);
        // L'app demo doit apparaître dans le top liste (Top 5 risque ou sain)
        $this->assertStringContainsString('demo', $body);
    }

    public function testKpiAcceptsVueDevFilter(): void
    {
        $this->seedMinimalFixture(); // v1.0 = release stable -> hors cohorte 'dev'

        $this->client->request('GET', '/dependency-check/kpi?vue=dev');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        // Toggle dev sélectionné dans le template
        $this->assertStringContainsString('Suivi (dev)', $body);
        // Vue dev + uniquement une release stable -> cohorte vide -> garde "Aucun projet"
        $this->assertStringContainsString('Aucun projet dans votre périmètre', $body);
    }

    public function testComparerReturns200WithoutApps(): void
    {
        $this->client->request('GET', '/dependency-check/comparer');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Comparer des applications', $body);
        // Branche < 2 apps : message "Sélectionnez au moins 2 applications"
        $this->assertStringContainsString('Sélectionnez au moins 2 applications', $body);
        // Pas de tableau comparé
        $this->assertStringNotContainsString('Synthèse comparée', $body);
    }

    public function testComparerReturns200WithOneAppKeepsSelectorOnly(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/dependency-check/comparer?apps%5B%5D=fr.ma-moulinette%3Ademo%3A1.0');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        // 1 app seule => raw_app_keys prérempli mais branche "Sélectionnez au moins 2"
        $this->assertStringContainsString('Sélectionnez au moins 2 applications', $body);
        $this->assertStringNotContainsString('Synthèse comparée', $body);
    }

    public function testComparerReturns200WithTwoAppsShowsComparison(): void
    {
        $this->seedMinimalFixture(); // fr.ma-moulinette:demo:1.0
        // 2e app dans le périmètre ACL : ajoute fr-test-acl tag + nouveau scan
        $listeProjet2 = new ListeProjet(
            mavenKey: 'fr.ma-moulinette:bravo',
            name: 'bravo',
            visibility: 'public',
            tags: ['fr-test-acl']
        );
        $this->em->persist($listeProjet2);
        $this->em->flush();
        $this->seedAdditionalScan('fr.ma-moulinette', 'bravo', '1.0', '2026-05-12T08:00:00+02:00', high: 1);

        $this->client->request(
            'GET',
            '/dependency-check/comparer?apps%5B%5D=fr.ma-moulinette%3Ademo%3A1.0&apps%5B%5D=fr.ma-moulinette%3Abravo%3A1.0'
        );
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Synthèse comparée', $body);
        $this->assertStringContainsString('demo', $body);
        $this->assertStringContainsString('bravo', $body);
    }

    public function testComparerExcludesAppsOutsideAcl(): void
    {
        $this->seedMinimalFixture();
        $this->seedOutOfScopeScan(); // fr.hors-perimetre:projet-externe:2.0 hors ACL emma

        // emma sélectionne 2 apps : demo (dans ACL) + other-app (hors ACL).
        // Comportement attendu : other-app filtré -> reste 1 app -> branche < 2.
        $this->client->request(
            'GET',
            '/dependency-check/comparer?apps%5B%5D=fr.ma-moulinette%3Ademo%3A1.0&apps%5B%5D=fr.hors-perimetre%3Aprojet-externe%3A2.0'
        );
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('other-app', $body, 'App hors ACL ne doit pas apparaître');
        $this->assertStringContainsString('Sélectionnez au moins 2 applications', $body);
    }

    public function testMutualisablesReturns200WhenEmpty(): void
    {
        $this->client->request('GET', '/dependency-check/mutualisables');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Mutualisations possibles', $body);
    }

    public function testMutualisablesReturns200WithFixture(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/dependency-check/mutualisables');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Mutualisations possibles', $body);
        // Une seule app => 0 mutualisable (seuil >= 2 apps), mais la page rend OK
    }

    /* MODIF 2026-05-16 : sort/dir/page gérés côté client par
     * DataTables. Le contrôleur les ignore (plus de whitelist server-side).
     * Le test vérifie que ces params ne provoquent pas d'erreur (200 attendu). */
    public function testMutualisablesIgnoresSortDirPageParams(): void
    {
        $this->seedMinimalFixture();

        // sort/dir/page passés en query string : ignorés par le contrôleur, 200 attendu
        $this->client->request('GET', '/dependency-check/mutualisables?sort=severity&dir=asc&page=1');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        // Valeurs invalides : toujours ignorées, 200 attendu
        $this->client->request('GET', '/dependency-check/mutualisables?sort=hack&dir=hack');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    /* ====================================================================
     * MODIF 2026-05-16 : tests dashboard
     * branches profondes (filtres vue/socle/groupe). 5 cas couvrant les
     * combinaisons de filtres query string non testées par les smoke 5A.
     * ==================================================================== */

    public function testDashboardAcceptsVueDevFilter(): void
    {
        // Seed une app avec version SNAPSHOT pour qu'elle apparaisse en cohorte dev
        $listeProjet = new ListeProjet(
            mavenKey: 'fr.ma-moulinette:projet-wip',
            name: 'wip',
            visibility: 'public',
            tags: ['fr-test-acl']
        );
        $this->em->persist($listeProjet);
        $this->em->flush();
        $this->seedAdditionalScan('fr.ma-moulinette', 'projet-wip', '1.0-SNAPSHOT', '2026-05-14T08:00:00+02:00', high: 1);

        $this->client->request('GET', '/dependency-check/dashboard?vue=dev');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        // Toggle dev sélectionné dans le template
        $this->assertStringContainsString('Suivi (dev)', $body);
    }

    public function testDashboardAcceptsValidSocleFilter(): void
    {
        // Seed 2 apps partageant le même parent_label/version
        $listeProjet1 = new ListeProjet(
            mavenKey: 'fr.ma-moulinette:projet-a',
            name: 'alpha',
            visibility: 'public',
            tags: ['fr-test-acl']
        );
        $listeProjet2 = new ListeProjet(
            mavenKey: 'fr.ma-moulinette:projet-b',
            name: 'bravo',
            visibility: 'public',
            tags: ['fr-test-acl']
        );
        $this->em->persist($listeProjet1);
        $this->em->persist($listeProjet2);
        $this->em->flush();
        $this->seedScanWithSocle('fr.ma-moulinette', 'projet-a', '1.0', '2026-05-10T08:00:00+02:00', 'springboot-config', '2.7.0');
        $this->seedScanWithSocle('fr.ma-moulinette', 'projet-b', '1.0', '2026-05-10T08:00:00+02:00', 'springboot-config', '2.7.0');

        $this->client->request('GET', '/dependency-check/dashboard?socle=springboot-config%402.7.0');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('springboot-config', $body);
    }

    public function testDashboardIgnoresInvalidSocleFilter(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/dependency-check/dashboard?socle=inexistant%40999.0');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        // Le filtre invalide est ignoré : la fixture reste visible
        $this->assertStringContainsString('demo', $body);
    }

    public function testDashboardAcceptsValidGroupeFilter(): void
    {
        // emma a déjà le groupe 'fr-test' (cf. setUp)
        $this->seedMinimalFixture();

        $this->client->request('GET', '/dependency-check/dashboard?groupe=fr-test');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('demo', $body);
    }

    public function testDashboardIgnoresInvalidGroupeFilter(): void
    {
        $this->seedMinimalFixture();

        // emma n'a PAS le groupe 'hacker-group' -> filtre ignoré (tentative IDOR)
        $this->client->request('GET', '/dependency-check/dashboard?groupe=hacker-group');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();
        // Le périmètre user normal s'applique, demo est visible
        $this->assertStringContainsString('demo', $body);
    }

    /**
     * MODIF 2026-05-16 : helper scan + socle.
     * Sert aux tests qui ont besoin d'apps partageant un parent_label.
     */
    private function seedScanWithSocle(
        string $group,
        string $artifact,
        string $version,
        string $scanDate,
        string $parentLabel,
        string $parentVersion,
    ): void {
        $scan = (new DcScan())
            ->setMavenKey($group . ':' . $artifact)
            ->setProjectGroup($group)
            ->setProjectArtifact($artifact)
            ->setProjectVersion($version)
            ->setScanDate(new \DateTimeImmutable($scanDate))
            ->setEngineVersion('12.2.0')
            ->setReportSchema('1.1')
            ->setDepCountTotal(50)
            ->setDepCountVulnerable(0)
            ->setCveCountTotal(0)
            ->setParentLabel($parentLabel)
            ->setParentVersion($parentVersion);
        $this->em->persist($scan);
        $this->em->flush();

        $updater = static::getContainer()->get(DcLatestVersionUpdater::class);
        $updater->recomputeFor($group, $artifact);
    }

    /**
     * MODIF 2026-05-16 : helper pour ajouter
     * un scan supplémentaire (même projet ou autre version) sans relancer
     * tout seedMinimalFixture.
     */
    private function seedAdditionalScan(
        string $group,
        string $artifact,
        string $version,
        string $scanDate,
        int $critical = 0,
        int $high = 0,
        int $medium = 0,
        int $low = 0,
    ): void {
        $scan = (new DcScan())
            ->setMavenKey($group . ':' . $artifact)
            ->setProjectGroup($group)
            ->setProjectArtifact($artifact)
            ->setProjectVersion($version)
            ->setScanDate(new \DateTimeImmutable($scanDate))
            ->setEngineVersion('12.2.0')
            ->setReportSchema('1.1')
            ->setDepCountTotal(50)
            ->setDepCountVulnerable($critical + $high + $medium + $low > 0 ? 1 : 0)
            ->setCveCountCritical($critical)
            ->setCveCountHigh($high)
            ->setCveCountMedium($medium)
            ->setCveCountLow($low)
            ->setCveCountTotal($critical + $high + $medium + $low);
        $this->em->persist($scan);
        $this->em->flush();

        // Recompute flags is_latest_*
        $updater = static::getContainer()->get(DcLatestVersionUpdater::class);
        $updater->recomputeFor($group, $artifact);
    }
}
