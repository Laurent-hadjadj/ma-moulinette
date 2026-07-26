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

namespace App\Tests\Unit\Entity;

use App\Entity\UserRoleLog;
use PHPUnit\Framework\TestCase;

/**
 * [Description UserRoleLogTest]
 */
class UserRoleLogTest extends TestCase
{
    public function testConstructorInitializesAllFields(): void
    {
        $log = new UserRoleLog(
            'user@ma-moulinette.fr',
            'editor@ma-moulinette.fr',
            ['ROLE_UTILISATEUR'],
            ['ROLE_COLLECTE', 'ROLE_UTILISATEUR'],
            true,
            false,
            ['CHANGEMENT_MASSIF_ROLES']
        );

        /* MODIF 2026-05-05 : getId() ne peut pas etre
         * appele avant persist (typed property `int $id` non initialise). On verifie juste
         * que setId fonctionne. */
        $log->setId(42);
        $this->assertSame(42, $log->getId());
        $this->assertSame('user@ma-moulinette.fr', $log->getUserEmail());
        $this->assertSame('editor@ma-moulinette.fr', $log->getEditorEmail());
        $this->assertSame(['ROLE_UTILISATEUR'], $log->getOldRoles());
        $this->assertSame(['ROLE_COLLECTE', 'ROLE_UTILISATEUR'], $log->getNewRoles());
        $this->assertTrue($log->isOldActive());
        $this->assertFalse($log->isNewActive());
        $this->assertSame(['CHANGEMENT_MASSIF_ROLES'], $log->getAlerts());
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $log->getCreatedAt());
    }

    /* MODIF 2026-05-05 : le constructor exige 7 args
     * (entite L91), pas 6. L'argument $alerts est obligatoire (pas de defaut). */
    public function testConstructorAllowsEmptyAlerts(): void
    {
        $log = new UserRoleLog('u@x', 'e@x', [], [], false, true, []);

        $this->assertSame([], $log->getAlerts());
    }

    public function testSettersUpdateFields(): void
    {
        $log = new UserRoleLog('u@x', 'e@x', [], [], true, true, []);

        $log->setUserEmail('new@ma-moulinette.fr');
        $log->setEditorEmail('admin@ma-moulinette.fr');
        $log->setOldRoles(['ROLE_A']);
        $log->setNewRoles(['ROLE_B']);
        $log->setOldActive(false);
        $log->setNewActive(false);
        $log->setAlerts(['ALERT_X']);

        $date = new \DateTimeImmutable('2026-01-01 00:00:00');
        $log->setCreatedAt($date);

        $this->assertSame('new@ma-moulinette.fr', $log->getUserEmail());
        $this->assertSame('admin@ma-moulinette.fr', $log->getEditorEmail());
        $this->assertSame(['ROLE_A'], $log->getOldRoles());
        $this->assertSame(['ROLE_B'], $log->getNewRoles());
        $this->assertFalse($log->isOldActive());
        $this->assertFalse($log->isNewActive());
        $this->assertSame(['ALERT_X'], $log->getAlerts());
        $this->assertSame($date, $log->getCreatedAt());
    }

    public function testCreatedAtIsRecentWhenConstructed(): void
    {
        $before = new \DateTimeImmutable();
        $log = new UserRoleLog('u@x', 'e@x', [], [], true, true, []);
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $log->getCreatedAt());
        $this->assertLessThanOrEqual($after, $log->getCreatedAt());
    }
}
