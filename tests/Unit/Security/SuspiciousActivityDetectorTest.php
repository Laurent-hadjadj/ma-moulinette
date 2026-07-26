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

namespace App\Tests\Unit\Security;

use App\Entity\Utilisateur;
use App\Security\SuspiciousActivityDetector;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class SuspiciousActivityDetectorTest extends TestCase
{
    /** @var LoggerInterface&MockObject */ private MockObject $logger;
    private SuspiciousActivityDetector $detector;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->detector = new SuspiciousActivityDetector($this->logger);
    }

    public function testNoAlertWhenRolesUnchangedByInternal(): void
    {
        $target = $this->makeUser('target@x', ['ROLE_UTILISATEUR']);
        $editor = $this->makeUser('editor@x', ['ROLE_INTERNAL']);

        $this->logger->expects($this->never())->method('warning');

        $alerts = $this->detector->analyze(
            $target,
            $editor,
            ['ROLE_UTILISATEUR'],
            ['ROLE_UTILISATEUR']
        );

        $this->assertSame([], $alerts);
    }

    public function testDetectsModificationOfInternalByNonInternal(): void
    {
        $target = $this->makeUser('target@x', ['ROLE_INTERNAL']);
        $editor = $this->makeUser('editor@x', ['ROLE_GESTIONNAIRE']);

        $this->logger->expects($this->once())->method('warning');

        $alerts = $this->detector->analyze(
            $target,
            $editor,
            ['ROLE_INTERNAL'],
            ['ROLE_INTERNAL', 'ROLE_UTILISATEUR']
        );

        $this->assertContains('MODIFICATION_INTERNAL_NON_AUTORISEE', $alerts);
    }

    public function testInternalEditorOnInternalTargetRaisesNoInternalAlert(): void
    {
        $target = $this->makeUser('target@x', ['ROLE_INTERNAL']);
        $editor = $this->makeUser('editor@x', ['ROLE_INTERNAL']);

        $alerts = $this->detector->analyze(
            $target,
            $editor,
            ['ROLE_INTERNAL'],
            ['ROLE_INTERNAL']
        );

        $this->assertNotContains('MODIFICATION_INTERNAL_NON_AUTORISEE', $alerts);
    }

    public function testDetectsSensitiveRoleAttributionByNonInternal(): void
    {
        $target = $this->makeUser('target@x', ['ROLE_UTILISATEUR']);
        $editor = $this->makeUser('editor@x', ['ROLE_GESTIONNAIRE']);

        $this->logger->expects($this->once())->method('warning');

        $alerts = $this->detector->analyze(
            $target,
            $editor,
            ['ROLE_UTILISATEUR'],
            ['ROLE_UTILISATEUR', 'ROLE_BATCH']
        );

        $this->assertContains('ATTRIBUTION_ROLE_SENSIBLE', $alerts);
    }

    public function testInternalEditorCanAssignSensitiveRolesWithoutAlert(): void
    {
        $target = $this->makeUser('target@x', ['ROLE_UTILISATEUR']);
        $editor = $this->makeUser('editor@x', ['ROLE_INTERNAL']);

        $alerts = $this->detector->analyze(
            $target,
            $editor,
            ['ROLE_UTILISATEUR'],
            ['ROLE_UTILISATEUR', 'ROLE_BATCH']
        );

        $this->assertNotContains('ATTRIBUTION_ROLE_SENSIBLE', $alerts);
    }

    public function testDetectsMassiveRoleChange(): void
    {
        $target = $this->makeUser('target@x', ['ROLE_UTILISATEUR']);
        $editor = $this->makeUser('editor@x', ['ROLE_INTERNAL']);

        $this->logger->expects($this->once())->method('warning');

        $alerts = $this->detector->analyze(
            $target,
            $editor,
            ['ROLE_UTILISATEUR'],
            ['ROLE_UTILISATEUR', 'ROLE_COLLECTE', 'ROLE_SUIVI', 'ROLE_ACTIVITY']
        );

        $this->assertContains('CHANGEMENT_MASSIF_ROLES', $alerts);
    }

    public function testDifferenceOfTwoDoesNotTriggerMassiveChange(): void
    {
        $target = $this->makeUser('target@x', ['ROLE_UTILISATEUR']);
        $editor = $this->makeUser('editor@x', ['ROLE_INTERNAL']);

        $alerts = $this->detector->analyze(
            $target,
            $editor,
            ['ROLE_UTILISATEUR'],
            ['ROLE_UTILISATEUR', 'ROLE_COLLECTE', 'ROLE_SUIVI']
        );

        $this->assertNotContains('CHANGEMENT_MASSIF_ROLES', $alerts);
    }

    public function testDetectsGestionnaireRemoval(): void
    {
        $target = $this->makeUser('target@x', ['ROLE_GESTIONNAIRE']);
        $editor = $this->makeUser('editor@x', ['ROLE_INTERNAL']);

        $this->logger->expects($this->once())->method('warning');

        $alerts = $this->detector->analyze(
            $target,
            $editor,
            ['ROLE_GESTIONNAIRE', 'ROLE_UTILISATEUR'],
            ['ROLE_UTILISATEUR']
        );

        $this->assertContains('RETRAIT_GESTIONNAIRE', $alerts);
    }

    public function testGestionnaireKeptDoesNotTriggerRemovalAlert(): void
    {
        $target = $this->makeUser('target@x', ['ROLE_GESTIONNAIRE']);
        $editor = $this->makeUser('editor@x', ['ROLE_INTERNAL']);

        $alerts = $this->detector->analyze(
            $target,
            $editor,
            ['ROLE_GESTIONNAIRE'],
            ['ROLE_GESTIONNAIRE', 'ROLE_UTILISATEUR']
        );

        $this->assertNotContains('RETRAIT_GESTIONNAIRE', $alerts);
    }

    public function testMultipleAlertsStackedInSingleCall(): void
    {
        // INTERNAL target + non-INTERNAL editor + retrait GESTIONNAIRE + diff >= 3
        $target = $this->makeUser('target@x', ['ROLE_INTERNAL']);
        $editor = $this->makeUser('editor@x', ['ROLE_GESTIONNAIRE']);

        $this->logger->expects($this->once())->method('warning');

        $alerts = $this->detector->analyze(
            $target,
            $editor,
            ['ROLE_GESTIONNAIRE', 'ROLE_UTILISATEUR', 'ROLE_COLLECTE', 'ROLE_SUIVI'],
            ['ROLE_UTILISATEUR']
        );

        $this->assertContains('MODIFICATION_INTERNAL_NON_AUTORISEE', $alerts);
        $this->assertContains('CHANGEMENT_MASSIF_ROLES', $alerts);
        $this->assertContains('RETRAIT_GESTIONNAIRE', $alerts);
    }

    public function testLoggerNotCalledWhenNoAlerts(): void
    {
        $target = $this->makeUser('target@x', ['ROLE_UTILISATEUR']);
        $editor = $this->makeUser('editor@x', ['ROLE_INTERNAL']);

        $this->logger->expects($this->never())->method('warning');

        $alerts = $this->detector->analyze(
            $target,
            $editor,
            ['ROLE_UTILISATEUR'],
            ['ROLE_UTILISATEUR', 'ROLE_COLLECTE']
        );

        $this->assertSame([], $alerts);
    }

    public function testLoggerContextIncludesEmailsAndAlerts(): void
    {
        $target = $this->makeUser('target@ma-moulinette.fr', ['ROLE_UTILISATEUR']);
        $editor = $this->makeUser('editor@ma-moulinette.fr', ['ROLE_GESTIONNAIRE']);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('Suspicious activity detected'),
                $this->callback(function (array $ctx) {
                    return $ctx['target_user'] === 'target@ma-moulinette.fr'
                        && $ctx['editor_user'] === 'editor@ma-moulinette.fr'
                        && in_array('ATTRIBUTION_ROLE_SENSIBLE', $ctx['alerts'], true);
                })
            );

        $this->detector->analyze(
            $target,
            $editor,
            ['ROLE_UTILISATEUR'],
            ['ROLE_UTILISATEUR', 'ROLE_ACTUATOR']
        );
    }

    /**
     * @param array<int, string> $roles
     */
    private function makeUser(string $courriel, array $roles): Utilisateur
    {
        $u = new Utilisateur();
        $u->setCourriel($courriel);
        $u->setRoles($roles);
        return $u;
    }
}
