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
use App\DataFixtures\MesuresFixtures;
use App\Entity\Main\Mesures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

class MesuresKernelTest extends KernelTestCase
{

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new MesuresFixtures()]);
    }

    public function testNotesFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $mesuresRepository = $entityManager->getRepository(Mesures::class);
        $response = $mesuresRepository->findOneBy(['mavenKey' => static::$mavenKey]);

        $this->assertNotNull($response, 'Aucune entité a été trouvée');
        $this->assertCount(1, [$response], 'MAVENKEY: Aucune réponse trouvée');
  }
}
