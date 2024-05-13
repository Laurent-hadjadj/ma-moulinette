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

namespace App\Tests\Unit\Repository;

use App\Entity\Notes;
use App\DataFixtures\NotesFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description UtilisateurRepositoryTest]
 */
class NotesRepositoryTest extends KernelTestCase
{

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $dateEnregistrement = '2024-03-26 14:46:38';
    private static $erreurCode200 = 'Erreur le code retour doit être 200';

    /**
     * [Description for setUp]
     * Création des notes en base depuis les fixtures
     *
     * @return void
     *
     * Created at: 05/05/2024 18:15:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new NotesFixtures()]);
    }

    /**
     * [Description for testdeleteNotesMavenKey]
     *
     * @return void
     *
     * Created at: 06/05/2024 21:14:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testDeleteNotesMavenKey(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map1 = ['maven_key' => static::$mavenKey, 'type' => 'reliability'];
        $map2 = ['maven_key' => static::$mavenKey, 'type' => 'security'];
        $map3 = ['maven_key' => static::$mavenKey, 'type' => 'sqale'];

        // Appel de la méthode
        $notesRepository = $entityManager->getRepository(Notes::class);
        $r1 = $notesRepository->deleteNotesMavenKey($map1);
        $r2 = $notesRepository->deleteNotesMavenKey($map2);
        $r3 = $notesRepository->deleteNotesMavenKey($map3);

        // Assert
        $this->assertEquals(200, $r1['code'], static::$erreurCode200);
        $this->assertEmpty($r1['erreur'], $r1['erreur']);
        $this->assertEquals(200, $r2['code'], static::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
        $this->assertEquals(200, $r3['code'], static::$erreurCode200);
        $this->assertEmpty($r3['erreur'], $r3['erreur']);
    }

    /**
     * [Description for testInsertNotes]
     *
     * @return void
     *
     * Created at: 06/05/2024 21:23:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testInsertNotes(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map1 = ['maven_key' => static::$mavenKey, 'type' => 'reliability',
                'value'=>3, 'date_enregistrement'=> static::$dateEnregistrement];
        $map2 = ['maven_key' => static::$mavenKey, 'type' => 'security',
        'value'=>1, 'date_enregistrement'=>static::$dateEnregistrement];
        $map3 = ['maven_key' => static::$mavenKey, 'type' => 'sqale',
        'value'=>2, 'date_enregistrement'=> static::$dateEnregistrement];

        // Appel de la méthode
        $notesRepository = $entityManager->getRepository(Notes::class);
        $r1 = $notesRepository->InsertNotes($map1);
        $r2 = $notesRepository->InsertNotes($map2);
        $r3 = $notesRepository->InsertNotes($map3);

        // Assert
        $this->assertEquals(200, $r1['code'], static::$erreurCode200);
        $this->assertEmpty($r1['erreur'], $r1['erreur']);
        $this->assertEquals(200, $r2['code'], static::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
        $this->assertEquals(200, $r3['code'], static::$erreurCode200);
        $this->assertEmpty($r3['erreur'], $r3['erreur']);
    }

    /**
     * [Description for testSelectNotesMavenType]
     *
     * @return void
     *
     * Created at: 06/05/2024 21:29:44 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testSelectNotesMavenType(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map1 = ['maven_key' => static::$mavenKey, 'type' => 'reliability'];
        $map2 = ['maven_key' => static::$mavenKey, 'type' => 'security'];
        $map3 = ['maven_key' => static::$mavenKey, 'type' => 'sqale'];

        // Appel de la méthode
        $notesRepository = $entityManager->getRepository(Notes::class);
        $r1 = $notesRepository->selectNotesMavenType($map1);
        $r2 = $notesRepository->selectNotesMavenType($map2);
        $r3 = $notesRepository->selectNotesMavenType($map3);

        // Assert
        $this->assertEquals(200, $r1['code'], static::$erreurCode200);
        $this->assertEmpty($r1['erreur'], $r1['erreur']);
        $this->assertEquals(200, $r2['code'], static::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
        $this->assertEquals(200, $r3['code'], static::$erreurCode200);
        $this->assertEmpty($r3['erreur'], $r3['erreur']);
    }
}
