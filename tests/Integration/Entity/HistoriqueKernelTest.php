<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Integration\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\DataFixtures\HistoriqueFixtures;
use App\Entity\Historique;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * [Description HistoriqueKernelTest]
 */
class HistoriqueKernelTest extends KernelTestCase
{

    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Il n'y a pas de séquence pour cette table car on utilise une clé composite

        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.historique');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new HistoriqueFixtures()], true);
    }

    public function testHistoriqueFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $response = $historiqueRepository->findOneBy(['mavenKey' => self::$mavenKey]);

        $this->assertNotNull($response, 'Maven_key: Aucune réponse trouvée');
    }

    public function testHistoriqueCount(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $response = $historiqueRepository->findBy(['mavenKey' => self::$mavenKey]);

        $this->assertCount(2, $response, 'Maven_Key: Aucune réponse trouvée');
    }
}
