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

use App\Entity\Historique;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description HistoriqueValidatorTest]
 */
class HistoriqueValidatorTest extends KernelTestCase
{

  private function getEntity(): Historique
  {
      return (new historique())
      ->setMavenKey('fr.ma-petite-entreprise:ma-moulinette')
      ->setAnalyseKey('AZCc05qWgfifxdiJPzns')
      ->setVersion('1.2.0-RELEASE')
      ->setDateVersion('2024-07-12 16:34:46')
      ->setNomProjet('ma-moulinette')
      ->setVersionRelease('0')
      ->setVersionSnapshot('0')
      ->setVersionAutre('1')
      ->setSuppressWarning('8')
      ->setNoSonar('0')
      ->setTodo('17')
      ->setLoggerInfo('14')
      ->setLoggerWarn('0')
      ->setLoggerError('15')
      ->setLoggerDebug('8')
      ->setNombreLigne('17049')
      ->setNombreLigneCode('8928')
      ->setCoverage('50.1')
      ->setDuplicatedLinesDensity('0.2')
      ->setSqaleDebtRatio('1')
      ->setTests('55')
      ->setViolations('295')
      ->setDette('3054')
      ->setNombreBug('88')
      ->setNombreVulnerability('9')
      ->setNombreCodeSmell('198')
      ->setBugBlocker('7')
      ->setBugCritical('0')
      ->setBugMajor('44')
      ->setBugMinor('0')
      ->setBugInfo('37')
      ->setVulnerabilityBlocker('0')
      ->setVulnerabilityCritical('9')
      ->setVulnerabilityMajor('0')
      ->setVulnerabilityMinor('0')
      ->setVulnerabilityInfo('0')
      ->setCodeSmellBlocker('0')
      ->setCodeSmellCritical('4')
      ->setCodeSmellMajor('109')
      ->setCodeSmellMinor('13')
      ->setCodeSmellInfo('72')
      ->setFrontend('21')
      ->setBackend('136')
      ->setAutre('0')
      ->setNombreAnomalieBloquant('7')
      ->setNombreAnomalieCritique('13')
      ->setNombreAnomalieMajeur('153')
      ->setNombreAnomalieMineur('13')
      ->setNombreAnomalieInfo('109')
      ->setNoteReliability('E')
      ->setNoteSecurity('D')
      ->setNoteSqale('A')
      ->setNoteHotspot('A')
      ->setNombreHotspot('0')
      ->setHotspotHigh('0')
      ->setHotspotMedium('0')
      ->setHotspotLow('0')
      ->setInitial('false')
      ->setModeCollecte('COLLECTE')
      ->setUtilisateurCollecte('admin@ma-moulinette.fr')
      ->setDateEnregistrement(new \DateTimeImmutable('2024-06-28 17:55:45+02'));
  }

  public function assertHasErrors(Historique $entity, int $number = 0): void
  {
    self::bootKernel();
    $container = static::getContainer();
    $errors = $container->get('validator')->validate($entity);
    $messages = [];
    /** @var ConstraintViolation $error */
    foreach($errors as $error) {
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
    $this->assertHasErrors($this->getEntity()->setMavenKey(''), 0);
    $this->assertHasErrors($this->getEntity()->setAnalyseKey(''), 1);
    $this->assertHasErrors($this->getEntity()->setVersion(''), 1);
    $this->assertHasErrors($this->getEntity()->setDateVersion(''), 1);
    $this->assertHasErrors($this->getEntity()->setNomProjet(''), 1);
    $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
    $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
  }

  public function testValidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setVersionRelease(-1), 0);
    $this->assertHasErrors($this->getEntity()->setVersionSnapshot(-1), 0);
    $this->assertHasErrors($this->getEntity()->setVersionAutre(-1), 0);
    $this->assertHasErrors($this->getEntity()->setSuppressWarning(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNoSonar(-1), 0);
    $this->assertHasErrors($this->getEntity()->setTodo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setLoggerInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setLoggerWarn(-1), 0);
    $this->assertHasErrors($this->getEntity()->setLoggerError(-1), 0);
    $this->assertHasErrors($this->getEntity()->setLoggerDebug(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNombreLigne(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNombreLigneCode(-1), 0);
    $this->assertHasErrors($this->getEntity()->setTests(-1), 0);
    $this->assertHasErrors($this->getEntity()->setViolations(-1), 0);
    $this->assertHasErrors($this->getEntity()->setDette(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNombreBug(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNombreVulnerability(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNombreCodeSmell(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBugBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBugCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBugMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBugMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBugInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontend(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackend(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutre(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNombreAnomalieBloquant(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNombreAnomalieCritique(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNombreAnomalieMajeur(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNombreAnomalieMineur(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNombreAnomalieInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNoteReliability(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNoteSecurity(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNoteSqale(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNoteHotspot(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNombreHotspot(-1), 0);
    $this->assertHasErrors($this->getEntity()->setHotspotHigh(-1), 0);
    $this->assertHasErrors($this->getEntity()->setHotspotMedium(-1), 0);
    $this->assertHasErrors($this->getEntity()->setHotspotLow(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInitial(-1), 0);
  }


  public function testValidFloatEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setCoverage(0.0), 0);
    $this->assertHasErrors($this->getEntity()->setDuplicatedLinesDensity(0.0), 0);
    $this->assertHasErrors($this->getEntity()->setSqaleDebtRatio(0.0), 0);

  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 62);
  }
}
