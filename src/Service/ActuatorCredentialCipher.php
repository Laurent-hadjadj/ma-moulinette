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

namespace App\Service;

use Psr\Log\LoggerInterface;
use App\Exception\{CipherEncryptionFailedException, InvalidCipherKeyException};

/**
 * [Description ActuatorCredentialCipher]
 * Chiffrement réversible (AES-256-GCM) du mot de passe d'accès Actuator.
 *
 * Ce n'est pas un mot de passe de compte Ma-Moulinette : c'est un identifiant
 * utilisé pour appeler un endpoint Actuator distant (BatchCollecteActuatorController),
 * il doit donc pouvoir être récupéré en clair — un hachage (bcrypt) est inadapté ici.
 */
class ActuatorCredentialCipher
{
    private const CIPHER = 'aes-256-gcm';
    private const PREFIX = 'enc_v1:';
    private const TAG_LENGTH = 16;

    private string $key;

    public function __construct(
        #[\SensitiveParameter] string $cipherKey,
        private LoggerInterface $logger
    ) {
        $decoded = base64_decode($cipherKey, true);
        if ($decoded === false || strlen($decoded) !== 32) {
            throw new InvalidCipherKeyException();
        }
        $this->key = $decoded;
    }

    /**
     * [Description for encrypt]
     * Chiffre une valeur. Retourne null si l'entrée est null/vide (mot de passe optionnel).
     *
     * @param string|null $plaintext
     *
     * @return string|null
     *
     * Created at: 24/07/2026 17:53:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function encrypt(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return null;
        }

        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new CipherEncryptionFailedException();
        }

        return self::PREFIX . base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * [Description for decrypt]
     * Déchiffre une valeur stockée. Les valeurs historiques enregistrées en clair
     * (avant l'introduction du chiffrement) sont reconnues par l'absence du préfixe
     * et renvoyées telles quelles, pour ne pas casser les fiches existantes.
     *
     * @param string|null $stored
     *
     * @return string|null
     *
     * Created at: 24/07/2026 17:53:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function decrypt(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        if (!str_starts_with($stored, self::PREFIX)) {
            return $stored;
        }

        $raw = base64_decode(substr($stored, strlen(self::PREFIX)), true);
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($raw === false || strlen($raw) <= $ivLength + self::TAG_LENGTH) {
            $this->logger->error("[ActuatorCipher] ❌ Valeur chiffrée illisible (format invalide).");
            return null;
        }

        $iv = substr($raw, 0, $ivLength);
        $tag = substr($raw, $ivLength, self::TAG_LENGTH);
        $ciphertext = substr($raw, $ivLength + self::TAG_LENGTH);

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            $this->logger->error("[ActuatorCipher] ❌ Échec du déchiffrement (clé changée ou donnée corrompue).");
            return null;
        }

        return $plaintext;
    }
}
