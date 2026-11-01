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

use App\Entity\Owasp;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description OwaspValidatorTest]
 *
 * MODIF 27/10/2026 : les 60 attributs a1..a10/aN_blocker..minor + source sont
 * remplacés par categories (JSON) + module_frontend/backend/autre.
 */
class OwaspValidatorTest extends KernelTestCase
{
  private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
  private static int $referentialOwasp = 2017;
  private static string $version = '1.2.0-RELEASE';
  private static string $dateVersion = '2024-07-10 15:26:07+02';
  private static int $effortTotal = 0;
  private static string $modeCollecte = 'TRAITEMENT MANUEL';
  private static string $utilisateurCollecte = 'laurent.hadjadj@ma-moulinette.fr';
  private static string $dateEnregistrement = '2024-03-26 14:46:38+02';

  private function getEntity(): Owasp
  {
    $owasp = new Owasp();
    $owasp->setMavenKey(self::$mavenKey);
    $owasp->setReferentialOwasp(self::$referentialOwasp);
    $owasp->setVersion(self::$version);
    $owasp->setDateVersion(new \DateTimeImmutable(self::$dateVersion));
    $owasp->setEffortTotal(self::$effortTotal);
    $owasp->setCategories([]);
    $owasp->setModuleFrontend(0);
    $owasp->setModuleBackend(0);
    $owasp->setModuleAutre(0);
    $owasp->setModeCollecte(self::$modeCollecte);
    $owasp->setUtilisateurCollecte(self::$utilisateurCollecte);
    $owasp->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    return $owasp;
  }

  public function assertHasErrors(Owasp $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setVersion(''), 1);
    $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
    $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
  }

  public function testValidEntityWithEmptyCategories(): void
  {
    // Projet sans vulnérabilité OWASP détectée : categories reste à [], valide.
    $this->assertHasErrors($this->getEntity()->setCategories([]), 0);
  }

  public function testValidEntityWithModuleCountsNull(): void
  {
    // Nullable : un projet jamais collecté n'a pas encore de répartition par module.
    $this->assertHasErrors(
      $this->getEntity()->setModuleFrontend(null)->setModuleBackend(null)->setModuleAutre(null),
      0
    );
  }

  // `effortTotal`, `moduleFrontend/backend/autre` contraints par Assert\PositiveOrZero (>= 0).

  public function testValidIntegerEffortTotalEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setEffortTotal(0), 0);
  }

  public function testInvalidIntegerEffortTotalEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setEffortTotal(-1), 1);
  }

  /**
   * @return iterable<string, array{0: string}>
   */
  public static function moduleFieldsProvider(): iterable
  {
    yield 'moduleFrontend' => ['ModuleFrontend'];
    yield 'moduleBackend' => ['ModuleBackend'];
    yield 'moduleAutre' => ['ModuleAutre'];
  }

  #[DataProvider('moduleFieldsProvider')]
  public function testValidModuleCount(string $suffix): void
  {
    $this->assertHasErrors($this->getEntity()->{'set' . $suffix}(0), 0);
  }

  #[DataProvider('moduleFieldsProvider')]
  public function testInvalidModuleCount(string $suffix): void
  {
    $this->assertHasErrors($this->getEntity()->{'set' . $suffix}(-1), 1);
  }

  /**
   * MODIF 27/10/2026 : 70 → 13 (suppression des 60 attributs a1..a10/aN_blocker..minor
   * + source, remplacés par categories + module_frontend/backend/autre).
   */
  public function testCountAttribut(): void
  {
    $entity = $this->getEntity();
    $reflectionClass = new \ReflectionClass($entity);
    $nbAttributs = count($reflectionClass->getProperties());
    $this->assertEquals(13, $nbAttributs);
  }
}
