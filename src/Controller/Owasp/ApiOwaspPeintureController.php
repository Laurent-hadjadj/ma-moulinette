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
use Symfony\Component\HttpFoundation\{JsonResponse, Response, Request};
use \Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{Owasp, HotspotOwasp, HotspotDetails};

/**
 * [Description ApiOwaspPeintureController]
 */
class ApiOwaspPeintureController extends AbstractController
{
    /** Définition des constantes */
    private static string $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static string $noData = 'Pas de données';

    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {}

    /**
     * [Description for total]
     *
     * @param array<int|string, mixed> $liste
     * @param string $severity
     *
     * @return int
     *
     * Created at: 19/10/2025 09:19:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function somme(array $liste, string|null $severity): int
    {
        $total = 0;
        for ($i = 1; $i < 11; $i++) {
            if ($severity !== null) {
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
    #[Route('/api/secure/peinture/owasp/liste', name: 'peinture_owasp_liste', methods: ['POST'])]
    public function peintureOwaspListe(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/peinture/owasp/liste");

        /** On instancie l'entityRepository */
        $owaspRepos = $this->em->getRepository(Owasp::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if (
            $data === null
            || !property_exists($data, 'maven_key') || !is_string($data->maven_key)
            || !property_exists($data, 'referential_owasp')
        ) {
            $this->logger->error("[Owasp-Peinture] ❌ Requête invalide : clé 'maven_key', 'referential_owasp' manquante ou JSON mal formé.", [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** On récupère les failles owasp */
        $map = [
            'maven_key' => $data->maven_key,
            'referential_owasp' => $data->referential_owasp
        ];
        $result = $owaspRepos->selectOwaspOrderByDateEnregistrement($map);
        if ($result['code'] !== 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête selectOwaspOrderByDateEnregistrement.', [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? self::$noData,
                'maven_key' => $data->maven_key,
                'referential_owasp' => $data->referential_owasp
            ]);

            return new JsonResponse([
                'code' => $result['code'],
                'type' => 'error',
                'message' => "Une Erreur est survenue lors de la récupération des données ({$result['code']}).",
                'trace' => $result['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        /** si on ne trouve pas la liste on retourne une erreur HTTP 406 */
        if (empty($result['liste'])) {
            $this->logger->warning("[Owasp-Peinture] ⚠️ La liste des signalements OWASP est vide.");

            return new JsonResponse([
                'code' => 406,
                'liste' => $result['liste']
            ], Response::HTTP_OK);
        }

        /** Informations */
        $ligne = $result['liste'][0];
        $referential_owasp = $ligne['referential_owasp'];

        $total = self::somme($ligne, null);
        $bloquant = self::somme($ligne, '_blocker');
        $critique = self::somme($ligne, '_critical');
        $majeur = self::somme($ligne, '_major');
        $mineur = self::somme($ligne, '_minor');

        /** On factorise les dictionnaires a1..a10 par sévérité */
        $payload = [
            'code' => 200,
            'referential_owasp' => $referential_owasp,
            'total' => $total,
            'version' => $ligne['version'],
            'date_version' => $ligne['date_version'],
            'bloquant' => $bloquant,
            'critique' => $critique,
            'majeur' => $majeur,
            'mineur' => $mineur,
        ];
        $severities = ['' => '', 'Blocker' => '_blocker', 'Critical' => '_critical', 'Major' => '_major', 'Minor' => '_minor'];
        for ($i = 1; $i <= 10; $i++) {
            foreach ($severities as $suffix => $colSuffix) {
                $payload["a{$i}{$suffix}"] = $ligne["a{$i}{$colSuffix}"];
            }
        }

        $this->logger->debug('[OWASP-Peinture] 🛠️ Liste des menaces OWASP.', [
            'data' => $payload
        ]);
        return new JsonResponse($payload, Response::HTTP_OK);
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
    #[Route('/api/secure/peinture/owasp/hotspot/info', name: 'peinture_owasp_hotspot_info', methods: ['POST'])]
    public function peintureOwaspHotspotInfo(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/peinture/owasp/hotspot/info");

        /** On instancie l'entityRepository */
        $hotspotOwaspRepos = $this->em->getRepository(HotspotOwasp::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') || !is_string($data->maven_key)) {
            $this->logger->error("[Owasp-Peinture] ❌ Requête invalide : clé 'maven_key' manquante ou JSON mal formé.", [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** On compte le nombre de hotspot REVIEWED */
        $map = [
            'maven_key' => $data->maven_key,
            'status' => 'REVIEWED'
        ];
        $reviewed = $hotspotOwaspRepos->countHotspotOwaspStatus($map);
        if ($reviewed['code'] !== 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête countHotspotOwaspStatus.', [
                'code' => $reviewed['code'],
                'erreur' => $reviewed['erreur'] ?? self::$noData,
                'maven_key' => $data->maven_key,
                'status' => 'REVIEWED'
            ]);

            return new JsonResponse([
                'code' => $reviewed['code'],
                'type' => 'error',
                'message' => "Une Erreur est survenue lors de la récupération des données pour les menaces au statut 'REVIEW' ({$reviewed['code']}).",
                'trace' => $reviewed['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        /** On compte le nombre de hotspot TO_REVIEW */
        $map = [
            'maven_key' => $data->maven_key,
            'status' => 'TO_REVIEW'
        ];
        $toReview = $hotspotOwaspRepos->countHotspotOwaspStatus($map);

        if ($toReview['code'] !== 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête countHotspotOwaspStatus.', [
                'code' => $toReview['code'],
                'erreur' => $toReview['erreur'] ?? self::$noData,
                'maven_key' => $data->maven_key,
                'status' => 'TO_REVIEW'
            ]);

            return new JsonResponse([
                'code' => $toReview['code'],
                'type' => 'error',
                'message' => "Une Erreur est survenue lors de la récupération des données pour les menaces au statut 'TO_REVIEW' ({$toReview['code']}).",
                'trace' => $toReview['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        /** On récupère le nombre de hotspot owasp par niveau de sévérité potentiel. */
        $map = ['maven_key' => $data->maven_key];
        $probability = $hotspotOwaspRepos->countHotspotOwaspProbability($map);

        if ($probability['code'] !== 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête countHotspotOwaspProbability.', [
                'code' => $probability['code'],
                'erreur' => $probability['erreur'] ?? self::$noData,
                'maven_key' => $data->maven_key,
            ]);

            return new JsonResponse([
                'code' => $probability['code'],
                'type' => 'error',
                'message' => "Une Erreur est survenue lors de la récupération des probabilité ({$probability['code']}).",
                'trace' => $probability['erreur'] ?? self::$noData
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
            'high' => $high,
            'medium' => $medium,
            'low' => $low
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
    #[Route('/api/secure/peinture/owasp/hotspot/liste', name: 'peinture_owasp_hotspot_liste', methods: ['POST'])]
    public function peintureOwaspHotspotListe(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/peinture/owasp/hotspot/liste");

        /** On instancie l'entityRepository */
        $hotspotOwaspRepos = $this->em->getRepository(HotspotOwasp::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') || !is_string($data->maven_key)) {
            $this->logger->error("[Owasp-Peinture] ❌ Requête invalide : clé 'maven_key' manquante ou JSON mal formé.", [
                'payload' => $data
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** On compte le nombre de hotspot de type OWASP au statut TO_REVIEWED */
        $map = ['maven_key' => $data->maven_key];
        $menaces = $hotspotOwaspRepos->countHotspotOwaspMenaces($map);

        if ($menaces['code'] !== 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête countHotspotOwaspMenaces.', [
                'code' => $menaces['code'],
                'erreur' => $menaces['erreur'] ?? self::$noData,
                'maven_key' => $data->maven_key,
            ]);

            return new JsonResponse([
                'code' => $menaces['code'],
                'type' => 'error',
                'message' => "Une Erreur est survenue lors de la récupération des données pour les menaces potentielles au statut 'TO_REVIEWED' ({$menaces['code']}).",
                'trace' => $menaces['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        /** On factorise la projection des totaux par menace a1..a10 */
        $payload = ['code' => 200];
        for ($i = 1; $i <= 10; $i++) {
            $payload["menaceA{$i}"] = 0;
        }
        foreach ($menaces['menaces'] as $elt) {
            $key = 'menaceA' . substr((string) $elt['menace'], 1);
            if (array_key_exists($key, $payload)) {
                $payload[$key] = $elt['total'];
            }
        }

        return new JsonResponse($payload, Response::HTTP_OK);
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
    #[Route('/api/secure/peinture/owasp/hotspot/details', name: 'peinture_owasp_hotspot_details', methods: ['POST'])]
    public function peintureOwaspHotspotDetails(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/peinture/owasp/hotspot/details");

        /** On instancie l'entityRepository */
        $hotspotDetailsRepos = $this->em->getRepository(HotspotDetails::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') || !is_string($data->maven_key)) {
            $this->logger->error("[Owasp-Peinture] ❌ Requête invalide : clé 'maven_key' manquante ou JSON mal formé.", [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** On récupère la liste des hotspots par status de la table détails. */
        $map = ['maven_key' => $data->maven_key];
        $details = $hotspotDetailsRepos->selectHotspotDetailsByStatus($map);

        if ($details['code'] !== 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête selectHotspotDetailsByStatus.', [
                'code' => $details['code'],
                'erreur' => $details['erreur'] ?? self::$noData,
                'maven_key' => $data->maven_key,
            ]);

            return new JsonResponse([
                'code' => $details['code'],
                'type' => 'error',
                'message' => "Une Erreur est survenue lors de la récupération du détail des menaces ({$details['code']}).",
                'trace' => $details['erreur'] ?? self::$noData
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
    #[Route('/api/secure/peinture/owasp/hotspot/severity', name: 'peinture_owasp_hotspot_severity', methods: ['POST'])]
    public function peintureOwaspSeverity(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/peinture/owasp/hotspot/severity");

        /** On instancie l'entityRepository */
        $hotspotOwaspRepos = $this->em->getRepository(HotspotOwasp::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si le body est correcte */
        if (
            $data === null
            || !property_exists($data, 'maven_key') || !is_string($data->maven_key)
            || !property_exists($data, 'menace') || !is_string($data->menace)
        ) {
            $this->logger->error("[Owasp-Peinture] ❌ Requête invalide : clé 'maven_key', 'menace' manquante ou JSON mal formé.", [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** On compte le nombre de faille OWASP au statut HIGH */
        $map = [
            'maven_key' => $data->maven_key,
            'menace' => $data->menace,
            'probability' => 'HIGH'
        ];
        $high = $hotspotOwaspRepos->countHotspotOwaspMenaceByStatus($map);

        if ($high['code'] !== 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête countHotspotOwaspMenaceByStatus.', [
                'code' => $high['code'],
                'erreur' => $high['erreur'] ?? self::$noData,
                'maven_key' => $data->maven_key,
                'probability' => 'HIGH',
                'menace' => $data->menace
            ]);

            return new JsonResponse([
                'code' => $high['code'],
                'type' => 'error',
                'message' => "Une Erreur est survenue lors de la récupération des données des menaces potentielles de probabilité <strong>High</strong> ({$high['code']}).",
                'trace' => $high['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        /** On compte le nombre de faille OWASP au statut MEDIUM */
        $map = [
            'maven_key' => $data->maven_key,
            'menace' => $data->menace,
            'probability' => 'MEDIUM'
        ];
        $medium = $hotspotOwaspRepos->countHotspotOwaspMenaceByStatus($map);

        if ($medium['code'] !== 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête countHotspotOwaspMenaceByStatus.', [
                'code' => $medium['code'],
                'erreur' => $medium['erreur'] ?? self::$noData,
                'maven_key' => $data->maven_key,
                'probability' => 'MEDIUM',
                'menace' => $data->menace
            ]);

            return new JsonResponse([
                'code' => $medium['code'],
                'type' => 'error',
                'message' => "Une Erreur est survenue lors de la récupération des données des menaces potentielles de probabilité <strong>Medium</strong> ({$medium['code']}).",
                'trace' => $medium['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        /**  On compte le nombre de faille OWASP au statut LOW */
        $map = [
            'maven_key' => $data->maven_key,
            'menace' => $data->menace,
            'probability' => 'LOW'
        ];
        $low = $hotspotOwaspRepos->countHotspotOwaspMenaceByStatus($map);

        if ($low['code'] !== 200) {
            $this->logger->error('[Owasp-Peinture] ❌ Échec de la requête countHotspotOwaspMenaceByStatus.', [
                'code' => $low['code'],
                'erreur' => $low['erreur'] ?? self::$noData,
                'maven_key' => $data->maven_key,
                'probability' => 'LOW',
                'menace' => $data->menace
            ]);

            return new JsonResponse([
                'code' => $low['code'],
                'type' => 'error',
                'message' => "Une Erreur est survenue lors de la récupération des données des menaces potentielles de probabilité <strong>Low</strong> ({$low['code']}).",
                'trace' => $low['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        /**
         * On vérifie la valeur des vulnérabilité de type HIGH, MEDIUM et LOW.
         * Si la valeur est null alors on initialise à 0.
         */
        $x_high = $high['nombre']['total'] ?? 0;
        $x_medium = $medium['nombre']['total'] ?? 0;
        $x_low = $low['nombre']['total'] ?? 0;

        return new JsonResponse([
            'code' => 200,
            'high' => $x_high,
            'medium' => $x_medium,
            'low' => $x_low
        ], Response::HTTP_OK);
    }
}
