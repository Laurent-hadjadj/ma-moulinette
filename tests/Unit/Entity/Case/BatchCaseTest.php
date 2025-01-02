<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
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

    private static $statut = false;
    private static $titre = 'mon-batch à moi';
    private static $description = 'Mon batch à moi';
    private static $responsable = 'Laurent HADJADJ';
    private static $portefeuille = 'application-ma-moulinette';
    private static $nombreProjet = 4;
    private static $execution = 'OK';
    private static $dateModification = '2025-01-02 12:00:00+02';
    private static $dateEnregistrement = '2024-07-31 12:27:05+02';

    private function getEntity(): batch
    {
        return (new batch())
        ->setStatut(static::$statut)
        ->setTitre(static::$titre)
        ->setDescription(static::$description)
        ->setResponsable(static::$responsable)
        ->setPortefeuille(static::$portefeuille)
        ->setNombreProjet(static::$nombreProjet)
        ->setExecution(static::$execution)
        ->setDateModification(new \DateTimeImmutable(static::$dateModification))
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->batch = $this->getEntity();
    }

    public function testSettingAndGettingStatut(): void
    {
        $this->batch->setStatut(static::$statut);
        $this->assertEquals(static::$statut, $this->batch->isStatut());
    }

    public function testSettingAndGettingTitre(): void
    {
        $this->batch->setTitre(static::$titre);
        $this->assertEquals(static::$titre, $this->batch->getTitre());
    }

    public function testSettingAndGettingDescription(): void
    {
        $this->batch->setDescription(static::$description);
        $this->assertEquals(static::$description, $this->batch->getDescription());
    }

    public function testSettingAndGettingResponsable(): void
    {
        $this->batch->setResponsable(static::$responsable);
        $this->assertEquals(static::$responsable, $this->batch->getResponsable());
    }

    public function testSettingAndGettingPortefeuille(): void
    {
        $this->batch->setPortefeuille(static::$portefeuille);
        $this->assertEquals(static::$portefeuille, $this->batch->getPortefeuille());
    }

    public function testSettingAndGettingNombreProjet(): void
    {
        $this->batch->setNombreProjet(static::$nombreProjet);
        $this->assertEquals(static::$nombreProjet, $this->batch->getNombreProjet());
    }

    public function testSettingAndGettingExecution(): void
    {
        $this->batch->setExecution(static::$execution);
        $this->assertEquals(static::$execution, $this->batch->getExecution());
    }

    public function testSettingAndGettingDateModification(): void
    {
        $newDate = new \DateTimeImmutable('2025-01-02 12:00:00+02');
        $this->batch->setDateModification($newDate);
        $this->assertEquals($newDate, $this->batch->getDateModification());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->batch->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->batch->getDateEnregistrement());
    }



}
