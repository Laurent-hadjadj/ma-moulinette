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

use App\Entity\BatchProfiling;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description BatchProfilingValidatorTest]
 *
 * v2.0.0 : entite immutable -> les invalidations passent par le constructeur.
 */
class BatchProfilingValidatorTest extends KernelTestCase
{
    private function build(
        string $portefeuille = 'Equipe DEV',
        int $nbProjets = 15,
        float $tempsTotal = 42.5,
        float $tempsMoyen = 2.83,
        float $memoirePeak = 128.5,
        float $memoireMoyenne = 96.2,
        string $utilisateur = 'admin@ma-moulinette.fr'
    ): BatchProfiling {
        return new BatchProfiling(
            portefeuille: $portefeuille,
            nbProjets: $nbProjets,
            tempsTotal: $tempsTotal,
            tempsMoyen: $tempsMoyen,
            memoirePeak: $memoirePeak,
            memoireMoyenne: $memoireMoyenne,
            utilisateur: $utilisateur
        );
    }

    public function assertHasErrors(BatchProfiling $entity, int $number = 0): void
    {
        self::bootKernel();
        $errors = static::getContainer()->get('validator')->validate($entity);
        $messages = [];
        /** @var ConstraintViolation $error */
        foreach ($errors as $error) {
            $messages[] = $error->getPropertyPath() . ' => ' . $error->getMessage();
        }
        $this->assertCount($number, $errors, implode(', ', $messages));
    }

    public function testValidEntity(): void
    {
        $this->assertHasErrors($this->build(), 0);
    }

    public function testInvalidBlankPortefeuille(): void
    {
        $this->assertHasErrors($this->build(portefeuille: ''), 1);
    }

    public function testInvalidLengthPortefeuille(): void
    {
        $this->assertHasErrors($this->build(portefeuille: str_repeat('a', 65)), 1);
    }

    public function testInvalidBlankUtilisateur(): void
    {
        $this->assertHasErrors($this->build(utilisateur: ''), 1);
    }

    public function testInvalidLengthUtilisateur(): void
    {
        $this->assertHasErrors($this->build(utilisateur: str_repeat('a', 129)), 1);
    }

    public function testInvalidNegativeNbProjets(): void
    {
        $this->assertHasErrors($this->build(nbProjets: -1), 1);
    }

    public function testInvalidNegativeTempsTotal(): void
    {
        $this->assertHasErrors($this->build(tempsTotal: -0.1), 1);
    }

    public function testInvalidNegativeTempsMoyen(): void
    {
        $this->assertHasErrors($this->build(tempsMoyen: -0.1), 1);
    }

    public function testInvalidNegativeMemoirePeak(): void
    {
        $this->assertHasErrors($this->build(memoirePeak: -0.1), 1);
    }

    public function testInvalidNegativeMemoireMoyenne(): void
    {
        $this->assertHasErrors($this->build(memoireMoyenne: -0.1), 1);
    }

    public function testZerosAreValid(): void
    {
        $this->assertHasErrors(
            $this->build(nbProjets: 0, tempsTotal: 0.0, tempsMoyen: 0.0, memoirePeak: 0.0, memoireMoyenne: 0.0),
            0
        );
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass($this->build());
        $this->assertEquals(10, count($reflectionClass->getProperties()));
    }
}
