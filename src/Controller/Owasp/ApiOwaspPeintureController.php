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

namespace App\Controller\Owasp;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Controller\Traits\RequireAuthenticatedClientTrait;
use App\Entity\Owasp;
use App\Entity\HotspotOwasp;
use App\Entity\HotspotDetails;

/**
 * [Description ApiOwaspPeintureController]
 */
class ApiOwaspPeintureController extends AbstractController
{
    use RequireAuthenticatedClientTrait;

    private $appClient;

    /** Définition des constantes */
    private static $erreur400 = "La requête est incorrecte (Erreur 400).";

    public function __construct(
        private EntityManagerInterface $em,
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
)
    {
        $this->appClient = $this->params->get('app.client');
    }

    /**
     * [Description for total]
     *
     * @param array $liste
     * @param string $severity
     *
     * @return int
     *
     * Created at: 19/10/2025 09:19:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function somme(array $liste, string|null $severity):int
    {
        $i = 1;
        $total = 0;
        for($i; $i<11; $i++){
            if ($severity != null) {
                $total += $liste["a{$i}{$severity}"];
            } else {
                $total += $liste["a{$i}"];
            }
        }
        return $total;
    }

    /**
     * [Description for peintureOwaspListe]
     * On récupère les résultats Owasp
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:19:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/owasp/liste', name: 'peinture_owasp_liste', methods: ['POST'])]
    public function peintureOwaspListe(Request $request): JsonResponse
    {
        $this->logger->info("📥 [API] Requête reçue sur /api/peinture/owasp/liste");

        // Vérifie X-App-Client
        if ($resp = $this->checkApiClient($request, $this->appClient)) {
            return $resp; // renvoie 403 si pas ok
        }

        /** On instancie l'entityRepository */
        $owaspRepos = $this->em->getRepository(Owasp::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key')
        || !property_exists($data, 'referential_owasp')) {
            $this->logger->alert("[Owasp-Peinture] ❌ Requête invalide : clé 'maven_key', 'referential_owasp' manquante ou JSON mal formé.", [
                'payload' => $data
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On récupère les failles owasp */
        $map = [
            'maven_key' => $data->maven_key,
            'referential_owasp' => $data->referential_owasp
        ];
        $request = $owaspRepos->selectOwaspOrderByDateEnregistrement($map);
        if ($request['code'] != 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête selectOwaspOrderByDateEnregistrement.',[
                'code' => $request['code'],
                'erreur' => $request['erreur'],
                'maven_key' => $data->maven_key
            ]);

            return new JsonResponse([
                'code' => $request['code'],
                'type' => 'alert',
                'message' => "Une Erreur est survenue lors de la récupération des données ({$request['code']}).",
                'trace' => $request['erreur']
            ], Response::HTTP_OK);
        }

        /** si on ne trouve pas la liste on retourne une erreur HTTP 406 */
        if (empty($request['liste'])) {
            $this->logger->warning("[Owasp-Peinture] ⚠️ La liste des signalements OWASP est vide.");

            return new JsonResponse([
                'code' => 406,
                'liste' => $request['liste']
            ], Response::HTTP_OK);
        }

        /** Informations */
        $referential_owasp = $request['liste'][0]['referential_owasp'];
        $liste = $request['liste'][0];

        $total = self::somme($liste, null);
        $bloquant = self::somme($liste, '_blocker');
        $critique = self::somme($liste, '_critical');
        $majeur = self::somme($liste, '_major');
        $mineur = self::somme($liste, '_minor');

        $data = [
                'code' => 200,
                'referential_owasp' => $referential_owasp,
                'total' => $total,
                'version' => $request['liste'][0]['version'],
                'date_version' => $request['liste'][0]['date_version'],
                'bloquant' => $bloquant,
                'critique' => $critique,
                'majeur' => $majeur,
                'mineur' => $mineur,
                'a1' => $request['liste'][0]['a1'],
                'a2' => $request['liste'][0]['a2'],
                'a3' => $request['liste'][0]['a3'],
                'a4' => $request['liste'][0]['a4'],
                'a5' => $request['liste'][0]['a5'],
                'a6' => $request['liste'][0]['a6'],
                'a7' => $request['liste'][0]['a7'],
                'a8' => $request['liste'][0]['a8'],
                'a9' => $request['liste'][0]['a9'],
                'a10' => $request['liste'][0]['a10'],
                'a1Blocker' => $request['liste'][0]['a1_blocker'],
                'a2Blocker' => $request['liste'][0]['a2_blocker'],
                'a3Blocker' => $request['liste'][0]['a3_blocker'],
                'a4Blocker' => $request['liste'][0]['a4_blocker'],
                'a5Blocker' => $request['liste'][0]['a5_blocker'],
                'a6Blocker' => $request['liste'][0]['a6_blocker'],
                'a7Blocker' => $request['liste'][0]['a7_blocker'],
                'a8Blocker' => $request['liste'][0]['a8_blocker'],
                'a9Blocker' => $request['liste'][0]['a9_blocker'],
                'a10Blocker' => $request['liste'][0]['a10_blocker'],
                'a1Critical' => $request['liste'][0]['a1_critical'],
                'a2Critical' => $request['liste'][0]['a2_critical'],
                'a3Critical' => $request['liste'][0]['a3_critical'],
                'a4Critical' => $request['liste'][0]['a4_critical'],
                'a5Critical' => $request['liste'][0]['a5_critical'],
                'a6Critical' => $request['liste'][0]['a6_critical'],
                'a7Critical' => $request['liste'][0]['a7_critical'],
                'a8Critical' => $request['liste'][0]['a8_critical'],
                'a9Critical' => $request['liste'][0]['a9_critical'],
                'a10Critical' => $request['liste'][0]['a10_critical'],
                'a1Major' => $request['liste'][0]['a1_major'],
                'a2Major' => $request['liste'][0]['a2_major'],
                'a3Major' => $request['liste'][0]['a3_major'],
                'a4Major' => $request['liste'][0]['a4_major'],
                'a5Major' => $request['liste'][0]['a5_major'],
                'a6Major' => $request['liste'][0]['a6_major'],
                'a7Major' => $request['liste'][0]['a7_major'],
                'a8Major' => $request['liste'][0]['a8_major'],
                'a9Major' => $request['liste'][0]['a9_major'],
                'a10Major' => $request['liste'][0]['a10_major'],
                'a1Minor' => $request['liste'][0]['a1_minor'],
                'a2Minor' => $request['liste'][0]['a2_minor'],
                'a3Minor' => $request['liste'][0]['a3_minor'],
                'a4Minor' => $request['liste'][0]['a4_minor'],
                'a5Minor' => $request['liste'][0]['a5_minor'],
                'a6Minor' => $request['liste'][0]['a6_minor'],
                'a7Minor' => $request['liste'][0]['a7_minor'],
                'a8Minor' => $request['liste'][0]['a8_minor'],
                'a9Minor' => $request['liste'][0]['a9_minor'],
                'a10Minor' => $request['liste'][0]['a10_minor']
        ];

        $this->logger->debug('[OWASP-Peinture] 🛠️ Liste des menaces OWASP.', [
            'data' => $data
        ]);
        return new JsonResponse($data, Response::HTTP_OK);
    }

