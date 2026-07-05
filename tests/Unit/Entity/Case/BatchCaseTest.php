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

namespace App\Tests\Unit\Entity\Case;

use App\Entity\Batch;
use PHPUnit\Framework\TestCase;

/**
 * [Description BatchCaseTest]
 */
class BatchCaseTest extends TestCase
{
    private Batch $batch;

    private static bool $automatique = false;
    private static string $titre = 'mon-batch à moi';
    private static string $description = 'Mon batch à moi';
    private static string $responsable = 'Laurent HADJADJ';
    private static string $portefeuille = 'application-ma-moulinette';
    private static int $nombreProjet = 4;
    private static string $execution = 'OK';
    private static string $dateModification = '2025-01-02 12:00:00+02';
    private static string $dateEnregistrement = '2024-07-31 12:27:05+02';

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

    /* MODIF 2026-06-08 : isActivated/setActivated non testés jusqu'ici. */
    public function testSettingAndGettingActivated(): void
    {
        $this->batch->setActivated(true);
        $this->assertTrue($this->batch->isActivated());
        $this->batch->setActivated(false);
        $this->assertFalse($this->batch->isActivated());
    }

    /* MODIF 2026-06-08 : getResponsableShort/setResponsableShort non testés. */
    public function testSettingAndGettingResponsableShort(): void
    {
        $this->batch->setResponsableShort('JD');
        $this->assertSame('JD', $this->batch->getResponsableShort());
    }

    /* MODIF 2026-06-08 : getTraitementId/setTraitementId non testés. */
    public function testSettingAndGettingTraitementId(): void
    {
        $ulid = new \Symfony\Component\Uid\Ulid();
        $this->batch->setTraitementId($ulid);
        $this->assertSame($ulid, $this->batch->getTraitementId());
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
