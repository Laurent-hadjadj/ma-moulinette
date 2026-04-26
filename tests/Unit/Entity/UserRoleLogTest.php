<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
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
            'user@example.com',
            'editor@example.com',
            ['ROLE_UTILISATEUR'],
            ['ROLE_COLLECTE', 'ROLE_UTILISATEUR'],
            true,
            false,
            ['CHANGEMENT_MASSIF_ROLES']
        );

        $this->assertNull($log->getId());
        $this->assertSame('user@example.com', $log->getUserEmail());
        $this->assertSame('editor@example.com', $log->getEditorEmail());
        $this->assertSame(['ROLE_UTILISATEUR'], $log->getOldRoles());
        $this->assertSame(['ROLE_COLLECTE', 'ROLE_UTILISATEUR'], $log->getNewRoles());
        $this->assertTrue($log->isOldActive());
        $this->assertFalse($log->isNewActive());
        $this->assertSame(['CHANGEMENT_MASSIF_ROLES'], $log->getAlerts());
        $this->assertInstanceOf(\DateTimeImmutable::class, $log->getCreatedAt());
    }

    public function testConstructorAllowsEmptyAlerts(): void
    {
        $log = new UserRoleLog('u@x', 'e@x', [], [], false, true);

        $this->assertSame([], $log->getAlerts());
    }

    public function testSettersUpdateFields(): void
    {
        $log = new UserRoleLog('u@x', 'e@x', [], [], true, true);

        $log->setUserEmail('new@example.com');
        $log->setEditorEmail('admin@example.com');
        $log->setOldRoles(['ROLE_A']);
        $log->setNewRoles(['ROLE_B']);
        $log->setOldActive(false);
        $log->setNewActive(false);
        $log->setAlerts(['ALERT_X']);

        $date = new \DateTimeImmutable('2026-01-01 00:00:00');
        $log->setCreatedAt($date);

        $this->assertSame('new@example.com', $log->getUserEmail());
        $this->assertSame('admin@example.com', $log->getEditorEmail());
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
        $log = new UserRoleLog('u@x', 'e@x', [], [], true, true);
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $log->getCreatedAt());
        $this->assertLessThanOrEqual($after, $log->getCreatedAt());
    }
}
