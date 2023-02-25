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

namespace App\Tests\Entity\Main;

use App\Entity\Main\Equipe;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EquipeUniqueTest extends KernelTestCase
{


  /**
   * @var \Doctrine\ORM\EntityManager
   */
  private $entityManager;

  private static $titre="MA MOULINETTE";

  /**
   * [Description for setUp]
   * On ouvre une connexion
   * @return void
   *
   * Created at: 15/02/2023, 10:49:22 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  protected function setUp(): void
  {
      $kernel = static::bootKernel();

      $this->entityManager = $kernel->getContainer()
          ->get('doctrine')
          ->getManager();
  }

  /**
   * [Description for testSearchByTitre]
   * Recherche par titre
   * @return [type]
   *
   * Created at: 15/02/2023, 09:51:37 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   *
   */
  public function testSearchByTitre()
  {
      $getTitre = $this->entityManager
          ->getRepository(Equipe::class)
          ->findOneBy(['titre' => 'MA MOULINETTE']);

      if ($getTitre){
        $this->assertSame(static::$titre, $getTitre->getTitre(), "[Equipe] Le titre exsite déjà !");
      }
      else {
        $this->assertNull($getTitre, "[Equipe] Le titre n'exsite pas.");
        $this->fail("[Equipe] Le titre n'exsite pas.");
      }
  }

  /**
   * [Description for tearDown]
   *  On ferme la connexion
   * @return void
   *
   * Created at: 15/02/2023, 10:48:44 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   *
   */
  protected function tearDown(): void
  {
      parent::tearDown();
      $this->entityManager->close();
      $this->entityManager = null;
  }

}
