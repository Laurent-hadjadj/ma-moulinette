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
 * [Description UtilisateurKernalTest]
 */
class NotesKernelTest extends KernelTestCase
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $dateEnregistrement = '2024-03-26 14:46:38';

  /**
   * [Description for getEntity]
   * Prépare le jeu de données
   *
   * @return Utilisateur
   *
   * Created at: 02/05/2024 20:44:25 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function getEntity(): Notes
  {
    return (new notes())
      ->setMavenKey(static::$mavenKey)
      ->setValue(3)
      ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
  }

    /**
     * [Description for setUp]
     * Création des utilisateurs en base depuis les fixtures
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

    public function testNotesFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $notesRepository = $entityManager->getRepository(Notes::class);
        $reliability = $notesRepository->findOneBy(['type' => 'reliability']);
        $security = $notesRepository->findOneBy(['type' => 'security']);
        $sqale = $notesRepository->findOneBy(['type' => 'sqale']);

        $this->assertCount(1, [$reliability]);
        $this->assertCount(1, [$security]);
        $this->assertCount(1, [$sqale]);
  }
}
