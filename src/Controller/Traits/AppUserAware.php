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

namespace App\Controller\Traits;

use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * [Description AppUserAware]
 * Mutualise la résolution typée de l'utilisateur courant.
 * Why: AbstractController::getUser() retourne ?UserInterface et masque les
 * méthodes propres à App\Entity\Utilisateur. Ce helper renvoie l'instance
 * concrète et lève si elle ne correspond pas.
 *
 * @method ?UserInterface getUser()
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
trait AppUserAware
{
    protected function appUser(): Utilisateur
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw new AccessDeniedException('Aucun utilisateur authentifié n’est de type App\\Entity\\Utilisateur.');
        }
        return $user;
    }
}
