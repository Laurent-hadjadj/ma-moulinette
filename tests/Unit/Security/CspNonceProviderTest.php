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

use App\Security\CspNonceProvider;
use PHPUnit\Framework\TestCase;

class CspNonceProviderTest extends TestCase
{
    public function testGetNonceReturnsSameValueOnRepeatedCalls(): void
    {
        $provider = new CspNonceProvider();

        $first = $provider->getNonce();
        $second = $provider->getNonce();

        $this->assertSame($first, $second);
    }

    public function testGetNonceReturns32HexCharacters(): void
    {
        $provider = new CspNonceProvider();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $provider->getNonce());
    }

    public function testGetNonceDiffersBetweenTwoInstances(): void
    {
        $this->assertNotSame((new CspNonceProvider())->getNonce(), (new CspNonceProvider())->getNonce());
    }
}
