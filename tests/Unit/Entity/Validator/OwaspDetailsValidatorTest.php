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

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Validator;

use App\Entity\OwaspDetails;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description OwaspDetailsValidatorTest]
 *
 * Created at: 28/10/2026 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
class OwaspDetailsValidatorTest extends KernelTestCase
{
    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
    private static int $referentialOwasp = 2021;
    private static string $version = '1.2.0-RELEASE';
    private static string $dateVersion = '2024-07-10 15:26:07+02';
    private static string $ruleKey = 'java:S2077';
    private static string $categories = 'A1, A3';
    private static string $severity = 'CRITICAL';
    private static string $source = 'core';
    private static string $module = 'frontend';
    private static string $filePath = 'ma-moulinette/src/main/java/Foo.java';
    private static int $line = 60;
    private static string $message = 'Make sure using a dynamically formatted SQL query is safe here.';
    private static string $modeCollecte = 'TRAITEMENT AUTOMATIQUE';
    private static string $utilisateurCollecte = 'laurent.hadjadj@ma-moulinette.fr';
    private static string $dateEnregistrement = '2024-03-26 14:46:38+02';

    private function getEntity(): OwaspDetails
    {
        return (new OwaspDetails())
            ->setMavenKey(self::$mavenKey)
            ->setReferentialOwasp(self::$referentialOwasp)
            ->setVersion(self::$version)
            ->setDateVersion(new \DateTimeImmutable(self::$dateVersion))
            ->setRuleKey(self::$ruleKey)
            ->setCategories(self::$categories)
            ->setSeverity(self::$severity)
            ->setSource(self::$source)
            ->setModule(self::$module)
            ->setFilePath(self::$filePath)
            ->setLine(self::$line)
            ->setMessage(self::$message)
            ->setModeCollecte(self::$modeCollecte)
            ->setUtilisateurCollecte(self::$utilisateurCollecte)
            ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    }

    public function assertHasErrors(OwaspDetails $entity, int $number = 0): void
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
        $this->assertHasErrors($this->getEntity()->setRuleKey(''), 1);
        $this->assertHasErrors($this->getEntity()->setCategories(''), 1);
        $this->assertHasErrors($this->getEntity()->setSeverity(''), 1);
        $this->assertHasErrors($this->getEntity()->setSource(''), 1);
        $this->assertHasErrors($this->getEntity()->setModule(''), 1);
        $this->assertHasErrors($this->getEntity()->setFilePath(''), 1);
        // message n'est pas #[Assert\NotBlank] (contrairement à HotspotDetails) : une règle au
        // niveau fichier/architecture n'a parfois aucun message distinct à afficher.
        $this->assertHasErrors($this->getEntity()->setMessage(''), 0);
        $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
        $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
    }

    public function testValidIntegerEntity(): void
    {
        $this->assertHasErrors($this->getEntity()->setLine(0), 0);
    }

    public function testValidNullLine(): void
    {
        // line est nullable (règle au niveau fichier/architecture, pas de ligne précise) —
        // contrairement à HotspotDetails::$line, jamais null.
        $this->assertHasErrors($this->getEntity()->setLine(null), 0);
    }

    public function testInvalidIntegerEntity(): void
    {
        $this->assertHasErrors($this->getEntity()->setLine(-1), 1);
    }

    public function testInvalidTooLongFields(): void
    {
        $this->assertHasErrors($this->getEntity()->setMavenKey(str_repeat('a', 256)), 1);
        $this->assertHasErrors($this->getEntity()->setVersion(str_repeat('a', 33)), 1);
        $this->assertHasErrors($this->getEntity()->setRuleKey(str_repeat('a', 129)), 1);
        $this->assertHasErrors($this->getEntity()->setCategories(str_repeat('a', 65)), 1);
        $this->assertHasErrors($this->getEntity()->setSeverity(str_repeat('a', 9)), 1);
        $this->assertHasErrors($this->getEntity()->setSource(str_repeat('a', 17)), 1);
        $this->assertHasErrors($this->getEntity()->setModule(str_repeat('a', 17)), 1);
        $this->assertHasErrors($this->getEntity()->setFilePath(str_repeat('a', 256)), 1);
        $this->assertHasErrors($this->getEntity()->setMessage(str_repeat('a', 256)), 1);
    }

    public function testCountAttribut(): void
    {
        $entity = $this->getEntity();
        $reflectionClass = new \ReflectionClass($entity);
        $nbAttributs = count($reflectionClass->getProperties());
        $this->assertEquals($nbAttributs, 16);
    }
}
