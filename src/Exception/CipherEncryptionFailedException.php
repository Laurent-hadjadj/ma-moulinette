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

namespace App\Exception;

/**
 * [Description CipherEncryptionFailedException]
 * openssl_encrypt() a échoué : le mot de passe Actuator n'a pas pu être chiffré.
 * On ne persiste jamais la valeur en clair dans ce cas, l'écriture est interrompue.
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
*/
class CipherEncryptionFailedException extends ActuatorCipherException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(
            "Échec du chiffrement du mot de passe Actuator.",
            0,
            $previous
        );
    }
}
