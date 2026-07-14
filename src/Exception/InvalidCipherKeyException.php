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
 * [Description InvalidCipherKeyException]
 * La clé de chiffrement fournie (ACTUATOR_CIPHER_KEY) n'est pas exploitable :
 * elle doit contenir 32 octets encodés en base64.
 *
 * Erreur de configuration : elle est levée à la construction du service, donc au
 * démarrage du conteneur, et n'est pas censée être rattrapée à l'exécution.
 */
class InvalidCipherKeyException extends ActuatorCipherException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(
            "La variable d'environnement ACTUATOR_CIPHER_KEY doit contenir une clé de 32 octets encodée en base64 (générer via : openssl rand -base64 32).",
            0,
            $previous
        );
    }
}
