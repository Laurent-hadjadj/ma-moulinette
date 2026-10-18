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
 * MODIF 2026-10-16 : KPI + tableaux semaine/mois/top10 de la page Activité —
 * vérifie le rendu réel de index(), jamais testé jusqu'ici (seul le
 * contrôleur Unit l'était, avec des mocks). Le calcul de ces blocs est
 * placé avant l'appel SonarQube dans index() pour s'afficher même si ce
 * dernier échoue — ce test ne mock pas ClientService, donc il vérifie
 * aussi que la page reste 200 dans ce cas.
 */
class ActivityControllerIndexTest extends WebTestCase
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

        $this->em->getConnection()->executeStatement("DELETE FROM ma_moulinette.activity WHERE analyse_id LIKE 'AY_test_index_%'");
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->executeStatement("DELETE FROM ma_moulinette.activity WHERE analyse_id LIKE 'AY_test_index_%'");
        parent::tearDown();
    }

    public function testIndexRendersKpiAndTablesFromLocalData(): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        for ($i = 0; $i < 3; $i++) {
            $executedAt = $now->modify("-{$i} days");
            $this->em->getConnection()->executeStatement(
                'INSERT INTO ma_moulinette.activity
                    (maven_key, project_name, analyse_id, status, submitter_login, submitted_at, started_at, executed_at, execution_time)
                 VALUES (:mk, :pn, :aid, :st, :sub, :sa, :sta, :ea, :et)',
                [
                    'mk' => 'fr.ma-moulinette:ma-moulinette',
                    'pn' => 'ma-moulinette',
                    'aid' => 'AY_test_index_' . $i,
                    'st' => 'SUCCESS',
                    'sub' => 'ci-pipeline',
                    'sa' => $executedAt->modify('-60 seconds')->format('Y-m-d H:i:sO'),
                    'sta' => $executedAt->modify('-10 seconds')->format('Y-m-d H:i:sO'),
                    'ea' => $executedAt->format('Y-m-d H:i:sO'),
                    'et' => 12000,
                ]
            );
        }

        $this->client->request('GET', '/activity');

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getContent();

        $this->assertStringContainsString('Indicateurs clés', $body);
        $this->assertStringContainsString('Cette semaine', $body);
        $this->assertStringContainsString('Ce mois', $body);
        $this->assertStringContainsString("Top 10 des pics d'analyse", $body);
        $this->assertStringContainsString('Top 10 projets — semaine en cours', $body);
        $this->assertStringContainsString('Top 10 projets — mois en cours', $body);
        $this->assertStringContainsString('ma-moulinette', $body);
    }
}
