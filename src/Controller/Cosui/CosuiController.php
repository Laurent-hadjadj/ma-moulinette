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

namespace App\Controller\Cosui;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

use App\Service\ProjetCosuiService;

/**
 * [Description CosuiController]
 */
class CosuiController extends AbstractController
{

    private static $titre = '[COSUI]';
    private static $erreur400 = 'La requête est incorrecte (Erreur 400).';
    private static $erreur403 = 'Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).';
    private static $page = 'projet/cosui.html.twig';

    /**
     * [Description for __construct]
     *
     * Created at: 13/02/2023, 08:57:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private LoggerInterface $logger,
        private ProjetCosuiService $cosuiService)
    {
        $this->cosuiService = $cosuiService;
    }

    /**
     * [Description for addFlashAndRender]
     *
    * Ajoute un message flash de type 'notice' puis rend la page demandée.
    *
    * @param string $type    Type du message flash (ex. : success, error, warning).
    * @param string $message Message principal à afficher à l'utilisateur.
    * @param string $debug   Message de débogage (visible uniquement en DEV).
    * @param array  $render  Données à passer au template.
    *
    * @return Response
     *
     * Created at: 06/03/2025 12:17:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function addFlashAndRender(string $type, string $message, string|null $trace, array $render): Response
    {
        if (!isset(static::$titre) || !isset(static::$page)) {
            $this->logger->error("Paramètre statique 'titre' ou 'page' non défini dans addFlashAndRender().");
        }

        $this->logger->info("ℹ️ [COSUI] Ajout d’un message flash de type '{$type}' avec le titre : " . (static::$titre ?? '[non défini]'));
        $this->logger->debug("Contenu du message flash", [
            'message' => $message,
            'trace' => $trace ?? 'Pas de traces',
            'render_keys' => array_keys($render),
        ]);
        $this->addFlash('notice', [
            'type' => $type,
            'titre' => static::$titre,
            'message' => $message,
            'trace' => $trace ?? null,
        ]);

        return $this->render(static::$page, $render);
    }

    /**
     * [Description for decodeToken]
     *
     * Décode un token ROT13+base64 contenant deux parties séparées par |.
     * Retourne la seconde partie (en minuscule) si le format est correct, sinon null.
     *
     * @param string $token
     * @return string|null
     *
     * Created at: 06/03/2025 12:13:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function decodeToken(string $token): ?string
    {
        //token=BGR2ZQL5ZQLjA3kzpv5gLF1go3IfnJ5yqUEyBzkyYJAbLKD=
        //1 - b64=OTE2MDY5MDYwN3xmci5tYS1tb3VsaW5ldHRlOmxlLWNoYXQ=
        //2 - rot13=BGR2ZQL5ZQLjA3kzpv5gLF1go3IfnJ5yqUEyBzkyYJAbLKD=
        $this->logger->debug("Tentative de décodage du token", ['token_brut' => $token]);

        $string = str_rot13($token);
        $decoded = base64_decode($string, true); // `true` => return false si invalide
        if ($decoded === false) {
            $this->logger->warning("⚠️ [COSUI] Échec du décodage base64 du token", ['token_rot13' => $string]);
            return null;
        }

        $parts = preg_split("/[|]+/", $decoded);

        if (count($parts) !== 2) {
            $this->logger->warning("⚠️ [COSUI] Format de token invalide après décodage", ['decoded' => $decoded]);
            return null;
        }

        $result = strtolower($parts[1]);
        $this->logger->info("ℹ️ [COSUI] Token décodé avec succès", ['valeur_extraite' => $result]);

        return $result;
    }

    /**
     * [Description for projetCosui]
     * On ouvre la page COSUI
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:18:08 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/projet/cosui', name: 'projet_cosui', methods: 'GET')]
    public function projetCosui(Request $request): Response
    {
        $this->logger->info('ℹ️ [COSUI] Accès à /projet/cosui');

        $render = $this->cosuiService->initialRender();

        $token = $request->get('token');
        if (empty($token)) {
            $this->logger->warning('⚠️ [COSUI] Token manquant dans la requête');
            return $this->addFlashAndRender('alert', static::$erreur400, 'token', $render);
        }

        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning('⚠️ [COSUI] Accès refusé : rôle COLLECTE manquant');
            return $this->addFlashAndRender('warning', static::$erreur403, 'auth', $render);
        }

        $maven_key = $this->decodeToken($token);
        if (null === $maven_key) {
            $this->logger->error('❌ [COSUI] Échec du décodage du token');
            return $this->addFlashAndRender('alert', static::$erreur400, 'Problème de décodage du token.', $render);
        }

        $this->logger->info('ℹ️ [COSUI] Token décodé, maven_key reçu', ['maven_key' => $maven_key]);

        try {
                $result = $this->cosuiService->generateRender($maven_key);

                if (isset($result['code']) && $result['code'] !== 200) {
                    $this->logger->error($result['message'], [
                        'maven_key' => $maven_key,
                        'trace' => $result['trace'] ?? 'Non disponible'
                    ]);
                    return $this->addFlashAndRender($result['type'], $result['message'], $result['trace'] ?? '', $render);
                }
        } catch (\RuntimeException $e) {
            return $this->addFlashAndRender('critical', 'Erreur lors de la génération COSUI', $e->getMessage(), $render);
        }

        return $this->render(static::$page, $render);
    }

}
