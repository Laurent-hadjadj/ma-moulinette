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

namespace App\Tests\Integration\Controller\Activity;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * MODIF 2026-10-16 : rapport PDF Activité (synthèse, histogramme
 * hebdomadaire, journées de pic, top projets) — vérifie que l'export
 * fonctionne réellement (200, Content-Type pdf, en-tête %PDF), pas
 * seulement que le câblage de la route compile.
 */
class ActivityControllerPdfTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    private const MEL = 'emma.durand@ma-moulinette.fr';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->em->getRepository(Utilisateur::class)->findOneBy(['courriel' => self::MEL]);
        $this->assertNotNull($user, 'Fixtures Utilisateur doivent être chargées.');
        $user->setRoles(['ROLE_UTILISATEUR', 'ROLE_ACTIVITY']);
        $this->em->flush();
        $this->client->loginUser($user);

        $this->em->getConnection()->executeStatement("DELETE FROM ma_moulinette.activity WHERE analyse_id LIKE 'AY_test_pdf_%'");
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->executeStatement("DELETE FROM ma_moulinette.activity WHERE analyse_id LIKE 'AY_test_pdf_%'");
        parent::tearDown();
    }

    public function testRapportPdfReturnsValidPdfWithActivityData(): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        for ($i = 0; $i < 5; $i++) {
            $executedAt = $now->modify("-{$i} days");
            $this->em->getConnection()->executeStatement(
                'INSERT INTO ma_moulinette.activity
                    (maven_key, project_name, analyse_id, status, submitter_login, submitted_at, started_at, executed_at, execution_time)
                 VALUES (:mk, :pn, :aid, :st, :sub, :sa, :sta, :ea, :et)',
                [
                    'mk' => 'fr.ma-moulinette:ma-moulinette',
                    'pn' => 'ma-moulinette',
                    'aid' => 'AY_test_pdf_' . $i,
                    'st' => 'SUCCESS',
                    'sub' => 'ci-pipeline',
                    'sa' => $executedAt->modify('-60 seconds')->format('Y-m-d H:i:sO'),
                    'sta' => $executedAt->modify('-10 seconds')->format('Y-m-d H:i:sO'),
                    'ea' => $executedAt->format('Y-m-d H:i:sO'),
                    'et' => 12000,
                ]
            );
        }

        $this->client->request('GET', '/activity/rapport/pdf');

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }
}
