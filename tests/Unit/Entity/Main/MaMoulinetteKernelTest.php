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

namespace App\Tests\Unit\Entity\Main;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\DataFixtures\MaMoulinetteFixtures;
use App\Entity\Main\MaMoulinette;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * [Description MaMoulinetteKernelTest]
 */
class MaMoulinetteKernelTest extends KernelTestCase
{

    private static $version = '2.0.0';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new MaMoulinetteFixtures()]);
    }

    public function testMaMoulinetteFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $maMoulinetteRepository = $entityManager->getRepository(MaMoulinette::class);
        $response = $maMoulinetteRepository->findOneBy(['version' => static::$version]);

        $this->assertNotNull($response, 'Aucune entité a été trouvée');
        $this->assertCount(1, [$response], 'VERSION: Aucune réponse trouvée');
  }
}
