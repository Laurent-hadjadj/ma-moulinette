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
use App\DataFixtures\NotesFixtures;
use App\Entity\Main\Notes;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * [Description NotesKernalTest]
 */
class NotesKernelTest extends KernelTestCase
{

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new NotesFixtures()]);
    }

    public function testNotesFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $notesRepository = $entityManager->getRepository(Notes::class);
        $reliability = $notesRepository->findOneBy(['type' => 'reliability']);
        $security = $notesRepository->findOneBy(['type' => 'security']);
        $sqale = $notesRepository->findOneBy(['type' => 'sqale']);

        $this->assertNotNull($reliability, 'Aucune entité a été trouvée');
        $this->assertCount(1, [$reliability], 'RELIABILITY : Aucune réponse');
        $this->assertCount(1, [$security], 'SECURITY: Aucune réponse trouvée');
        $this->assertCount(1, [$sqale], 'SQALE: Aucune réponse trouvée');
    }
}
