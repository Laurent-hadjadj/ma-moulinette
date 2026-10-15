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

namespace App\Tests\Integration\Controller\Zap;

use App\Entity\{ListeProjet, Utilisateur, ZapProcessingQueue};
use App\Service\Zap\ZapIngester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

/**
 * MODIF 2026-10-13 : tests smoke page ZAP (DAST), sur le gabarit
 * DependencyCheckPageControllerTest/OwaspControllerTest.
 *
 * Couvre :
 *  - 403 sans ROLE_SECURITY
 *  - token absent -> 200 + message info, état vide
 *  - avec un (ou deux, pour l'historique) scan(s) réellement ingéré(s) via
 *    ZapIngester -> 200, KPI/tableau/dataset JSON corrects.
 */
class ZapPageControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private ZapIngester $ingester;

    private const MEL = 'emma.durand@ma-moulinette.fr';
    private const MAVEN_KEY = 'fr.ma-moulinette:zap-page-test';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->ingester = static::getContainer()->get(ZapIngester::class);

        $user = $this->em->getRepository(Utilisateur::class)->findOneBy(['courriel' => self::MEL]);
        $this->assertNotNull($user, 'Fixtures Utilisateur doivent être chargées (emma.durand@ma-moulinette.fr).');
        $user->setListeGroupeFonctionnel(['fr-test']);
        $user->setRoles(['ROLE_UTILISATEUR', 'ROLE_SECURITY']);
        $this->em->flush();
        $this->client->loginUser($user);

        // Isolation : purge des tables ZAP + de la ligne liste_projet de ce test.
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.owasp_dast');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.zap_finding');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.zap_scan');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.zap_processing_queue');
        $this->em->getConnection()->executeStatement(
            "DELETE FROM ma_moulinette.liste_projet WHERE maven_key = '" . self::MAVEN_KEY . "'"
        );
    }

    public function testReturns403WithoutRoleSecurity(): void
    {
        $user = $this->em->getRepository(Utilisateur::class)->findOneBy(['courriel' => self::MEL]);
        $user->setRoles(['ROLE_UTILISATEUR']);
        $this->em->flush();
        // Le token de sécurité déjà authentifié (setUp) garde les rôles du
        // moment du login — il faut re-logger l'utilisateur pour que le
        // nouveau jeu de rôles (sans ROLE_SECURITY) soit pris en compte.
        $this->client->loginUser($user);

        $this->client->request('GET', '/zap');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testReturns200WithInfoMessageWhenTokenMissing(): void
    {
        $this->client->request('GET', '/zap');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Sélectionnez un projet', $this->client->getResponse()->getContent());
    }

    public function testReturns200WithEmptyStateWhenNoScan(): void
    {
        $this->seedListeProjet();

        $this->client->request('GET', '/zap?token=' . $this->buildToken(self::MAVEN_KEY));
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Aucun scan ZAP disponible', $this->client->getResponse()->getContent());
    }

    public function testReturns200WithKpiTableAndChartDataWhenScansExist(): void
    {
        $this->seedListeProjet();

        // 2 scans ingérés (le même fixture, 2 dates distinctes) pour exercer
        // l'historique et le graphique d'évolution avec 2 points.
        $this->ingestFixture('2026-10-01 10:00:00');
        $scan2 = $this->ingestFixture('2026-10-13 10:00:00');

        $this->client->request('GET', '/zap?token=' . $this->buildToken(self::MAVEN_KEY));
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = $this->client->getResponse()->getContent();

        // KPI : 3 alertes au total sur le dernier scan (fixture report-ok.json).
        $this->assertStringContainsString((string) $scan2->getAlertCountTotal(), $body);
        $this->assertStringContainsString('fr.ma-moulinette:zap-page-test', $body);

        // Tableau de catégories : au moins une catégorie 2021 avec un compte > 0.
        $this->assertStringContainsString('OWASP 2021', $body);
        $this->assertStringContainsString('OWASP 2017', $body);

        // Dataset JSON pour les graphiques : catégories + historique à 2 points.
        preg_match('/<script type="application\/json" id="zap-chart-data">(.*?)<\/script>/s', $body, $matches);
        $this->assertArrayHasKey(1, $matches, 'Le dataset JSON zap-chart-data doit être présent dans la page.');
        $chartData = json_decode($matches[1], true);
        $this->assertIsArray($chartData);
        $this->assertCount(2, $chartData['categories']); // 2017 + 2021
        $this->assertCount(2, $chartData['history']); // 2 scans ingérés
    }

    private function seedListeProjet(): void
    {
        $listeProjet = new ListeProjet(
            mavenKey: self::MAVEN_KEY,
            name: 'zap-page-test',
            visibility: 'public',
            tags: ['fr-test-acl'],
        );
        $this->em->persist($listeProjet);
        $this->em->flush();
    }

    private function ingestFixture(string $generatedAt): \App\Entity\ZapScan
    {
        $samplePath = __DIR__ . '/../../../Fixtures/zap-json/report-ok.json';
        $report = json_decode(file_get_contents($samplePath), true);
        $report['@generated'] = $generatedAt;
        $json = json_encode($report);

        $queue = (new ZapProcessingQueue())
            ->setUlid((new Ulid())->toBase32())
            ->setPayloadGz(gzencode($json, 6))
            ->setPayloadSha256(hash('sha256', $json))
            ->setPayloadSize(strlen($json))
            ->setContentType(ZapProcessingQueue::CONTENT_JSON)
            ->setMavenKey(self::MAVEN_KEY)
            ->setProjectVersion('1.0')
            ->setStatus(ZapProcessingQueue::STATUS_PROCESSING);
        $this->em->persist($queue);
        $this->em->flush();

        $scan = $this->ingester->ingest($queue);
        $this->em->clear();

        return $this->em->getRepository(\App\Entity\ZapScan::class)->find($scan->getId());
    }

    private function buildToken(string $mavenKey, int $salt = 12345): string
    {
        return str_rot13(base64_encode("{$salt}|{$mavenKey}"));
    }
}
