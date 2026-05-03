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

namespace App\Controller\Traits;

use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Mutualise la résolution typée de l'utilisateur courant.
 * Why: AbstractController::getUser() retourne ?UserInterface et masque les
 * méthodes propres à App\Entity\Utilisateur. Ce helper renvoie l'instance
 * concrète et lève si elle ne correspond pas.
 *
 * @method ?UserInterface getUser()
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
