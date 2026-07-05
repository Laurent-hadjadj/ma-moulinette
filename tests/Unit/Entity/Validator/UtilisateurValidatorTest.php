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

use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description UtilisateurValidatorTest]
 *
 * v2.0.0 : couvre les contraintes Assert sur prenom, nom, courriel,
 * password, groupeUtilisateur, groupeId, resetPasswordCount.
 */
class UtilisateurValidatorTest extends KernelTestCase
{
    public static bool $resetPassword = true;
    public static int $resetPasswordCount = 1;
    public static string $avatar = 'chiffre/01.png';
    public static string $prenom = 'Laurent';
    public static string $nom = 'HADJADJ';
    public static string $courriel = 'laurent.hadjadj@ma-petite-entreprise.fr';
    public static string $pass = '$2y$13$6n72QhYwz.iufebkV.XaAOO4IOm3zOYcfzPUmal.jDTs8/QFq1p4K';
    public static bool $actif = true;
    public static array $roles = ["ROLE_GESTIONNAIRE"];
    public static string $groupeUtilisateur = 'admin';
    public static string $groupeId = '01HK7XMKQGM3F5XZJ4S6T7VWE2';
    public static array $listeGroupeFonctionnel = [];
    public static array $preference = ['{
        "statut":{"projet":false,"favori":false,"version":false},
        "projet":[],"favori":[],"version":[]}'];
    public static string $dateModification = '1981-01-01 00:00:00';
    public static string $dateEnregistrement = '1980-01-01 00:00:00';

    public function getEntity(): Utilisateur
    {
        return (new Utilisateur())
            ->setResetPassword(self::$resetPassword)
            ->setResetPasswordCount(self::$resetPasswordCount)
            ->setAvatar(self::$avatar)
            ->setPrenom(self::$prenom)
            ->setNom(self::$nom)
            ->setCourriel(self::$courriel)
            ->setPassword(self::$pass)
            ->setActif(self::$actif)
            ->setRoles(self::$roles)
            ->setGroupeUtilisateur(self::$groupeUtilisateur)
            ->setGroupeId(self::$groupeId)
            ->setListeGroupeFonctionnel(self::$listeGroupeFonctionnel)
            ->setPreference(self::$preference)
            ->setDateModification(new \DateTime(self::$dateModification))
            ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    }

    public function assertHasErrors(Utilisateur $entity, int $number = 0, ?array $groups = ['default']): void
    {
        self::bootKernel();
        $errors = static::getContainer()->get('validator')->validate($entity, null, $groups);
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
        $this->assertHasErrors($this->getEntity()->setPrenom(''), 1, ['default']);
        $this->assertHasErrors($this->getEntity()->setNom(''), 1, ['default']);
        // Symfony court-circuite Email quand la valeur est vide => 1 seule violation NotBlank
        $this->assertHasErrors($this->getEntity()->setCourriel(''), 1, ['default']);
        $this->assertHasErrors($this->getEntity()->setPassword(''), 1, ['default']);
        // groupeId : NotBlank declare sans `groups` => valider en groupe Default automatique (null)
        $this->assertHasErrors($this->getEntity()->setGroupeId(''), 1, null);
    }

    public function testInvalidEmailCourriel(): void
    {
        $this->assertHasErrors($this->getEntity()->setCourriel('not-an-email'), 1, ['default']);
    }

    public function testInvalidLengthGroupeUtilisateur(): void
    {
        // Length declare sans `groups` => valider en groupe Default automatique (null)
        $this->assertHasErrors($this->getEntity()->setGroupeUtilisateur(str_repeat('a', 65)), 1, null);
    }

    public function testInvalidLengthGroupeId(): void
    {
        // Length declare sans `groups` => valider en groupe Default automatique (null)
        $this->assertHasErrors($this->getEntity()->setGroupeId(str_repeat('a', 27)), 1, null);
    }

    public function testValidBlankOptionalFields(): void
    {
        // avatar, listeGroupeFonctionnel, preference, roles ne portent pas de NotBlank.
        $this->assertHasErrors($this->getEntity()->setAvatar(''), 0);
        $this->assertHasErrors($this->getEntity()->setListeGroupeFonctionnel([]), 0);
        $this->assertHasErrors($this->getEntity()->setPreference([]), 0);
        $this->assertHasErrors($this->getEntity()->setRoles([]), 0);
    }

    public function testValidIntegerEntity(): void
    {
        // resetPasswordCount n'a pas de contrainte explicite ; toute valeur int est valide.
        $this->assertHasErrors($this->getEntity()->setResetPasswordCount(0), 0);
        $this->assertHasErrors($this->getEntity()->setResetPasswordCount(42), 0);
    }

    public function testValidBooleanEntity(): void
    {
        $this->assertHasErrors($this->getEntity()->setActif(true), 0);
        $this->assertHasErrors($this->getEntity()->setActif(false), 0);
        $this->assertHasErrors($this->getEntity()->setResetPassword(true), 0);
        $this->assertHasErrors($this->getEntity()->setResetPassword(false), 0);
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new Utilisateur());
        $this->assertEquals(17, count($reflectionClass->getProperties()));
    }
}
