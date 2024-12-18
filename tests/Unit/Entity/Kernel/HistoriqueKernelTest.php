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

namespace App\Tests\Unit\Entity\Kernel;

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

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Il n'y a pas de séquence pour cette table car on utilise une clé composite

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new HistoriqueFixtures()]);
    }

    public function testHistoriqueFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $response = $historiqueRepository->findOneBy(['mavenKey' => static::$mavenKey]);

        $this->assertNotNull($response, 'Aucune entité a été trouvée');
        $this->assertCount(1, [$response], 'MAVENKEY: Aucune réponse trouvée');
    }

    public function testHistoriqueCount(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $response = $historiqueRepository->findBy(['mavenKey' => static::$mavenKey]);

        $this->assertNotNull($response, 'Aucune entité a été trouvée');
        $this->assertCount(2, $response, 'MAVENKEY: Aucune réponse trouvée');
    }

}
