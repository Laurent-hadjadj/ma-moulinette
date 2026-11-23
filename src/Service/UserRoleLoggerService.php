<?php

declare(strict_types=1);

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

use App\Entity\UserRoleLog;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;

/**
 * [Description UserRoleLogger]
 */
class UserRoleLoggerService
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * [Description for log]
     *
     * @param Utilisateur $user
     * @param Utilisateur $editor
     * @param array<int, string> $oldRoles
     * @param array<int, string> $newRoles
     * @param bool $oldActive
     * @param bool $newActive
     * @param array<int, string> $alerts
     *
     * @return void
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function log(
        Utilisateur $user,
        Utilisateur $editor,
        array $oldRoles,
        array $newRoles,
        bool $oldActive,
        bool $newActive,
        array $alerts
    ): void {
        $log = new UserRoleLog(
            $user->getCourriel(),
            $editor->getCourriel(),
            $oldRoles,
            $newRoles,
            $oldActive,
            $newActive,
            $alerts
        );

        $this->em->persist($log);
        $this->em->flush();
    }
}
