<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Case;

use App\Entity\Batch;
use PHPUnit\Framework\TestCase;

/**
 * [Description BatchCaseTest]
 */
class BatchCaseTest extends TestCase
{
    private $batch;

    private static $automatique = false;
    private static $titre = 'mon-batch à moi';
    private static $description = 'Mon batch à moi';
    private static $responsable = 'Laurent HADJADJ';
    private static $portefeuille = 'application-ma-moulinette';
    private static $nombreProjet = 4;
    private static $execution = 'OK';
    private static $dateModification = '2025-01-02 12:00:00+02';
    private static $dateEnregistrement = '2024-07-31 12:27:05+02';

    private function getEntity(): Batch
    {
        return (new batch())
        ->setAutomatique(self::$automatique)
        ->setTitre(self::$titre)
        ->setDescription(self::$description)
        ->setResponsable(self::$responsable)
        ->setPortefeuille(self::$portefeuille)
        ->setNombreProjet(self::$nombreProjet)
        ->setExecution(self::$execution)
        ->setDateModification(new \DateTime(self::$dateModification))
        ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->batch = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->batch->setId(true);
        $this->assertEquals(true, $this->batch->getId());
    }

    public function testSettingAndGettingAutomatique(): void
    {
        $this->batch->setAutomatique(self::$automatique);
        $this->assertEquals(self::$automatique, $this->batch->isAutomatique());
    }

    public function testSettingAndGettingTitre(): void
    {
        $this->batch->setTitre(self::$titre);
        $this->assertEquals(self::$titre, $this->batch->getTitre());
    }

    public function testSettingAndGettingDescription(): void
    {
        $this->batch->setDescription(self::$description);
        $this->assertEquals(self::$description, $this->batch->getDescription());
    }

    public function testSettingAndGettingResponsable(): void
    {
        $this->batch->setResponsable(self::$responsable);
        $this->assertEquals(self::$responsable, $this->batch->getResponsable());
    }

    public function testSettingAndGettingPortefeuille(): void
    {
        $this->batch->setPortefeuille(self::$portefeuille);
        $this->assertEquals(self::$portefeuille, $this->batch->getPortefeuille());
    }

    public function testSettingAndGettingNombreProjet(): void
    {
        $this->batch->setNombreProjet(self::$nombreProjet);
        $this->assertEquals(self::$nombreProjet, $this->batch->getNombreProjet());
    }

    public function testSettingAndGettingExecution(): void
    {
        $this->batch->setExecution(self::$execution);
        $this->assertEquals(self::$execution, $this->batch->getExecution());
    }

    public function testSettingAndGettingDateModification(): void
    {
        $newDate = new \DateTime('2025-01-02 12:00:00+02');
        $this->batch->setDateModification($newDate);
        $this->assertEquals($newDate, $this->batch->getDateModification());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnregistrement);
        $this->batch->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->batch->getDateEnregistrement());
    }

}
