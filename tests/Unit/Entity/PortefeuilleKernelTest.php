<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\DataFixtures\PortefeuilleFixtures;
use App\Entity\Portefeuille;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * [Description EquipeKernelTest]
 */
class PortefeuilleKernelTest extends KernelTestCase
{

    private static $titre = 'MES PROJETS';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new PortefeuilleFixtures()]);
    }

    public function testInformationProjetFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $equipeRepository = $entityManager->getRepository(Portefeuille::class);
        $response = $equipeRepository->findOneBy(['titre' => static::$titre]);

        $this->assertNotNull($response, 'Aucune entité a été trouvée');
        $this->assertCount(1, [$response], 'TITRE: Aucune réponse trouvée');
    }
}