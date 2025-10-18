<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2024.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Service;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * [Description TokenService]
 */
class TokenService
{

    public function __construct(
      private CsrfTokenManagerInterface $csrfTokenManager,
      private LoggerInterface $logger)
    {
      $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * [Description for generateToken]
     *
     * @param array $data
     *
     * @return string
     *
     * Created at: 08/09/2025 16:27:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function generateToken(array $data): string
    {
        $tokenData = base64_encode(json_encode($data));
        $csrfToken = $this->csrfTokenManager->getToken('intention')->getValue();
        return $csrfToken . '.' . $tokenData;
    }

    /**
     * [Description for decodeToken]
     *
     * @param string $token
     *
     * @return array
     *
     * Created at: 08/09/2025 16:27:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function decodeToken(string $token): array
    {
        /** token doit disposé d'un séparateur de type point */
        $parts = explode('.', $token, 2);
        try {
          if (count($parts) !== 2) {
              $this->logger->critical(
                "[Welcome] ℹ️ Le token a été correctement décodé.",
                ['parts' =>  $parts]
              );
          }
        } catch (\InvalidArgumentException $e){
              $this->logger->critical("[Welcome] 🔴 Le token est incorrect ou mal formé.", [
                      'token' =>  $token ?? 'trop pourri',
                      'erreur'  => $e->getMessage()]);
              throw new \InvalidArgumentException('Invalid token format');
        }

        list($csrfToken, $tokenData) = $parts;
        if ($this->csrfTokenManager->isTokenValid(new CsrfToken('intention', $csrfToken))) {
            $this->logger->critical("[Welcome] ℹ️ Le token est correcte.", ['parts' =>  $parts]);
            return json_decode(base64_decode($tokenData), true);
        }
        throw new \InvalidArgumentException('Invalid token');
    }
}
