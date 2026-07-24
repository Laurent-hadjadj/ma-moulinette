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

use App\Entity\GroupeUtilisateur;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description GroupeUtilisateurValidatorTest]
 *
 * Note : la contrainte UniqueEntity est bien active ici (elle interroge la
 * base réelle). Le groupe 'admin' utilisé par getEntity() est donc purgé
 * avant chaque test pour ne pas dépendre d'un éventuel résidu en base
 * (ex. laissé par une session de test manuelle contre la même base de test).
 */
class GroupeUtilisateurValidatorTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        static::getContainer()->get('doctrine')->getManager()->getConnection()
            ->executeStatement("DELETE FROM ma_moulinette.groupe_utilisateur WHERE groupe_utilisateur = 'admin'");
    }

    private function getEntity(): GroupeUtilisateur
    {
        return (new GroupeUtilisateur())
            ->setGroupeUtilisateur('admin')
            ->setDescription('Groupe administrateur')
            ->setDateEnregistrement(new \DateTimeImmutable('2024-01-01 09:00:00+01:00'));
    }

    public function assertHasErrors(GroupeUtilisateur $entity, int $number = 0): void
    {
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
        $this->assertHasErrors($this->getEntity(), 0);
    }

    public function testInvalidBlankDescription(): void
    {
        // description a NotBlank.
        $this->assertHasErrors($this->getEntity()->setDescription(''), 1);
    }

    public function testInvalidLengthEntity(): void
    {
        // groupeUtilisateur : Length(max:64)
        $this->assertHasErrors($this->getEntity()->setGroupeUtilisateur(str_repeat('a', 65)), 1);
        // groupeId : Length(max:26)
        $this->assertHasErrors($this->getEntity()->setGroupeId(str_repeat('a', 27)), 1);
        // description : Length(max:255)
        $this->assertHasErrors($this->getEntity()->setDescription(str_repeat('a', 256)), 1);
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new GroupeUtilisateur());
        $this->assertEquals(6, count($reflectionClass->getProperties()));
    }
}
