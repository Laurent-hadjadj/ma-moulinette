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

namespace App\Tests\Integration\Controller;

use App\EventSubscriber\DemoModeSubscriber;
use App\Tests\Support\AbstractWebTestCase;

/**
 * Page « Action indisponible dans l'instance de démonstration » : publique, rendue avec le gabarit de l'application.
 */
class DemoControllerTest extends AbstractWebTestCase
{
    public function testLaPageEstPubliqueEtAfficheLeMessage(): void
    {
        $this->client->request('GET', '/demo/indisponible');

        // Non-régression : sans règle « PUBLIC_ACCESS », un visiteur serait renvoyé vers /login et ne verrait jamais le message.
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('main h2', DemoModeSubscriber::MESSAGE);
    }

    public function testLaPageUtiliseLeGabaritDeBaseAvecEnTeteEtPiedDePage(): void
    {
        $this->client->request('GET', '/demo/indisponible');

        $this->assertResponseIsSuccessful();
        // En-tête commun (nom de l'application) et pied de page commun (liens légaux).
        $this->assertSelectorExists('a.ma-moulinette');
        $this->assertSelectorExists('footer');
        $this->assertSelectorExists('main a.button[href$="/accueil"]');
    }

    public function testSeulLeVerbeGetEstAccepte(): void
    {
        $this->client->request('POST', '/demo/indisponible');

        $this->assertResponseStatusCodeSame(405);
    }
}
