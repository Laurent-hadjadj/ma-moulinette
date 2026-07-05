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

use App\Entity\Utilisateur;
use App\Service\RoleManagerService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RoleManagerService::class)]
final class RoleManagerServiceTest extends TestCase
{
    private RoleManagerService $service;

    protected function setUp(): void
    {
        $this->service = new RoleManagerService();
    }

    public function testInactiveTargetUserAlwaysGetsRoleNone(): void
    {
        $target = $this->makeUser(actif: false, roles: ['ROLE_INTERNAL']);
        $editor = $this->makeUser(actif: true, roles: ['ROLE_INTERNAL']);

        $result = $this->service->normalize(['ROLE_GESTIONNAIRE'], ['ROLE_INTERNAL'], $target, $editor);

        self::assertSame(['ROLE_NONE'], $result);
    }

    public function testRoleNoneStrippedWhenTargetIsActive(): void
    {
        $target = $this->makeUser(actif: true);
        $editor = $this->makeUser(actif: true, roles: ['ROLE_INTERNAL']);

        $result = $this->service->normalize(['ROLE_NONE', 'ROLE_UTILISATEUR'], [], $target, $editor);

        self::assertNotContains('ROLE_NONE', $result);
        self::assertContains('ROLE_UTILISATEUR', $result);
    }

    public function testEmptyRolesFallsBackToRoleUtilisateur(): void
    {
        $target = $this->makeUser(actif: true);
        $editor = $this->makeUser(actif: true, roles: ['ROLE_INTERNAL']);

        $result = $this->service->normalize([], [], $target, $editor);

        self::assertSame(['ROLE_UTILISATEUR'], $result);
    }

    public function testRoleInternalIsExclusive(): void
    {
        $target = $this->makeUser(actif: true);
        $editor = $this->makeUser(actif: true, roles: ['ROLE_INTERNAL']);

        $result = $this->service->normalize(
            ['ROLE_INTERNAL', 'ROLE_GESTIONNAIRE', 'ROLE_BATCH'],
            [],
            $target,
            $editor
        );

        self::assertSame(['ROLE_INTERNAL'], $result);
    }

    public function testSensitiveRolesAreStrippedWhenEditorIsNotInternal(): void
    {
        $target = $this->makeUser(actif: true);
        $editor = $this->makeUser(actif: true, roles: ['ROLE_GESTIONNAIRE']);

        $result = $this->service->normalize(
            ['ROLE_UTILISATEUR', 'ROLE_BATCH', 'ROLE_ACTUATOR', 'ROLE_ACTIVITY'],
            [],
            $target,
            $editor
        );

        self::assertContains('ROLE_UTILISATEUR', $result);
        self::assertNotContains('ROLE_BATCH', $result);
        self::assertNotContains('ROLE_ACTUATOR', $result);
        self::assertNotContains('ROLE_ACTIVITY', $result);
    }

    public function testSensitiveRolesAreKeptWhenEditorIsInternal(): void
    {
        $target = $this->makeUser(actif: true);
        $editor = $this->makeUser(actif: true, roles: ['ROLE_INTERNAL']);

        $result = $this->service->normalize(
            ['ROLE_UTILISATEUR', 'ROLE_BATCH', 'ROLE_ACTUATOR'],
            [],
            $target,
            $editor
        );

        self::assertContains('ROLE_BATCH', $result);
        self::assertContains('ROLE_ACTUATOR', $result);
    }

    public function testExistingInternalRoleIsProtectedFromNonInternalEditor(): void
    {
        $target = $this->makeUser(actif: true);
        $editor = $this->makeUser(actif: true, roles: ['ROLE_GESTIONNAIRE']);

        // L'editor non-INTERNAL essaie de retirer le rôle INTERNAL → bloqué
        $result = $this->service->normalize(
            ['ROLE_UTILISATEUR'],
            ['ROLE_INTERNAL'],
            $target,
            $editor
        );

        self::assertSame(['ROLE_INTERNAL'], $result);
    }

    public function testGestionnaireIsPersistedWhenAlreadyPresent(): void
    {
        $target = $this->makeUser(actif: true);
        $editor = $this->makeUser(actif: true, roles: ['ROLE_INTERNAL']);

        $result = $this->service->normalize(
            ['ROLE_UTILISATEUR'],
            ['ROLE_GESTIONNAIRE'],
            $target,
            $editor
        );

        self::assertContains('ROLE_GESTIONNAIRE', $result);
        self::assertContains('ROLE_UTILISATEUR', $result);
    }

    public function testResultIsDeduplicatedAndReindexed(): void
    {
        $target = $this->makeUser(actif: true);
        $editor = $this->makeUser(actif: true, roles: ['ROLE_INTERNAL']);

        $result = $this->service->normalize(
            ['ROLE_UTILISATEUR', 'ROLE_UTILISATEUR', 'ROLE_BATCH'],
            [],
            $target,
            $editor
        );

        self::assertSame(array_values(array_unique($result)), $result);
    }

    /**
     * @param list<string> $roles
     */
    private function makeUser(bool $actif, array $roles = []): Utilisateur
    {
        $user = new Utilisateur();
        $user->setActif($actif);
        $user->setRoles($roles);

        return $user;
    }
}
