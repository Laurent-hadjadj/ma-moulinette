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

namespace App\Tests\Unit\Entity;

use App\Entity\Accessibility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * [Description AccessibilityValidationTest]
 *
 * MODIF 27/10/2026 : les contraintes Assert (NotBlank/Length/PositiveOrZero/
 * Choice) de l'entité Accessibility n'étaient jamais exercées par aucun test
 * — seule leur présence déclarative était vérifiée implicitement via
 * PHPStan, jamais leur comportement réel.
 */
class AccessibilityValidationTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    /**
     * mavenKey et dateEnregistrement sont des propriétés typées non
     * nullables sans valeur par défaut : le validateur doit les lire pour
     * vérifier les autres contraintes, donc elles doivent être initialisées
     * avant tout appel à validate(), sous peine d'Error PHP (« must not be
     * accessed before initialization »), indépendamment du sujet testé.
     */
    private function makeValidEntity(): Accessibility
    {
        $entity = new Accessibility();
        $entity->setMavenKey('fr.ma-moulinette:ma-moulinette');
        $entity->setDateEnregistrement(new \DateTimeImmutable('2026-10-27 12:00:00'));

        return $entity;
    }

    public function testFullyPopulatedEntityIsValid(): void
    {
        $entity = $this->makeValidEntity();
        $entity->setAccessibilityAnomalies(12);
        $entity->setAccessibilityDetteMinute(60);
        $entity->setAccessibilityRating('B');
        $entity->setModeCollecte('COLLECTE');
        $entity->setUtilisateurCollecte('admin@ma-moulinette.fr');

        $violations = $this->validator->validate($entity);

        $this->assertCount(0, $violations);
    }

    public function testEntityWithOnlyRequiredFieldsIsValid(): void
    {
        // Langage absent du projet : tous les champs nullable restent null.
        $violations = $this->validator->validate($this->makeValidEntity());

        $this->assertCount(0, $violations);
    }

    public function testBlankMavenKeyIsRejected(): void
    {
        $entity = $this->makeValidEntity();
        $entity->setMavenKey('');

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
        $this->assertSame('mavenKey', $violations[0]->getPropertyPath());
    }

    public function testMavenKeyTooLongIsRejected(): void
    {
        $entity = $this->makeValidEntity();
        $entity->setMavenKey(str_repeat('a', 256));

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
        $this->assertSame('mavenKey', $violations[0]->getPropertyPath());
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function ratingFieldsProvider(): iterable
    {
        yield 'accessibilityRating' => ['AccessibilityRating'];
        yield 'accessibilityCssRating' => ['AccessibilityCssRating'];
        yield 'accessibilityWebRating' => ['AccessibilityWebRating'];
        yield 'accessibilityJavascriptRating' => ['AccessibilityJavascriptRating'];
        yield 'accessibilityTypescriptRating' => ['AccessibilityTypescriptRating'];
    }

    #[DataProvider('ratingFieldsProvider')]
    public function testInvalidRatingLetterIsRejected(string $suffix): void
    {
        $entity = $this->makeValidEntity();
        $entity->{'set' . $suffix}('Z');

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function anomaliesFieldsProvider(): iterable
    {
        yield 'accessibilityAnomalies' => ['AccessibilityAnomalies'];
        yield 'accessibilityCssAnomalies' => ['AccessibilityCssAnomalies'];
        yield 'accessibilityWebAnomalies' => ['AccessibilityWebAnomalies'];
        yield 'accessibilityJavascriptAnomalies' => ['AccessibilityJavascriptAnomalies'];
        yield 'accessibilityTypescriptAnomalies' => ['AccessibilityTypescriptAnomalies'];
        yield 'accessibilityDetteMinute' => ['AccessibilityDetteMinute'];
    }

    #[DataProvider('anomaliesFieldsProvider')]
    public function testNegativeCounterIsRejected(string $suffix): void
    {
        $entity = $this->makeValidEntity();
        $entity->{'set' . $suffix}(-1);

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
    }

    public function testModeCollecteTooLongIsRejected(): void
    {
        $entity = $this->makeValidEntity();
        $entity->setModeCollecte(str_repeat('a', 33));

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
        $this->assertSame('modeCollecte', $violations[0]->getPropertyPath());
    }

    public function testUtilisateurCollecteTooLongIsRejected(): void
    {
        $entity = $this->makeValidEntity();
        $entity->setUtilisateurCollecte(str_repeat('a', 321));

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
        $this->assertSame('utilisateurCollecte', $violations[0]->getPropertyPath());
    }
}