    /**
     * [Description for peintureOwaspHotspotInfo]
     * On récupère les résultats des hotspots
       *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 22:11:35 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/owasp/hotspot/info', name: 'peinture_owasp_hotspot_info', methods: ['POST'])]
    public function peintureOwaspHotspotInfo(Request $request): JsonResponse
    {
        // Vérifie X-App-Client
        if ($resp = $this->checkApiClient($request, $this->appClient)) {
            return $resp; // renvoie 403 si pas ok
        }

        /** On instancie l'entityRepository */
        $hotspotOwaspRepos = $this->em->getRepository(HotspotOwasp::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key')) {
            $this->logger->alert("[Owasp-Peinture] ❌ Requête invalide : clé 'maven_key' manquante ou JSON mal formé.", [
                'payload' => $data
            ]);

            return new JsonResponse([
                'code' => 400,
                'type'=>'alert',
                'message'=> static::$erreur400
            ], Response::HTTP_OK);
        }

        /** On compte le nombre de hotspot REVIEWED */
        $map = [
            'maven_key' => $data->maven_key,
            'status' => 'REVIEWED'
        ];
        $reviewed = $hotspotOwaspRepos->countHotspotOwaspStatus($map);
        if ($reviewed['code'] != 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête countHotspotOwaspStatus.', [
                'code' => $reviewed['code'],
                'erreur' => $reviewed['erreur'],
                'maven_key' => $data->maven_key,
                'status' => 'REVIEWED'
            ]);

            return new JsonResponse([
                'code' => $reviewed['code'],
                'type' => 'alert',
                'message' => "Une Erreur est survenue lors de la récupération des données pour les menaces au statut 'REVIEW' ({$reviewed['code']}).",
                'trace' => $reviewed['erreur']
            ], Response::HTTP_OK);
        }

        /** On compte le nombre de hotspot TO_REVIEW */
        $map = [
            'maven_key' => $data->maven_key,
            'status'=> 'TO_REVIEW'
        ];
        $toReview = $hotspotOwaspRepos->countHotspotOwaspStatus($map);
        if ($toReview['code'] != 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête countHotspotOwaspStatus.', [
                'code' => $toReview['code'],
                'erreur' => $toReview['erreur'],
                'maven_key' => $data->maven_key,
                'status' => 'TO_REVIEW'
            ]);

            return new JsonResponse([
                'code'=>$toReview['code'],
                'type' => 'alert',
                'message' => "Une Erreur est survenue lors de la récupération des données pour les menaces au statut 'TO_REVIEW' ({$toReview['code']}).",
                'trace' => $toReview['erreur']
            ], Response::HTTP_OK);
        }

        /** On récupère le nombre de hotspot owasp par niveau de sévérité potentiel. */
        $map = ['maven_key' => $data->maven_key];
        $probability = $hotspotOwaspRepos->countHotspotOwaspProbability($map);
        if ($probability['code'] != 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête countHotspotOwaspProbability.', [
                'code' => $probability['code'],
                'erreur' => $probability['erreur'],
                'maven_key' => $data->maven_key,
            ]);

            return new JsonResponse([
                'code' => $probability['code'],
                'type' => 'alert',
                'message' => "Une Erreur est survenue lors de la récupération des probabilité ({$probability['code']}).",
                'trace' => $probability['erreur']
            ], Response::HTTP_OK);
        }

        $high = $medium = $low = 0;
        foreach ($probability['nombre'] as $elt) {
            if ($elt['probability'] == "HIGH") {
                $high = $elt['total'];
            }
            if ($elt['probability'] == "MEDIUM") {
                $medium = $elt['total'];
            }
            if ($elt['probability'] == "LOW") {
                $low = $elt['total'];
            }
        }

        return new JsonResponse([
            'code' => 200,
            'reviewed' => $reviewed['request'][0]['nombre'],
            'toReview' => $toReview['request'][0]['nombre'],
            'total' => $reviewed['request'][0]['nombre'] + $toReview['request'][0]['nombre'],
            'high' => $high, 'medium' => $medium, 'low' => $low
        ], Response::HTTP_OK);
    }

    /**
     * [Description for peintureOwaspHotspotListe]
     * On récupère les résultats de la table hotspot_owasp
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:20:20 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/owasp/hotspot/liste', name: 'peinture_owasp_hotspot_liste', methods: ['POST'])]
    public function peintureOwaspHotspotListe(Request $request): JsonResponse
    {
        // Vérifie X-App-Client
        if ($resp = $this->checkApiClient($request, $this->appClient)) {
            return $resp; // renvoie 403 si pas ok
        }

        /** On instancie l'entityRepository */
        $hotspotOwaspRepos = $this->em->getRepository(HotspotOwasp::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') ) {
            $this->logger->alert("[Owasp-Peinture] ❌ Requête invalide : clé 'maven_key' manquante ou JSON mal formé.", [
                'payload' => $data
            ]);

            return new JsonResponse([
                'code' => 400,
                'type'=>'alert',
                'message'=> static::$erreur400
            ], Response::HTTP_OK);
        }

        /** On compte le nombre de hotspot de type OWASP au statut TO_REVIEWED */
        $map = ['maven_key' => $data->maven_key];
        $menaces = $hotspotOwaspRepos->countHotspotOwaspMenaces($map);
        if ($menaces['code'] != 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête countHotspotOwaspProbability.', [
                'code' => $menaces['code'],
                'erreur' => $menaces['erreur'],
                'maven_key' => $data->maven_key,
            ]);

            return new JsonResponse([
                'code' => $menaces['code'],
                'message' => "Une Erreur est survenue lors de la récupération des données pour les menaces potentielles au statut 'TO_REVIEWED' ({$menaces['code']}).",
                'erreur' => $menaces['erreur']
            ], Response::HTTP_OK);
        }

        $menaceA1 = $menaceA2 = $menaceA3 = $menaceA4 = $menaceA5 = $menaceA6 = $menaceA7 = $menaceA8 = $menaceA9 = $menaceA10 = 0;

        foreach ($menaces['menaces'] as $elt) {
            if ($elt['menace'] === "a1") {
                $menaceA1 = $elt['total'];
            }
            if ($elt['menace'] === "a2") {
                $menaceA2 = $elt['total'];
            }
            if ($elt['menace'] === "a3") {
                $menaceA3 = $elt['total'];
            }
            if ($elt['menace'] === "a4") {
                $menaceA4 = $elt['total'];
            }
            if ($elt['menace'] === "a5") {
                $menaceA5 = $elt['total'];
            }
            if ($elt['menace'] === "a6") {
                $menaceA6 = $elt['total'];
            }
            if ($elt['menace'] === "a7") {
                $menaceA7 = $elt['total'];
            }
            if ($elt['menace'] === "a8") {
                $menaceA8 = $elt['total'];
            }
            if ($elt['menace'] === "a9") {
                $menaceA9 = $elt['total'];
            }
            if ($elt['menace'] === "a10") {
                $menaceA10 = $elt['total'];
            }
        }

        return new JsonResponse([
                'code' => 200,
                'menaceA1' => $menaceA1, 'menaceA2' => $menaceA2,
                'menaceA3' => $menaceA3, 'menaceA4' => $menaceA4,
                'menaceA5' => $menaceA5, 'menaceA6' => $menaceA6,
                'menaceA7' => $menaceA7, 'menaceA8' => $menaceA8,
                'menaceA9' => $menaceA9, 'menaceA10' => $menaceA10
            ], Response::HTTP_OK);
    }

    /**
     * [Description for peintureOwaspHotspotDetails]
     * On récupère le détails des failles de type hotspot
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:20:54 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/owasp/hotspot/details', name: 'peinture_owasp_hotspot_details', methods: ['POST'])]
    public function peintureOwaspHotspotDetails(Request $request): JsonResponse
    {
        // Vérifie X-App-Client
        if ($resp = $this->checkApiClient($request, $this->appClient)) {
            return $resp; // renvoie 403 si pas ok
        }

        /** On instancie l'entityRepository */
        $hotspotDetailsRepos = $this->em->getRepository(HotspotDetails::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key')) {
            $this->logger->alert("[Owasp-Peinture] ❌ Requête invalide : clé 'maven_key' manquante ou JSON mal formé.", [
                'payload' => $data
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$erreur400
            ], Response::HTTP_OK);
        }

        /** On récupère la liste des hotspots par status de la table détails. */
        $map = ['maven_key' => $data->maven_key];
        $details = $hotspotDetailsRepos->selectHotspotDetailsByStatus($map);

        if ($details['code'] != 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête selectHotspotDetailsByStatus.', [
                'code' => $details['code'],
                'erreur' => $details['erreur'],
                'maven_key' => $data->maven_key,
            ]);

            return new JsonResponse([
                'code' => $details['code'],
                'type' => 'alert',
                'message' => "Une Erreur est survenue lors de la récupération du détail des menaces ({$details['code']}).",
                'trace' => $details['erreur']
            ], Response::HTTP_OK);
        }

        return new JsonResponse([
            'code' => 200,
            'details' => $details
        ], Response::HTTP_OK);
    }

    /**
     * [Description for peintureOwaspSeverity]
     * On récupère le détails des failles de type hotspot
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:21:20 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/owasp/hotspot/severity', name: 'peinture_owasp_hotspot_severity', methods: ['POST'])]
    public function peintureOwaspSeverity(Request $request): JsonResponse
    {
        // Vérifie X-App-Client
        if ($resp = $this->checkApiClient($request, $this->appClient)) {
            return $resp; // renvoie 403 si pas ok
        }

        /** On instancie l'entityRepository */
        $hotspotOwaspRepos = $this->em->getRepository(HotspotOwasp::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si le body est correcte */
        if ($data === null ||
            !property_exists($data, 'maven_key') ||
            !property_exists($data, 'menace')) {
            $this->logger->alert("[Owasp-Peinture] ❌ Requête invalide : clé 'maven_key', 'menace' manquante ou JSON mal formé.", [
                'payload' => $data
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message'=> static::$erreur400
            ], Response::HTTP_OK);
        }

        /** On compte le nombre de faille OWASP au statut HIGH */
        $map = [
            'maven_key' => $data->maven_key,
            'menace' => $data->menace,
            'probability' => 'HIGH'
        ];
        $high = $hotspotOwaspRepos->countHotspotOwaspMenaceByStatus($map);

        if ($high['code'] != 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête selectHotspotDetailsByStatus.', [
                'code' => $high['code'],
                'erreur' => $high['erreur'],
                'maven_key' => $data->maven_key,
                'probability' => 'HIGH',
                'menace' => $data->menace
            ]);

            return new JsonResponse([
                'code' => $high['code'],
                'type' => 'alert',
                'message' => "Une Erreur est survenue lors de la récupération des données des menaces potentielles de probabilité <strong>High</strong> ({$high['code']}).",
                'trace' => $high['erreur']
            ], Response::HTTP_OK);
        }

        /** On compte le nombre de faille OWASP au statut MEDIUM */
        $map = [
            'maven_key' => $data->maven_key,
            'menace' => $data->menace,
            'probability' => 'MEDIUM'
        ];
        $medium = $hotspotOwaspRepos->countHotspotOwaspMenaceByStatus($map);
        if ($medium['code'] != 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête selectHotspotDetailsByStatus.', [
                'code' => $medium['code'],
                'erreur' => $medium['erreur'],
                'maven_key' => $data->maven_key,
                'probability' => 'MEDIUM',
                'menace' => $data->menace
            ]);

            return new JsonResponse([
                'maven_key' => $data->maven_key,
                'code' => $medium['code'],
                'message' => "Une Erreur est survenue lors de la récupération des données des menaces potentielles de probabilité <strong>Medium</strong> ({$medium['code']}).",
                'trace' => $medium['erreur']
            ], Response::HTTP_OK);
        }

        /**  On compte le nombre de faille OWASP au statut LOW */
        $map = [
            'maven_key' => $data->maven_key,
            'menace' => $data->menace,
            'probability' => 'LOW'
        ];
        $low = $hotspotOwaspRepos->countHotspotOwaspMenaceByStatus($map);
        if ($low['code'] != 200) {
            return new JsonResponse([
                'maven_key' => $data->maven_key,
                'code' => $low['code'],
                "Une Erreur est survenue lors de la récupération des données des menaces potentielles de probabilité <strong>Low</strong> ({$low['code']}).",
                'trace' => $low['erreur']
            ], Response::HTTP_OK);
        }

        /**
         * On vérifie la valeur des vulnérabilité de type HIGH, MEDIUMet LOW
         * Si la valeur est null alors on initialise à 0
         */
        $x_high = empty($high) ? 0 : $high['nombre']['total'];
        $x_medium = empty($medium) ? 0 : $medium['nombre']['total'];
        $x_low = empty($low) ? 0 : $low['nombre']['total'];

        return new JsonResponse([
            'code' => 200,
            'high' => $x_high,
            'medium' => $x_medium,
            'low' => $x_low
        ], Response::HTTP_OK);
    }

}
