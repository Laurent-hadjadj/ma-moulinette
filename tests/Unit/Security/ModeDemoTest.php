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

use App\Security\ModeDemo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ModeDemoTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function requetesAutoriseesProvider(): iterable
    {
        foreach (
            [
                '/', '/login', '/logout', '/logout/au-revoir', '/error', '/plan-du-site', '/mention-legale',
                '/donnees-personnelles', '/demo/indisponible', '/.well-known/security.txt', '/accueil',
                '/api/secure/preference/category', '/projet', '/projet/mes-projets', '/projet/cosui',
                '/suivi', '/suivi/set', '/suivi/rapport/pdf', '/owasp', '/owasp/detail/42', '/owasp/rapport/pdf',
                '/clean-code', '/clean-code/synthese', '/clean-code/synthese/pdf', '/dependency-check',
                '/dependency-check/dashboard', '/dependency-check/projet/fr.ma/app/1.0.0',
                '/dependency-check/projet/fr.ma/app/1.0.0/executive', '/zap', '/zap/rapport/pdf', '/activity',
                '/activity/rapport/pdf', '/preferences', '/profil', '/statistiques', '/statistiques/projet',
                '/api/public/healthcheck/status',
            ] as $chemin
        ) {
            yield "GET $chemin" => ['GET', $chemin];
        }

        yield 'HEAD /accueil' => ['HEAD', '/accueil'];
        yield 'méthode en minuscules' => ['get', '/accueil'];

        foreach (
            [
                '/api/secure/peinture/projet/mesures', '/api/secure/peinture/owasp/hotspot/details',
                '/api/secure/projet/liste', '/api/secure/liste/v1.0/version', '/api/secure/liste/v2.0/version',
                '/api/secure/get/version', '/api/secure/suivi/version/liste', '/api/secure/favori/check',
                '/api/secure/accueil/tags', '/api/secure/activity/dessin', '/api/secure/quality/langage',
                '/api/secure/quality/off', '/api/secure/status', '/api/secure/health', '/api/secure/system/info',
                '/login', '/api/secure/utilisateur/change-me', '/api/secure/preference/statut',
            ] as $chemin
        ) {
            yield "POST $chemin" => ['POST', $chemin];
        }

        // Version de référence (page de suivi) : seule écriture en PUT tolérée.
        yield 'PUT /api/secure/suivi/version/reference' => ['PUT', '/api/secure/suivi/version/reference'];

        yield 'barre finale ignorée' => ['GET', '/accueil/'];
        yield 'barres doublées ignorées' => ['GET', '//accueil'];
    }

    #[DataProvider('requetesAutoriseesProvider')]
    public function testRequeteDeLectureAutorisee(string $methode, string $chemin): void
    {
        $this->assertTrue(ModeDemo::autorise($methode, $chemin));
    }

    /**
     * Routes qui écrivent en base, déclenchent une collecte, une ingestion, l'administration ou un changement de
     * compte : elles doivent rester interdites, quelle que soit la méthode.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function requetesInterditesProvider(): iterable
    {
        $chemins = [
            '/admin', '/admin/utilisateur', '/admin/logs', '/admin/journal-roles',
            '/api/secure/collecte/mesure', '/api/secure/collecte/anomalie', '/api/secure/collecte/information',
            '/api/secure/enregistrement', '/api/secure/accueil/projet', '/api/secure/quality/profiles',
            '/api/secure/favori', '/api/secure/suivi/version/suivi', '/api/secure/suivi/mise-a-jour',
            '/api/secure/suivi/version/favori', '/api/secure/suivi/version/poubelle',
            '/api/secure/repartition/collecte',
            '/api/secure/repartition/analyse', '/api/secure/repartition/historique',
            '/api/secure/preference/favori/delete',
            '/api/secure/preference/version/delete', '/api/secure/activity/sauvegarde',
            '/api/secure/traitement/start', '/api/secure/traitement/add-pending',
            '/api/secure/dependency-check/upload', '/api/secure/zap/upload',
            '/api/secure/mot-de-passe/mise-a-jour',
            '/api/secure/admin/journal-roles/delete', '/api/secure/admin/logs/download-selection',
            '/api/public/traitement/automatique/liste', '/api/public/traitement/automatique/start',
            '/mot-de-passe/mise-a-jour', '/profil/details', '/repartition', '/actuator', '/actuator/info',
            '/actuator/12/modifier', '/rapports', '/traitement/suivi', '/traitement/profiling',
            '/statistiques/dashboard', '/statistiques/ma-moulinette', '/statistiques/utilisateur',
            '/statistique/run_batch', '/workers/pendingWorker.js',
            '/api/public/traitement/rapport/pdf/abc/1',
        ];
        foreach ($chemins as $chemin) {
            foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] as $methode) {
                yield "$methode $chemin" => [$methode, $chemin];
            }
        }
    }

    #[DataProvider('requetesInterditesProvider')]
    public function testRequeteSensibleInterdite(string $methode, string $chemin): void
    {
        $this->assertFalse(ModeDemo::autorise($methode, $chemin));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function methodesEcritureSurRouteDeLectureProvider(): iterable
    {
        foreach (['PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE', 'PURGE'] as $methode) {
            yield "$methode /accueil" => [$methode, '/accueil'];
            yield "$methode /api/secure/peinture/projet/mesures" => [$methode, '/api/secure/peinture/projet/mesures'];
        }
        // Une route de pages n'accepte pas de POST (seul /login est un formulaire).
        yield 'POST /accueil' => ['POST', '/accueil'];
        yield 'POST /projet' => ['POST', '/projet'];
        // Une lecture exposée en POST n'est pas lisible en GET.
        yield 'GET /api/secure/peinture/projet/mesures' => ['GET', '/api/secure/peinture/projet/mesures'];
        // Le changement d'avatar n'est toléré qu'en POST : la route accepte aussi GET, qui doit rester refusé.
        foreach (['GET', 'HEAD', 'PUT', 'PATCH', 'DELETE'] as $methode) {
            yield "$methode /api/secure/utilisateur/change-me" => [$methode, '/api/secure/utilisateur/change-me'];
        }
        // Les préférences : écriture en POST seulement, jamais en GET ; la catégorie se lit en GET seulement.
        foreach (['GET', 'HEAD', 'PUT', 'PATCH', 'DELETE'] as $methode) {
            yield "$methode /api/secure/preference/statut" => [$methode, '/api/secure/preference/statut'];
        }
        yield 'POST /api/secure/preference/category' => ['POST', '/api/secure/preference/category'];
        // La version de référence n'est tolérée qu'en PUT (la corbeille, le favori et la mise à jour restent interdits).
        foreach (['GET', 'HEAD', 'POST', 'PATCH', 'DELETE'] as $methode) {
            yield "$methode /api/secure/suivi/version/reference" => [$methode, '/api/secure/suivi/version/reference'];
        }
        foreach (['/api/secure/suivi/version/poubelle', '/api/secure/suivi/version/favori', '/api/secure/suivi/mise-a-jour'] as $chemin) {
            yield "PUT $chemin" => ['PUT', $chemin];
        }
        yield 'PUT reference/ailleurs' => ['PUT', '/api/secure/suivi/version/reference/ailleurs'];
        // Et seul le chemin exact : pas de variante.
        yield 'POST change-me/ailleurs' => ['POST', '/api/secure/utilisateur/change-me/ailleurs'];
        yield 'POST change-me-autre' => ['POST', '/api/secure/utilisateur/change-me-autre'];
    }

    #[DataProvider('methodesEcritureSurRouteDeLectureProvider')]
    public function testMethodeNonPrevueInterdite(string $methode, string $chemin): void
    {
        $this->assertFalse(ModeDemo::autorise($methode, $chemin));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function cheminsDetournesProvider(): iterable
    {
        yield 'remontée de répertoire' => ['/accueil/../admin'];
        yield 'remontée après un préfixe autorisé' => ['/dependency-check/../admin/utilisateur'];
        yield 'octet nul' => ["/accueil\0/../admin"];
        yield 'saut de ligne' => ["/accueil\n"];
        yield 'préfixe seul sans le séparateur' => ['/accueilx'];
        yield 'suffixe ajouté' => ['/accueil/extra'];
        yield 'point d\'entrée php' => ['/index.php/admin'];
        yield 'sous-chemin de peinture vide' => ['/api/secure/peinture/'];
        yield 'préfixe de .well-known mal formé' => ['/.well-known/../admin'];
    }

    #[DataProvider('cheminsDetournesProvider')]
    public function testCheminDetourneInterdit(string $chemin): void
    {
        $this->assertFalse(ModeDemo::autorise('GET', $chemin));
        $this->assertFalse(ModeDemo::autorise('POST', $chemin));
    }

    public function testLaClasseNEstPasInstanciable(): void
    {
        $constructeur = (new \ReflectionClass(ModeDemo::class))->getConstructor();

        $this->assertNotNull($constructeur);
        $this->assertTrue($constructeur->isPrivate());
    }
}
