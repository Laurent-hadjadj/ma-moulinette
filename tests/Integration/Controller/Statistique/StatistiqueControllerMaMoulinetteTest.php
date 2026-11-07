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

namespace App\Tests\Integration\Controller\Statistique;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * MODIF 2026-11-07 : BUG CORRIGÉ — templates/statistique/ma-moulinette.html.twig
 * utilisait des accolades Twig simples ("{ chart_anomalies | raw }") au lieu de
 * "{{ chart_anomalies|raw }}" : syntaxiquement valide pour lint:twig (du texte
 * littéral, pas une erreur de parsing), mais les variables n'étaient jamais
 * évaluées, produisant un JSON invalide dans #admin-stats-data. admin-stats.js
 * catch cette erreur et fait un retour silencieux (aucune exception levée,
 * aucun graphique affiché) — jamais détecté par le test Unit du contrôleur
 * (mocke render(), n'exécute jamais le vrai moteur Twig) ni par lint:twig.
 * Ce test rend réellement le template pour vérifier que le JSON embarqué est
 * valide et porte les clés attendues par admin-stats.js.
 */
class StatistiqueControllerMaMoulinetteTest extends WebTestCase
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
        $this->client->loginUser($user);
    }

    public function testAdminStatsDataIsValidJsonWithExpectedKeys(): void
    {
        $this->client->request('GET', '/statistiques/ma-moulinette');

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $crawler = $this->client->getCrawler();
        $scriptNode = $crawler->filter('#admin-stats-data');
        $this->assertCount(1, $scriptNode, '#admin-stats-data doit être présent sur la page.');

        $stats = json_decode($scriptNode->text(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('anomalies', $stats);
        $this->assertArrayHasKey('projets', $stats);
        foreach (['labels', 'data', 'colors'] as $key) {
            $this->assertArrayHasKey($key, $stats['anomalies']);
            $this->assertArrayHasKey($key, $stats['projets']);
        }
    }
}
