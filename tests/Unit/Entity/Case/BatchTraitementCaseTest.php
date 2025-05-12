<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Case;

use App\Entity\BatchTraitement;
use PHPUnit\Framework\TestCase;

/**
 * [Description BatchTraitementCaseTest]
*/
class BatchTraitementCaseTest extends TestCase
{
    private $batchTraitement;

    private static $modeCollecte = 'TRAITEMENT MANUEL';
    private static $result = true;
    private static $titre = 'mon-batch à moi';
    private static $portefeuille = 'application-ma-moulinette';
    private static $nombreProjet = 4;
    private static $responsable = 'Laurent HADJADJ';
    private static $debutTraitement = '2025-01-02 12:00:00+02';
    private static $finTraitement = '2025-01-02 12:02:00+02';
    private static $dateEnregistrement = '2025-01-02 12:02:00+02';

    private function getEntity(): batchTraitement
    {
        return (new batchTraitement())
        ->setModeCollecte(static::$modeCollecte)
        ->setResult(static::$result)
        ->setTitre(static::$titre)
        ->setPortefeuille(static::$portefeuille)
        ->setNombreProjet(static::$nombreProjet)
        ->setResponsable(static::$responsable)
        ->setDebutTraitement(new \DateTimeImmutable(static::$debutTraitement))
        ->setFinTraitement(new \DateTimeImmutable(static::$finTraitement))
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->batchTraitement = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->batchTraitement->setId(1);
        $this->assertEquals(1, $this->batchTraitement->getId());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $newModeCollecte = 'TRAITEMENT AUTOMATIQUE';
        $this->batchTraitement->setModeCollecte($newModeCollecte);
        $this->assertEquals($newModeCollecte, $this->batchTraitement->getModeCollecte());
    }

    public function testSettingAndGettingResult(): void
    {
        $newResult = false;
        $this->batchTraitement->setResult($newResult);
        $this->assertEquals($newResult, $this->batchTraitement->isResult());
    }

    public function testSettingAndGettingTitre(): void
    {
        $newTitre = 'Nouveau titre';
        $this->batchTraitement->setTitre($newTitre);
        $this->assertEquals($newTitre, $this->batchTraitement->getTitre());
    }

    public function testSettingAndGettingPortefeuille(): void
    {
        $newPortefeuille = 'nouveau-portefeuille';
        $this->batchTraitement->setPortefeuille($newPortefeuille);
        $this->assertEquals($newPortefeuille, $this->batchTraitement->getPortefeuille());
    }

    public function testSettingAndGettingNombreProjet(): void
    {
        $newNombreProjet = 10;
        $this->batchTraitement->setNombreProjet($newNombreProjet);
        $this->assertEquals($newNombreProjet, $this->batchTraitement->getNombreProjet());
    }

    public function testSettingAndGettingResponsable(): void
    {
        $newResponsable = 'Jean Dupont';
        $this->batchTraitement->setResponsable($newResponsable);
        $this->assertEquals($newResponsable, $this->batchTraitement->getResponsable());
    }

    public function testSettingAndGettingDebutTraitement(): void
    {
        $newDebutTraitement = new \DateTimeImmutable('2025-01-03 12:00:00+02');
        $this->batchTraitement->setDebutTraitement($newDebutTraitement);
        $this->assertEquals($newDebutTraitement, $this->batchTraitement->getDebutTraitement());
    }

    public function testSettingAndGettingFinTraitement(): void
    {
        $newFinTraitement = new \DateTimeImmutable('2025-01-03 12:30:00+02');
        $this->batchTraitement->setFinTraitement($newFinTraitement);
        $this->assertEquals($newFinTraitement, $this->batchTraitement->getFinTraitement());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDateEnregistrement = new \DateTimeImmutable('2025-01-03 12:45:00+02');
        $this->batchTraitement->setDateEnregistrement($newDateEnregistrement);
        $this->assertEquals($newDateEnregistrement, $this->batchTraitement->getDateEnregistrement());
    }

}
