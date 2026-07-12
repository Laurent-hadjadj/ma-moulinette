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

namespace App\Security;

use App\Entity\Utilisateur;
use Psr\Log\LoggerInterface;

/**
 * [Description SuspiciousActivityDetector]
 */
class SuspiciousActivityDetector
{
    private const SENSITIVE_ROLES = [
        'ROLE_INTERNAL',
        'ROLE_BATCH',
        'ROLE_ACTUATOR',
        'ROLE_ACTIVITY',
        'ROLE_SECURITY',
        'ROLE_SECURITY_ANALYTICS'
    ];

    public function __construct(private LoggerInterface $log) {}

    /**
     * [Description for analyze]
     *
     * @param Utilisateur $target
     * @param Utilisateur $editor
     * @param array<int|string, mixed> $oldRoles
     * @param array<int|string, mixed> $newRoles
     *
     * @return array<int|string, mixed>
     *
     * Created at: 23/04/2026 14:48:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function analyze(
        Utilisateur $target,
        Utilisateur $editor,
        array $oldRoles,
        array $newRoles
    ): array {
        $alerts = [];

        if (
            in_array('ROLE_INTERNAL', $target->getRoles(), true) &&
            !in_array('ROLE_INTERNAL', $editor->getRoles(), true)
        ) {
            $alerts[] = 'MODIFICATION_INTERNAL_NON_AUTORISEE';
        }

        if (!in_array('ROLE_INTERNAL', $editor->getRoles(), true)) {
            $diff = array_diff($newRoles, $oldRoles);

            foreach ($diff as $role) {
                if (in_array($role, self::SENSITIVE_ROLES, true)) {
                    $alerts[] = 'ATTRIBUTION_ROLE_SENSIBLE';
                    break;
                }
            }
        }

        if (abs(count($newRoles) - count($oldRoles)) >= 3) {
            $alerts[] = 'CHANGEMENT_MASSIF_ROLES';
        }

        if (
            in_array('ROLE_GESTIONNAIRE', $oldRoles, true) &&
            !in_array('ROLE_GESTIONNAIRE', $newRoles, true)
        ) {
            $alerts[] = 'RETRAIT_GESTIONNAIRE';
        }

        /* MODIF 2026-05-03 : log conditionnel — on n'écrit que s'il y a des alertes (sinon bruit). */
        if (!empty($alerts)) {
            $this->log->warning('[SuspiciousActivityDetector] ⚠️ Suspicious activity detected', [
                'target_user' => $target->getCourriel(),
                'editor_user' => $editor->getCourriel(),
                'old_roles' => $oldRoles,
                'new_roles' => $newRoles,
                'alerts' => $alerts,
            ]);
        }
        return $alerts;
    }
}
