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

namespace App\Service;

use App\Entity\Utilisateur;

/**
 * [Description RoleManager]
 */
class RoleManagerService
{
    private const ROLE_NONE = 'ROLE_NONE';
    private const ROLE_USER = 'ROLE_UTILISATEUR';
    private const ROLE_INTERNAL = 'ROLE_INTERNAL';
    private const ROLE_GESTIONNAIRE = 'ROLE_GESTIONNAIRE';

    private const ROLES_SENSIBLES = [
        'ROLE_BATCH',
        'ROLE_ACTUATOR',
        'ROLE_ACTIVITY',
        'ROLE_GESTIONNAIRE',
        'ROLE_INTERNAL'
    ];

    /**
     * [Description for normalize]
     *
     * @param array<int, string> $roles
     * @param array<int, string> $currentRoles
     * @param Utilisateur $targetUser
     * @param Utilisateur $editor
     *
     * @return array<int, string>
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function normalize(array $roles, array $currentRoles, Utilisateur $targetUser, Utilisateur $editor): array
    {

        /** 🔐 0. Inactif = ROLE_NONE unique */
        if (!$targetUser->isActif()) {
            return [self::ROLE_NONE];
        }

        /** 🔐 1. Nettoyage ROLE_NONE si actif */
        if (in_array(self::ROLE_NONE, $roles, true)) {
            $roles = array_diff($roles, [self::ROLE_NONE]);
        }

        /** 🔐 2. Si aucun rôle → fallback */
        if (empty($roles)) {
            $roles = [self::ROLE_USER];
        }

        /** 🔐 3. ROLE_INTERNAL exclusif */
        if (in_array(self::ROLE_INTERNAL, $roles, true)) {
            return [self::ROLE_INTERNAL];
        }

        /** 🔐 4. Protection rôles sensibles */
        if (!in_array(self::ROLE_INTERNAL, $editor->getRoles(), true)) {
            $roles = array_diff($roles, self::ROLES_SENSIBLES);
        }

        /** 🔐 5. INTERNAL existant protégé */
        if (
            in_array(self::ROLE_INTERNAL, $currentRoles, true) &&
            !in_array(self::ROLE_INTERNAL, $editor->getRoles(), true)
        ) {
            return [self::ROLE_INTERNAL];
        }

        /** 🔐 6. GESTIONNAIRE persistant */
        if (
            in_array(self::ROLE_GESTIONNAIRE, $currentRoles, true) &&
            !in_array(self::ROLE_GESTIONNAIRE, $roles, true)
        ) {
            $roles[] = self::ROLE_GESTIONNAIRE;
        }

        return array_values(array_unique($roles));
    }
}
