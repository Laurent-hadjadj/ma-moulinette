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

namespace App\Tests\Unit\Command\Dev;

use App\Command\Dev\LdapTestCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * MODIF 2026-07-21 : régression du garde-fou d'environnement ajouté à
 * LdapTestCommand (absent jusqu'ici, contrairement aux 4 autres commandes de
 * App\Command\Dev) — la commande pouvait tourner en prod et exposait le DN
 * et le code d'erreur de bind en cas d'échec.
 */
class LdapTestCommandTest extends TestCase
{
    public function testRefusesToRunOutsideDevEnvironment(): void
    {
        $tester = new CommandTester(new LdapTestCommand('prod'));

        $exit = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exit);
        $this->assertStringContainsString('réservée à env=dev', $tester->getDisplay());
    }
}
