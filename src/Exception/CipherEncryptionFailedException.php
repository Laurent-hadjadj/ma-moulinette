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

namespace App\Exception;

/**
 * [Description CipherEncryptionFailedException]
 * openssl_encrypt() a échoué : le mot de passe Actuator n'a pas pu être chiffré.
 * On ne persiste jamais la valeur en clair dans ce cas, l'écriture est interrompue.
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
