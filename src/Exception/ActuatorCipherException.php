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

use RuntimeException;

/**
 * [Description ActuatorCipherException]
 * Exception de base du chiffrement des identifiants Actuator
 * (App\Service\ActuatorCredentialCipher).
 */
class ActuatorCipherException extends RuntimeException
{
}
