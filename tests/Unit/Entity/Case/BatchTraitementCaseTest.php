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

use App\Entity\BatchTraitement;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\{Ulid};

/**
 * [Description BatchTraitementCaseTest]
*/
class BatchTraitementCaseTest extends TestCase
{
    private $batchTraitement;

    private static string $modeCollecte = 'TRAITEMENT MANUEL';
    private static bool $activated = true;
    private static bool $success = true;
    private static bool $pending = false;
    private static bool $inProgress = false;
    private static string $titre = 'mon-batch à moi';
    private static string $portefeuille = 'application-ma-moulinette';
    private static int $nombreProjet = 4;
    private static string $responsable = 'Laurent HADJADJ';
    private static string $responsableShort = 'L. HADJADJ';
    private static string $debutTraitement = '2025-01-02 12:00:00+02';
    private static string $finTraitement = '2025-01-02 12:02:00+02';
    private static string $dateEnregistrement = '2025-01-02 12:02:00+02';

    private function getEntity(): BatchTraitement
    {
        return (new batchTraitement(
            self::$titre,
            self::$portefeuille,
            self::$responsable,
            self::$responsableShort,
            self::$modeCollecte,
            self::$nombreProjet))
        ->setActivated(self::$activated)
        ->setSuccess(self::$success)
        ->setPending(self::$pending)
        ->setInProgress(self::$inProgress)
        ->setDebutTraitement(new \DateTimeImmutable(self::$debutTraitement))
        ->setFinTraitement(new \DateTimeImmutable(self::$finTraitement))
        ->setTraitementId((string) new Ulid())
        ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
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

    public function testSettingAndGettingActivated(): void
    {
        $newActivated = false;
        $this->batchTraitement->setActivated($newActivated);
        $this->assertEquals($newActivated, $this->batchTraitement->isActivated());
    }

    public function testSettingAndGettingSuccess(): void
    {
        $newSuccess = false;
        $this->batchTraitement->setSuccess($newSuccess);
        $this->assertEquals($newSuccess, $this->batchTraitement->isSuccess());
    }

    public function testSettingAndGettingPending(): void
    {
        $newPending = false;
        $this->batchTraitement->setPending($newPending);
        $this->assertEquals($newPending, $this->batchTraitement->isPending());
    }

    public function testSettingAndGettingInProgress(): void
    {
        $newInProgress = false;
        $this->batchTraitement->setInProgress($newInProgress);
        $this->assertEquals($newInProgress, $this->batchTraitement->isInProgress());
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

    public function testSettingAndGettingResponsableShort(): void
    {
        $newResponsableShort = 'J. Dupont';
        $this->batchTraitement->setResponsableShort($newResponsableShort);
        $this->assertEquals($newResponsableShort, $this->batchTraitement->getResponsableShort());
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

    public function testSettingAndGettingTraitementId(): void
    {
        $newTraitementId = (string) new Ulid();
        $this->batchTraitement->setTraitementId($newTraitementId);
        $this->assertEquals($newTraitementId, $this->batchTraitement->getTraitementId());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDateEnregistrement = new \DateTimeImmutable('2025-01-03 12:45:00+02');
        $this->batchTraitement->setDateEnregistrement($newDateEnregistrement);
        $this->assertEquals($newDateEnregistrement, $this->batchTraitement->getDateEnregistrement());
    }

}
