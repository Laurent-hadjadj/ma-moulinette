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
use App\DataFixtures\ProfilesFixtures;
use App\Entity\Profiles;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * [Description ProfilesKernalTest]
 */
class ProfilesKernelTest extends KernelTestCase
{

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new ProfilesFixtures()]);
    }

    public function testReferentielDefaultFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $profilesRepository = $entityManager->getRepository(Profiles::class);
        $languageName = $profilesRepository->findOneBy(['languageName' => 'css']);
        $default = $profilesRepository->findOneBy(['referentielDefault' => true]);

        $this->assertNotNull($languageName, 'Aucune entité a été trouvée');
        $this->assertCount(1, [$languageName], 'LANGUAGE NAME : Aucune réponse');
        $this->assertCount(1, [$default], 'REFERENTIEL DEFAULT : Aucune réponse');
    }
}
