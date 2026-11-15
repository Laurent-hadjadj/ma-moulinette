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

namespace App\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Garde-fou : une méthode de contrôleur portant #[Route] doit être publique.
 *
 * Le routeur ne peut pas appeler une méthode `private` ou `protected` : l'URL répond 500
 * (« Method "x" on class "y" should be public and non-abstract »). Le défaut passait inaperçu quand la méthode
 * servait aussi d'aide interne (AccueilController::getListeFavoriProjet / getListeFavoriVersion).
 */
class ControllerRoutesPubliquesTest extends TestCase
{
    public function testToutesLesMethodesRouteesDesControleursSontPubliques(): void
    {
        $racine = dirname(__DIR__, 3) . '/src/Controller';
        $this->assertDirectoryExists($racine);

        $nonPubliques = [];
        $methodesRouteesVues = 0;

        $iterateur = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine, \FilesystemIterator::SKIP_DOTS));
        /** @var \SplFileInfo $fichier */
        foreach ($iterateur as $fichier) {
            if ($fichier->getExtension() !== 'php') {
                continue;
            }

            $relatif = substr(str_replace('/', '\\', str_replace('\\', '/', $fichier->getPathname())), strlen($racine) + 1, -4);
            $classe = 'App\\Controller\\' . $relatif;
            if (!class_exists($classe)) {
                continue; // trait ou fichier sans classe
            }

            foreach ((new \ReflectionClass($classe))->getMethods() as $methode) {
                if ($methode->getDeclaringClass()->getName() !== $classe) {
                    continue;
                }
                if ($methode->getAttributes(Route::class) === []) {
                    continue;
                }
                ++$methodesRouteesVues;
                if (!$methode->isPublic()) {
                    $nonPubliques[] = $classe . '::' . $methode->getName();
                }
            }
        }

        $this->assertGreaterThan(100, $methodesRouteesVues, 'Le parcours des contrôleurs ne voit presque aucune route : le test est aveugle.');
        $this->assertSame([], $nonPubliques, 'Méthodes routées non publiques (réponse 500 garantie) : ' . implode(', ', $nonPubliques));
    }
}
