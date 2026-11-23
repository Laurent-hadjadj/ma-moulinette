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

declare(strict_types=1);

namespace App\Security;

/**
 * Résultat du contrôle d'un jeton d'API par {@see ClientTokenVerifier}.
 *
 * - Valide    : le jeton fourni correspond au jeton attendu ;
 * - Refuse    : le jeton fourni est absent, vide, d'un mauvais type ou différent du jeton attendu ;
 * - Desactive : le jeton attendu n'est pas configuré côté serveur (absent, vide ou valeur d'exemple) :
 *               le service doit être fermé, quelle que soit la valeur fournie.
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright   Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
enum ResultatJeton: string
{
    case Valide = 'valide';
    case Refuse = 'refuse';
    case Desactive = 'desactive';
}
