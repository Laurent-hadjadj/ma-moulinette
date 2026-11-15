<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\EventSubscriber\DependencyCheckTokenSubscriber;
use App\EventSubscriber\ZapIngestTokenSubscriber;
use App\Security\ClientTokenVerifier;
use App\Security\ResultatJeton;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ClientTokenVerifierTest extends TestCase
{
    private const ATTENDU = 'jeton-de-test-0123456789';

    /* ============ échec fermé : jeton attendu non configuré ============ */

    /**
     * Quel que soit le jeton fourni, un jeton attendu non configuré ferme le service. Cas clé : la chaîne vide fournie face
     * à un jeton attendu vide, que l'ancienne comparaison `!==` acceptait.
     *
     * @return iterable<string, array{mixed, mixed}>
     */
    public static function jetonAttenduNonConfigureProvider(): iterable
    {
        $nonConfigures = [
            'null' => null,
            'chaîne vide' => '',
            'valeur d\'exemple' => 'changeme',
            'entier' => 123,
            'tableau' => ['secret'],
            'booléen faux' => false,
        ];
        $fournis = ['chaîne vide' => '', 'null' => null, 'valeur d\'exemple' => 'changeme', 'quelconque' => 'x'];

        foreach ($nonConfigures as $nomAttendu => $attendu) {
            foreach ($fournis as $nomFourni => $fourni) {
                yield "attendu $nomAttendu / fourni $nomFourni" => [$attendu, $fourni];
            }
        }
    }

    #[DataProvider('jetonAttenduNonConfigureProvider')]
    public function testJetonAttenduNonConfigureDesactiveLeService(mixed $attendu, mixed $fourni): void
    {
        $this->assertSame(ResultatJeton::Desactive, ClientTokenVerifier::verifier($attendu, $fourni));
    }

    /* ============ refus ============ */

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function jetonFourniInvalideProvider(): iterable
    {
        yield 'absent (null)' => [null];
        yield 'chaîne vide' => [''];
        yield 'entier' => [123];
        yield 'tableau' => [[self::ATTENDU]];
        yield 'booléen' => [true];
        yield 'casse différente' => [strtoupper(self::ATTENDU)];
        yield 'préfixe seul' => [substr(self::ATTENDU, 0, -1)];
        yield 'suffixe ajouté' => [self::ATTENDU . '0'];
        yield 'espace final' => [self::ATTENDU . ' '];
        yield 'espace initial' => [' ' . self::ATTENDU];
        yield 'valeur d\'exemple' => ['changeme'];
    }

    #[DataProvider('jetonFourniInvalideProvider')]
    public function testJetonFourniInvalideEstRefuse(mixed $fourni): void
    {
        $this->assertSame(ResultatJeton::Refuse, ClientTokenVerifier::verifier(self::ATTENDU, $fourni));
    }

    /* ============ acceptation ============ */

    public function testJetonIdentiqueEstValide(): void
    {
        $this->assertSame(ResultatJeton::Valide, ClientTokenVerifier::verifier(self::ATTENDU, self::ATTENDU));
    }

    public function testJetonAvecCaracteresNonAsciiEtLongEstValide(): void
    {
        $jeton = str_repeat('Jéton-ÿ-ß-€-', 40);

        $this->assertSame(ResultatJeton::Valide, ClientTokenVerifier::verifier($jeton, $jeton));
        $this->assertSame(ResultatJeton::Refuse, ClientTokenVerifier::verifier($jeton, $jeton . 'x'));
    }

    public function testJetonResemblantAUnHachageBcryptEstTraiteCommeUneChaineOpaque(): void
    {
        // Les jetons de développement du projet ont la forme d'un hachage bcrypt : aucun traitement particulier, comparaison exacte.
        $jeton = '$2a$12$' . str_repeat('A', 53);

        $this->assertSame(ResultatJeton::Valide, ClientTokenVerifier::verifier($jeton, $jeton));
        $this->assertSame(ResultatJeton::Refuse, ClientTokenVerifier::verifier($jeton, '$2a$12$' . str_repeat('B', 53)));
    }

    /* ============ contrat ============ */

    public function testValeurSentinelleEstCelleDesDepotsDeRapports(): void
    {
        // Même valeur d'exemple que les jetons des dépôts de rapports : un seul comportement « fermé » dans toute
        // l'application. Si l'un des trois change sans les autres, ce test échoue.
        $dependencyCheck = (new \ReflectionClassConstant(DependencyCheckTokenSubscriber::class, 'SENTINEL_DEV'))->getValue();
        $zap = (new \ReflectionClassConstant(ZapIngestTokenSubscriber::class, 'SENTINEL_DEV'))->getValue();

        $this->assertSame($dependencyCheck, ClientTokenVerifier::SENTINELLE);
        $this->assertSame($zap, ClientTokenVerifier::SENTINELLE);
    }

    public function testLaClasseNEstPasInstanciable(): void
    {
        $constructeur = (new \ReflectionClass(ClientTokenVerifier::class))->getConstructor();

        $this->assertNotNull($constructeur);
        $this->assertTrue($constructeur->isPrivate());
    }
}
