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

namespace App\Tests\Unit\Service;

use App\Entity\UserRoleLog;
use App\Entity\Utilisateur;
use App\Service\UserRoleLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserRoleLoggerServiceTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    private UserRoleLoggerService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->service = new UserRoleLoggerService($this->em);
    }

    public function testLogPersistsAndFlushesUserRoleLog(): void
    {
        $user = $this->makeUser('user@example.com');
        $editor = $this->makeUser('editor@example.com');

        /* MODIF 2026-05-07 : capture un UserRoleLog — PHPDoc pour intelephense. */
        /** @var UserRoleLog|null $captured */
        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($arg) use (&$captured) {
                $captured = $arg;
                return $arg instanceof UserRoleLog;
            }));

        $this->em->expects($this->once())->method('flush');

        $this->service->log(
            $user,
            $editor,
            ['ROLE_UTILISATEUR'],
            ['ROLE_COLLECTE'],
            true,
            false,
            ['ATTRIBUTION_ROLE_SENSIBLE']
        );

        $this->assertInstanceOf(UserRoleLog::class, $captured);
        $this->assertSame('user@example.com', $captured->getUserEmail());
        $this->assertSame('editor@example.com', $captured->getEditorEmail());
        $this->assertSame(['ROLE_UTILISATEUR'], $captured->getOldRoles());
        $this->assertSame(['ROLE_COLLECTE'], $captured->getNewRoles());
        $this->assertTrue($captured->isOldActive());
        $this->assertFalse($captured->isNewActive());
        $this->assertSame(['ATTRIBUTION_ROLE_SENSIBLE'], $captured->getAlerts());
    }

    public function testLogAcceptsEmptyAlerts(): void
    {
        $user = $this->makeUser('u@x');
        $editor = $this->makeUser('e@x');

        /* MODIF 2026-05-07 : capture un UserRoleLog — PHPDoc pour intelephense. */
        /** @var UserRoleLog|null $captured */
        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($arg) use (&$captured) {
                $captured = $arg;
                return true;
            }));
        $this->em->expects($this->once())->method('flush');

        $this->service->log($user, $editor, [], ['ROLE_UTILISATEUR'], false, true, []);

        $this->assertInstanceOf(UserRoleLog::class, $captured);
        $this->assertSame([], $captured->getAlerts());
    }

    private function makeUser(string $courriel): Utilisateur
    {
        $u = new Utilisateur();
        $u->setCourriel($courriel);
        return $u;
    }
}
