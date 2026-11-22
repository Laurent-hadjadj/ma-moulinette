<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Tests\Integration\Controller\NpmAudit;

use App\Entity\{ListeProjet, NpmDependency, NpmFinding, NpmScan, NpmVulnerability, Utilisateur};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests smoke pages npm audit — même stratégie que
 * DependencyCheckPageControllerTest (login user existant, fixture minimale,
 * validation rendu HTTP + mots-clés clés dans le HTML).
 */
class NpmAuditPageControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private const NPM_AUDIT = '/npm-audit';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->em->getRepository(Utilisateur::class)
            ->findOneBy(['courriel' => 'emma.durand@ma-moulinette.fr']);
        $this->assertNotNull($user, 'Fixtures Utilisateur doivent être chargées (emma.durand@ma-moulinette.fr).');

        $user->setListeGroupeFonctionnel(['fr-test']);
        $user->setRoles(['ROLE_UTILISATEUR', 'ROLE_SECURITY']);
        $this->em->flush();

        $this->client->loginUser($user);

        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_finding');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_scan');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_dependency');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_vulnerability');
        $this->supprimerProjetsDeTest();
    }

    protected function tearDown(): void
    {
        $this->supprimerProjetsDeTest();
        parent::tearDown();
    }

    private function supprimerProjetsDeTest(): void
    {
        $this->em->getConnection()->executeStatement(
            "DELETE FROM ma_moulinette.liste_projet WHERE maven_key IN ("
            . "'fr.ma-moulinette:npm-demo', 'fr.hors-perimetre:npm-externe')"
        );
    }

    public function testIndexReturns200WhenEmpty(): void
    {
        $this->client->request('GET', self::NPM_AUDIT);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Paquets scannés', $body);
        $this->assertStringContainsString('Aucun paquet', $body);
    }

    public function testIndexListsScannedProjects(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', self::NPM_AUDIT);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('npm-demo', $body);
        $this->assertStringContainsString('1.0.0', $body);
    }

    public function testIndexFiltersOutOfScopeProjects(): void
    {
        $this->seedMinimalFixture();
        $this->seedOutOfScopeScan();

        $this->client->request('GET', self::NPM_AUDIT);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('npm-demo', $body);
        $this->assertStringNotContainsString('npm-externe', $body);
        $this->assertStringNotContainsString('fr.hors-perimetre', $body);
    }

    public function testIndexHidesCrossProjectLinkWithoutAnalyticsRole(): void
    {
        $this->client->request('GET', self::NPM_AUDIT);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = (string) $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('bouton-ouvrir-npm-audit-paquets', $body);
    }

    public function testIndexShowsCrossProjectLinkWithAnalyticsRole(): void
    {
        $this->grantAnalyticsRole();

        $this->client->request('GET', self::NPM_AUDIT);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('bouton-ouvrir-npm-audit-paquets', $body);
    }

    public function testPaquetsReturns403WithoutAnalyticsRole(): void
    {
        $this->client->request('GET', '/npm-audit/paquets');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testPaquetsReturns200WithAnalyticsRoleAndShowsAggregatedData(): void
    {
        $this->grantAnalyticsRole();
        $this->seedMinimalFixture();

        $this->client->request('GET', '/npm-audit/paquets');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('body-parser', $body);
        // seedMinimalFixture() : 1 seul projet -> 1 projet touché pour body-parser.
        $this->assertStringContainsString('HIGH', $body);
    }

    /**
     * Ré-authentifie avec le rôle analytics en plus — `loginUser()` fige le
     * jeton de sécurité au moment de l'appel : muter les rôles en base
     * après coup (sans relogin) ne serait pas pris en compte par le firewall
     * sur les requêtes suivantes du client de test.
     */
    private function grantAnalyticsRole(): void
    {
        $user = $this->em->getRepository(Utilisateur::class)
            ->findOneBy(['courriel' => 'emma.durand@ma-moulinette.fr']);
        $user->setRoles(['ROLE_UTILISATEUR', 'ROLE_SECURITY', 'ROLE_SECURITY_ANALYTICS']);
        $this->em->flush();
        $this->client->loginUser($user);
    }

    public function testProjetReturns403ForProjectOutsideAcl(): void
    {
        $this->client->request('GET', '/npm-audit/projet/x.y/unknown/0.0.0');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testProjetShowsScanDetails(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/npm-audit/projet/fr.ma-moulinette/npm-demo/1.0.0');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('GHSA-27R3-JXMH-P828', $body);
        $this->assertStringContainsString('body-parser', $body);
        $this->assertStringContainsString('HIGH', $body);
        $this->assertStringContainsString('CWE-248', $body);
    }

    public function testProjetShowsPackageSummaryTableAggregatedPerPackage(): void
    {
        $this->seedFixtureWithTwoFindingsSamePackage();

        $this->client->request('GET', '/npm-audit/projet/fr.ma-moulinette/npm-demo/1.0.0');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = (string) $this->client->getResponse()->getContent();

        $this->assertStringContainsString('Paquets concernés (1)', $body);
        // cvss_max : le plus élevé des 2 findings (5.0 et 8.0) -> 8.
        $this->assertStringContainsString('8', $body);
        // cwe_count : 2 CWE distincts cumulés (CWE-1 + CWE-2) -> badge "2".
        $this->assertStringContainsString('<span class="badge-cve">2</span>', $body);
        // scan.vulnCountTotal=2 (metadata npm) != 1 paquet réellement concerné -> phrase de clarification affichée.
        $this->assertStringContainsString('npm audit annonce par ailleurs', $body);
        $this->assertStringContainsString('Détail par avis de sécurité (2)', $body);
    }

    private function seedFixtureWithTwoFindingsSamePackage(): void
    {
        $listeProjet = new ListeProjet(
            mavenKey: 'fr.ma-moulinette:npm-demo',
            name: 'npm-demo',
            visibility: 'public',
            tags: ['fr-test-acl']
        );
        $this->em->persist($listeProjet);

        $dep = (new NpmDependency())->setName('body-parser');
        $this->em->persist($dep);

        $vuln1 = (new NpmVulnerability())
            ->setAdvisoryId('GHSA-AAAA-0000-0000')
            ->setTitle('Vuln 1')->setSeverity(\App\Entity\NpmVulnerability::SEVERITY_MODERATE)
            ->setCvssScore('5.0')->setCwes(['CWE-1']);
        $vuln2 = (new NpmVulnerability())
            ->setAdvisoryId('GHSA-BBBB-0000-0000')
            ->setTitle('Vuln 2')->setSeverity(\App\Entity\NpmVulnerability::SEVERITY_HIGH)
            ->setCvssScore('8.0')->setCwes(['CWE-2']);
        $this->em->persist($vuln1);
        $this->em->persist($vuln2);

        $scan = (new NpmScan())
            ->setProjectGroup('fr.ma-moulinette')
            ->setProjectArtifact('npm-demo')
            ->setProjectVersion('1.0.0')
            ->setScanDate(new \DateTimeImmutable('2026-11-20T08:00:00+01:00'))
            ->setVulnCountModerate(1)->setVulnCountHigh(1)->setVulnCountTotal(2);
        $this->em->persist($scan);
        $this->em->flush();

        $f1 = (new NpmFinding())
            ->setScan($scan)->setDependency($dep)->setVulnerability($vuln1)
            ->setSeverityAtScan('moderate')->setCvssAtScan('5.0')->setIsDirect(true)->setIsProd(true);
        $f2 = (new NpmFinding())
            ->setScan($scan)->setDependency($dep)->setVulnerability($vuln2)
            ->setSeverityAtScan('high')->setCvssAtScan('8.0')->setIsDirect(true)->setIsProd(true);
        $this->em->persist($f1);
        $this->em->persist($f2);
        $this->em->flush();
    }

    public function testProjetReturns200WithWarningWhenScanMissing(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/npm-audit/projet/fr.ma-moulinette/npm-demo/9.9.9-INEXISTANT');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Aucun scan npm audit trouvé', $body);
        $this->assertStringContainsString('Aucune vulnérabilité détectée', $body);
    }

    public function testProjetPdfReturnsValidPdf(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/npm-audit/projet/fr.ma-moulinette/npm-demo/1.0.0/pdf');
        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'rapport-npm-audit-npm-demo-1.0.0.pdf',
            $response->headers->get('Content-Disposition')
        );

        // Magic bytes PDF : "%PDF-"
        $body = (string) $response->getContent();
        $this->assertStringStartsWith('%PDF-', $body, 'Le contenu doit commencer par les magic bytes PDF');
        $this->assertGreaterThan(1000, strlen($body), 'Un PDF valide fait au moins quelques KB');
    }

    public function testProjetPdfReturns403ForProjectOutsideAcl(): void
    {
        $this->client->request('GET', '/npm-audit/projet/x.y/unknown/0.0.0/pdf');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testProjetPdfRedirectsWhenScanMissing(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/npm-audit/projet/fr.ma-moulinette/npm-demo/9.9.9-INEXISTANT/pdf');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString(
            '/npm-audit/projet/fr.ma-moulinette/npm-demo/9.9.9-INEXISTANT',
            (string) $this->client->getResponse()->headers->get('Location')
        );
    }

    public function testDashboardReturns403ForProjectOutsideAcl(): void
    {
        $this->client->request('GET', '/npm-audit/dashboard/x.y/unknown/0.0.0');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testDashboardShowsSeverityChartData(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/npm-audit/dashboard/fr.ma-moulinette/npm-demo/1.0.0');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('npm-audit-chart-data', $body);
        $this->assertStringContainsString('"high":1', $body);
    }

    public function testDashboardShowsOriginDirectAndTopPackagesChartData(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/npm-audit/dashboard/fr.ma-moulinette/npm-demo/1.0.0');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = (string) $this->client->getResponse()->getContent();

        // isProd=null (prodOnly jamais croisé) -> "unknown" (Indéterminé), 3ᵉ position.
        // json_encode échappe les caractères non-ASCII par défaut (\uXXXX) : on
        // teste le préfixe ASCII plutôt que le libellé accentué complet.
        $this->assertStringContainsString('"origin":{"labels":["Production","Dev","Ind', $body);
        $this->assertStringContainsString('"high":[0,0,1]', $body);

        // isDirect=false -> "transitive", 2ᵉ position.
        $this->assertStringContainsString('"direct":{"labels":["Direct","Transitif"]', $body);

        $this->assertStringContainsString('"topPackages":{"labels":["body-parser"],"counts":[1]}', $body);
    }

    public function testDashboardReturns200WithWarningWhenScanMissing(): void
    {
        $this->seedMinimalFixture();

        $this->client->request('GET', '/npm-audit/dashboard/fr.ma-moulinette/npm-demo/9.9.9-INEXISTANT');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Aucun scan npm audit disponible', $body);
    }

    /**
     * Insère une fixture minimale : 1 scan, 1 dep, 1 vulnerability, 1 finding.
     */
    private function seedMinimalFixture(): void
    {
        $listeProjet = new ListeProjet(
            mavenKey: 'fr.ma-moulinette:npm-demo',
            name: 'npm-demo',
            visibility: 'public',
            tags: ['fr-test-acl']
        );
        $this->em->persist($listeProjet);

        $vuln = (new NpmVulnerability())
            ->setAdvisoryId('GHSA-27R3-JXMH-P828')
            ->setTitle('body-parser vulnerable to Denial of Service')
            ->setUrl('https://github.com/advisories/GHSA-27r3-jxmh-p828')
            ->setSeverity(NpmVulnerability::SEVERITY_HIGH)
            ->setCvssScore('7.5')
            ->setCwes(['CWE-248']);
        $this->em->persist($vuln);

        $dep = (new NpmDependency())->setName('body-parser');
        $this->em->persist($dep);

        $scan = (new NpmScan())
            ->setProjectGroup('fr.ma-moulinette')
            ->setProjectArtifact('npm-demo')
            ->setProjectVersion('1.0.0')
            ->setScanDate(new \DateTimeImmutable('2026-11-20T08:00:00+01:00'))
            ->setAuditReportVersion('2')
            ->setVulnCountHigh(1)
            ->setVulnCountTotal(1)
            ->setDepCountProd(90)
            ->setDepCountDev(415)
            ->setDepCountTotal(504);
        $this->em->persist($scan);
        $this->em->flush();

        $finding = (new NpmFinding())
            ->setScan($scan)->setDependency($dep)->setVulnerability($vuln)
            ->setSeverityAtScan('high')->setCvssAtScan('7.5')
            ->setRange('>=2.0.0 <2.4.0')->setIsDirect(false)->setFixAvailable(true);
        $this->em->persist($finding);
        $this->em->flush();
    }

    private function seedOutOfScopeScan(): void
    {
        $listeProjet = new ListeProjet(
            mavenKey: 'fr.hors-perimetre:npm-externe',
            name: 'npm-externe',
            visibility: 'public',
            tags: ['autre-groupe-acl']
        );
        $this->em->persist($listeProjet);

        $scan = (new NpmScan())
            ->setProjectGroup('fr.hors-perimetre')
            ->setProjectArtifact('npm-externe')
            ->setProjectVersion('1.0.0')
            ->setScanDate(new \DateTimeImmutable());
        $this->em->persist($scan);
        $this->em->flush();
    }
}
