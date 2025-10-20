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

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * [Description ApiSecurityHandler]
 */
class ApiSecurityHandler implements AuthenticationEntryPointInterface, AccessDeniedHandlerInterface
{

    public function __construct(
      private LoggerInterface $logger,
      private TokenStorageInterface $tokenStorage
    )
    {
    }

    /**
     * [Description for start]
     * 🔹 Gestion des non-authentifiés (401)
     *
     * @param Request $request
     * @param AuthenticationException|null $authException
     *
     * @return Response
     *
     * Created at: 03/09/2025 17:34:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
      $this->logger->error('[API-Auth] ☠️ Tentative d’accès non autorisée à une API', [
            'path' => $request->getPathInfo(),
            'ip' =>$request->getClientIp(),
            'message' => $authException?->getMessage(),
        ]);

        // Vérifie un header spécifique pour décider si on renvoie HTTP 200 ou 403
        $useHttp200 = (bool) $request->headers->get('x-api-custom-401', false);

        $web_message = 'Votre session a expiré. Vous allez être redirigé dans quelques secondes sur la page de connexion (Erreur 401).';
        $api_message = '[API-Core] ☠️ Vous devez être authentifié pour accéder à cette ressource (Erreur 401).';

        $x_api_custom_401 = $useHttp200 ? 'X-Api-Custom-401' : 'status';

        return new JsonResponse([
            $x_api_custom_401 => $useHttp200 ? true : 'unauthorized',
            'code' => 401,
            'message' => $useHttp200 ? $web_message : $api_message,
        ], $useHttp200 ? Response::HTTP_OK : Response::HTTP_UNAUTHORIZED);
    }

    /**
     * [Description for handle]
     * 🔹 Gestion des utilisateurs authentifiés mais sans rôle suffisant (403)
     *
     * @param Request $request
     * @param AccessDeniedException $accessDeniedException
     *
     * @return Response
     *
     * Created at: 03/09/2025 17:34:08 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
      $token = $this->tokenStorage->getToken();
      $user = $token?->getUser();

      // Si pas de token ou anonyme -> session expirée -> 401
      if (!$token || !$user || $user === 'anon.') {
          return $this->start($request, null); // renvoie 401
      }

      $this->logger->error('[API-Credential] 👻 Accès refusé à une ressource API', [
              'path' => $request->getPathInfo(),
              'ip' => $request->getClientIp(),
              'user' => $request->getUser(),
              'message' => $accessDeniedException?->getMessage(),
      ]);

      // Récupère le rôle requis depuis l'attribut de la route
        $requiredRole = $request->attributes->get('role');

        $web_message = $requiredRole
          ? "Vous devez avoir le rôle <strong>$requiredRole</strong> pour accéder à cette ressource (Erreur 403)."
          : "Vous n’avez pas les droits pour accéder à cette ressource.";
        $api_message = '[API-Credential] Vous n’avez pas les droits pour accéder à cette ressource.';

        // Vérifie un header spécifique pour décider si on renvoie HTTP 200 ou 403
        $useHttp200 = (bool) $request->headers->get('X-Api-Custom-403', false);

        return new JsonResponse([
            'x-api-custom-403' => true,
            'code' => 403,
            'message' => $useHttp200 ? $web_message : $api_message,
        ], $useHttp200 ? Response::HTTP_OK : Response::HTTP_FORBIDDEN);
    }
}
