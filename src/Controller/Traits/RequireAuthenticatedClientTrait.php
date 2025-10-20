<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Controller\Traits;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Vérifie que l'appel API provient bien du client autorisé.
 */
trait RequireAuthenticatedClientTrait
{
    /**
     * Vérifie l'en-tête X-App-Client.
     *
     * @param Request $request
     * @param string  $token  La valeur attendue (ex: depuis les paramètres de config)
     *
     * @return JsonResponse|null
     */
    public function checkApiClient(Request $request, string $token): ?JsonResponse
    {
        $clientHeader = $request->headers->get('X-App-Client');

        if ($clientHeader !== $token) {
            return new JsonResponse([
                'code'    => 403,
                'message' => '[API-Credential] 👻 Vous n’avez pas les droits pour accéder à cette ressource.'
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        return null; // ✅ tout est ok
    }
}
