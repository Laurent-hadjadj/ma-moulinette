<?php

declare(strict_types=1);

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

namespace App\Service;

use App\Entity\UserRoleLog;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;

/**
 * [Description UserRoleLoggerService]
 */
class UserRoleLoggerService
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * [Description for log]
     *
     * @param Utilisateur $user
     * @param Utilisateur $editor
     * @param array $oldRoles
     * @param array $newRoles
     * @param bool $oldActive
     * @param bool $newActive
     *
     * @return void
     *
     * Created at: 23/04/2026 14:37:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function log(
        Utilisateur $user,
        Utilisateur $editor,
        array $oldRoles,
        array $newRoles,
        bool $oldActive,
        bool $newActive,
        array $alerts): void
    {
        $log = new UserRoleLog(
            $user->getCourriel(),
            $editor->getCourriel(),
            $oldRoles,
            $newRoles,
            $oldActive,
            $newActive,
            $alerts);

        $this->em->persist($log);
        $this->em->flush();
    }
}
