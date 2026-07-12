<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Validator;

use App\Entity\InformationProjet;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description InformationProjetValidatorTest]
 */
class InformationProjetValidatorTest extends KernelTestCase
{

  private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
  private static string $analyseKey = 'AYVyxZcQo0TJpgSeq-ph';
  /* MODIF 2026-05-06 : alignement avec entity
   * (setDate -> setDateAnalyse, setType -> setTypeAnalyse). */
  private static string $dateAnalyse = '2024-04-12 16:23:11';
  private static string $projectVersion = '2.0.0-RELEASE';
  private static string $typeAnalyse = 'RELEASE';
  private static int $versionSonar = 59;
  private static int $versionReleaseSonar = 54;
  private static int $versionSnapshotSonar = 3;
  private static int $versionAutreSonar = 2;
  private static string $modeCollecte = 'TRAITEMENT MANUEL';
  private static string $utilisateurCollecte = 'laurent.hadjadj@ma-moulinette.fr';
  private static string $dateEnregistrement = '2024-04-12 16:23:11+01';

  private function getEntity(): InformationProjet
  {
    return (new informationProjet())
      ->setMavenKey(self::$mavenKey)
      ->setAnalyseKey(self::$analyseKey)
      ->setDateAnalyse(new \DateTimeImmutable(self::$dateAnalyse))
      ->setProjectVersion(self::$projectVersion)
      ->setTypeAnalyse(self::$typeAnalyse)
      ->setVersionSonar(self::$versionSonar)
      ->setVersionReleaseSonar(self::$versionReleaseSonar)
      ->setVersionSnapshotSonar(self::$versionSnapshotSonar)
      ->setVersionAutreSonar(self::$versionAutreSonar)
      ->setModeCollecte(self::$modeCollecte)
      ->setUtilisateurCollecte(self::$utilisateurCollecte)
      ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
  }

  public function assertHasErrors(InformationProjet $entity, int $number = 0): void
  {
    self::bootKernel();
    $container = static::getContainer();
    $errors = $container->get('validator')->validate($entity);
    $messages = [];
    /** @var ConstraintViolation $error */
    foreach ($errors as $error) {
      $messages[] = $error->getPropertyPath() . ' => ' . $error->getMessage();
    }
    $this->assertCount($number, $errors, implode(', ', $messages));
  }

  public function testValidEntity(): void
  {
    $this->assertHasErrors($this->getEntity(), 0);
  }

  public function testInvalidBlankEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setMavenKey(''), 1);
    $this->assertHasErrors($this->getEntity()->setAnalyseKey(''), 1);
    $this->assertHasErrors($this->getEntity()->setProjectVersion(''), 1);
    $this->assertHasErrors($this->getEntity()->setTypeAnalyse(''), 1);
    $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
    $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
  }

  public function testValidIntegerEntity(): void
  {
    // v2.0.0 : valeurs limites BASSES acceptees par PositiveOrZero (>= 0)
    $this->assertHasErrors($this->getEntity()->setVersionSonar(0), 0);
    $this->assertHasErrors($this->getEntity()->setVersionReleaseSonar(0), 0);
    $this->assertHasErrors($this->getEntity()->setVersionSnapshotSonar(0), 0);
    $this->assertHasErrors($this->getEntity()->setVersionAutreSonar(0), 0);
  }

  public function testCountAttribut(): void
  {
    $entity = $this->getEntity();
    $reflectionClass = new \ReflectionClass($entity);
    $nbAttributs = count($reflectionClass->getProperties());
    $this->assertEquals($nbAttributs, 13);
  }
}
