<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

// Gestion de accès aux API
use Symfony\Component\HttpFoundation\JsonResponse;

// Accès aux tables SLQLite
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Main\Owasp;
use App\Entity\Main\HotspotOwasp;
use App\Entity\Main\HotspotDetails;



/**
 * [Description ApiOwaspPeintureController]
 */
class ApiOwaspPeintureController extends AbstractController
{

    /**
     * [Description for __construct]
     * EntityManagerInterface = em
     *
     *
     * Created at: 13/02/2023, 08:54:50 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(private EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * [Description for peintureOwaspListe]
     * On récupère les résultats Owasp
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:19:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/owasp/liste', name: 'peinture_owasp_liste', methods: ['POST'])]
    public function peintureOwaspListe(Request $request): response
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null) {
            return $response->setData(['data' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'mode')) {
            return $response->setData(['mode' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'maven_key')) {
            return $response->setData(['maven_key' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }

        /** On récupère les failles owasp */
        $map=['maven_key'=>$data->maven_key];
        $owasp = $this->em->getRepository(Owasp::class);
        $request=$owasp->selectOwaspOrderByDateEnregistrement($data->mode, $map);
        if ($request['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'maven_key' => $data->maven_key,
                'code'=>$request['code'], 'erreur' => $request['erreur'],
                Response::HTTP_OK]);
        }

        /** si on ne trouve pas la liste on retourne une erreur HTTP 406 */
        if (empty($request['liste'])) {
            return $response->setData(['mode'=>$data->mode, 'code' => 406, 'liste' => $request['liste'], Response::HTTP_OK]);
        }

        /** Informations */
        $total = $request['liste'][0]['a1'] + $request['liste'][0]['a2'] + $request['liste'][0]['a3'] + $request['liste'][0]['a4']
                    + $request['liste'][0]['a5'] + $request['liste'][0]['a6'] + $request['liste'][0]['a7'] + $request['liste'][0]['a8']
                    + $request['liste'][0]['a9'] + $request['liste'][0]['a10'];

        $bloquant = $request['liste'][0]['a1_blocker'] + $request['liste'][0]['a2_blocker'] + $request['liste'][0]['a3_blocker']
                    + $request['liste'][0]['a4_blocker'] + $request['liste'][0]['a5_blocker'] + $request['liste'][0]['a6_blocker']
                    + $request['liste'][0]['a7_blocker'] + $request['liste'][0]['a8_blocker'] + $request['liste'][0]['a9_blocker']
                    + $request['liste'][0]['a10_blocker'];

        $critique = $request['liste'][0]['a1_critical'] + $request['liste'][0]['a2_critical']
                    + $request['liste'][0]['a3_critical'] + $request['liste'][0]['a4_critical']
                    + $request['liste'][0]['a5_critical'] + $request['liste'][0]['a6_critical']
                    + $request['liste'][0]['a7_critical'] + $request['liste'][0]['a8_critical']
                    + $request['liste'][0]['a9_critical'] + $request['liste'][0]['a10_critical'];

        $majeur = $request['liste'][0]['a1_major'] + $request['liste'][0]['a2_major'] + $request['liste'][0]['a3_major']
                    + $request['liste'][0]['a4_major'] + $request['liste'][0]['a5_major'] + $request['liste'][0]['a6_major']
                    + $request['liste'][0]['a7_major'] + $request['liste'][0]['a8_major'] + $request['liste'][0]['a9_major']
                    + $request['liste'][0]['a10_major'];

        $mineur = $request['liste'][0]['a1_minor'] + $request['liste'][0]['a2_minor'] + $request['liste'][0]['a3_minor']
                    + $request['liste'][0]['a4_minor'] + $request['liste'][0]['a5_minor'] + $request['liste'][0]['a6_minor']
                    + $request['liste'][0]['a7_minor'] + $request['liste'][0]['a8_minor'] + $request['liste'][0]['a9_minor']
                    + $request['liste'][0]['a10_minor'];

        return $response->setData(
            [
                'mode' => $data->mode, 'code' => 200,
                'total' => $total,
                'bloquant' => $bloquant, 'critique' => $critique, 'majeur' => $majeur, 'mineur' => $mineur,
                'a1' => $request['liste'][0]['a1'], 'a2' => $request['liste'][0]['a2'], 'a3' => $request['liste'][0]['a3'],
                'a4' => $request['liste'][0]['a4'], 'a5' => $request['liste'][0]['a5'], 'a6' => $request['liste'][0]['a6'],
                'a7' => $request['liste'][0]['a7'], 'a8' => $request['liste'][0]['a8'], 'a9' => $request['liste'][0]['a9'],
                'a10' => $request['liste'][0]['a10'],
                'a1Blocker' => $request['liste'][0]['a1_blocker'], 'a2Blocker' => $request['liste'][0]['a2_blocker'],
                'a3Blocker' => $request['liste'][0]['a3_blocker'], 'a4Blocker' => $request['liste'][0]['a4_blocker'],
                'a5Blocker' => $request['liste'][0]['a5_blocker'], 'a6Blocker' => $request['liste'][0]['a6_blocker'],
                'a7Blocker' => $request['liste'][0]['a7_blocker'], 'a8Blocker' => $request['liste'][0]['a8_blocker'],
                'a9Blocker' => $request['liste'][0]['a9_blocker'], 'a10Blocker' => $request['liste'][0]['a10_blocker'],
                'a1Critical' => $request['liste'][0]['a1_critical'], 'a2Critical' => $request['liste'][0]['a2_critical'],
                'a3Critical' => $request['liste'][0]['a3_critical'], 'a4Critical' => $request['liste'][0]['a4_critical'],
                'a5Critical' => $request['liste'][0]['a5_critical'], 'a6Critical' => $request['liste'][0]['a6_critical'],
                'a7Critical' => $request['liste'][0]['a7_critical'], 'a8Critical' => $request['liste'][0]['a8_critical'],
                'a9Critical' => $request['liste'][0]['a9_critical'], 'a10Critical' => $request['liste'][0]['a10_critical'],
                'a1Major' => $request['liste'][0]['a1_major'], 'a2Major' => $request['liste'][0]['a2_major'],
                'a3Major' => $request['liste'][0]['a3_major'], 'a4Major' => $request['liste'][0]['a4_major'],
                'a5Major' => $request['liste'][0]['a5_major'], 'a6Major' => $request['liste'][0]['a6_major'],
                'a7Major' => $request['liste'][0]['a7_major'], 'a8Major' => $request['liste'][0]['a8_major'],
                'a9Major' => $request['liste'][0]['a9_major'], 'a10Major' => $request['liste'][0]['a10_major'],
                'a1Minor' => $request['liste'][0]['a1_minor'], 'a2Minor' => $request['liste'][0]['a2_minor'],
                'a3Minor' => $request['liste'][0]['a3_minor'], 'a4Minor' => $request['liste'][0]['a4_minor'],
                'a5Minor' => $request['liste'][0]['a5_minor'], 'a6Minor' => $request['liste'][0]['a6_minor'],
                'a7Minor' => $request['liste'][0]['a7_minor'], 'a8Minor' => $request['liste'][0]['a8_minor'],
                'a9Minor' => $request['liste'][0]['a9_minor'], 'a10Minor' => $request['liste'][0]['a10_minor'],
                Response::HTTP_OK
            ]
        );
    }

    /**
     * [Description for peintureOwaspHotspotInfo]
     * On récupère les résultats des hotspots
       *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 22:11:35 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/owasp/hotspot/info', name: 'peinture_owasp_hotspot_info', methods: ['POST'])]
    public function peintureOwaspHotspotInfo(Request $request): response
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null) {
            return $response->setData(['data' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'mode')) {
            return $response->setData(['mode' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'maven_key')) {
            return $response->setData(['maven_key' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }

        /** On récupère le nombre de hotspost en fonction du status */
        $hotspotOwasp = $this->em->getRepository(HotspotOwasp::class);

        /** On compte le nombre de hotspot REVIEWED */
        $map=['maven_key'=>$data->maven_key, 'status'=>'REVIEWED'];
        $reviewed=$hotspotOwasp->countHotspotOwaspStatus($data->mode, $map);
        if ($reviewed['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'maven_key' => $data->maven_key,
                'code'=>$reviewed['code'], 'erreur' => $request['erreur'],
                Response::HTTP_OK]);
        }

        /** On compte le nombre de hotspot TO_REVIEW */
        $map=['maven_key'=>$data->maven_key, 'status'=>'TO_REVIEW'];
        $toReview=$hotspotOwasp->countHotspotOwaspStatus($data->mode, $map);
        if ($toReview['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'maven_key' => $data->maven_key,
                'code'=>$toReview['code'], 'erreur' => $toReview['erreur'],
                Response::HTTP_OK]);
        }

        /** On récupère le nombre de hotspot owasp par niveau de sévérité potentiel. */
        $map=['maven_key'=>$data->maven_key];
        $probability=$hotspotOwasp->countHotspotOwaspProbability($data->mode, $map);
        if ($probability['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'maven_key' => $data->maven_key,
                'code'=>$probability['code'], 'erreur' => $probability['erreur'],
                Response::HTTP_OK]);
        }

        $high = 0;
        $medium = 0;
        $low = 0;
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


        return $response->setData(
            [
            'reviewed' => $reviewed['request'][0]['nombre'], 'toReview' => $toReview['request'][0]['nombre'],
            'total' => $reviewed['request'][0]['nombre'] + $toReview['request'][0]['nombre'],
            'high' => $high, 'medium' => $medium, 'low' => $low,
            Response::HTTP_OK
        ]
        );
    }

    /**
     * [Description for peintureOwaspHotspotListe]
     * On récupère les résultats de la table hotpsot_owasp
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:20:20 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/owasp/hotspot/liste', name: 'peinture_owasp_hotspot_liste', methods: ['POST'])]
    public function peintureOwaspHotspotListe(Request $request): response
    {

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null) {
            return $response->setData(['data' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'mode')) {
            return $response->setData(['mode' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'maven_key')) {
            return $response->setData(['maven_key' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }

        /** On compte le nombre de hotspot de type OWASP au statut TO_REVIEWED */
        $map=['maven_key'=>$data->maven_key];
        $hotspotOwasp = $this->em->getRepository(HotspotOwasp::class);
        $menaces=$hotspotOwasp->countHotspotOwaspMenaces($data->mode, $map);
        if ($menaces['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'maven_key' => $data->maven_key,
                'code'=>$menaces['code'], 'erreur' => $menaces['erreur'],
                Response::HTTP_OK]);
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

        return $response->setData(
            [   $data->mode,
                'menaceA1' => $menaceA1, 'menaceA2' => $menaceA2,
                'menaceA3' => $menaceA3, 'menaceA4' => $menaceA4,
                'menaceA5' => $menaceA5, 'menaceA6' => $menaceA6,
                'menaceA7' => $menaceA7, 'menaceA8' => $menaceA8,
                'menaceA9' => $menaceA9, 'menaceA10' => $menaceA10,
                Response::HTTP_OK
            ]
        );
    }

    /**
     * [Description for peintureOwaspHotspotDetails]
     * On récupère le détails des failles de type hotpsot
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:20:54 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/owasp/hotspot/details', name: 'peinture_owasp_hotspot_details', methods: ['POST'])]
    public function peintureOwaspHotspotDetails(Request $request): response
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null) {
            return $response->setData(['data' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'mode')) {
            return $response->setData(['mode' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'maven_key')) {
            return $response->setData(['maven_key' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }

        /** On récupère la liste des hotspots par status de la table détails. */
        $map=['maven_key'=>$data->maven_key];
        $hotspotDetails = $this->em->getRepository(HotspotDetails::class);
        $details=$hotspotDetails->selectHotspotDetailsByStatus($data->mode, $map);
        if ($details['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'maven_key' => $data->maven_key,
                'code'=>$details['code'], 'erreur' => $details['erreur'],
                Response::HTTP_OK]);
        }

        return $response->setData(['details' => $details, Response::HTTP_OK]);
    }

    /**
     * [Description for peintureOwaspSeverity]
     * On récupère le détails des failles de type hotpsot
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:21:20 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/owasp/hotspot/severity', name: 'peinture_owasp_hotspot_severity', methods: ['POST'])]
    public function peintureOwaspSeverity(Request $request): response
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null) {
            return $response->setData(['data' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'mode')) {
            return $response->setData(['mode' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'maven_key')) {
            return $response->setData(['maven_key' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'menace')) {
            return $response->setData(['menace' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }

        $hotspotOwasp = $this->em->getRepository(HotspotOwasp::class);
        /** On compte le nombre de faille OWASP au statut HIGH */
        $map=['maven_key'=>$data->maven_key, 'menace'=>$data->menace, 'status'=>'HIGH'];
        $high=$hotspotOwasp->countHotspotOwaspMenanceBystatus($data->mode, $map);
        if ($high['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'maven_key' => $data->maven_key,
                'code'=>$high['code'], 'erreur' => $high['erreur'],
                Response::HTTP_OK]);
        }

        /** On compte le nombre de faille OWASP au statut MEDIUM */
        $map=['maven_key'=>$data->maven_key, 'menace'=>$data->menace, 'status'=>'MEDIUM'];
        $medium=$hotspotOwasp->countHotspotOwaspMenanceBystatus($data->mode, $map);
        if ($high['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'maven_key' => $data->maven_key,
                'code'=>$medium['code'], 'erreur' => $medium['erreur'],
                Response::HTTP_OK]);
        }

        /**  On compte le nombre de faille OWASP au statut LOW */
        $map=['maven_key'=>$data->maven_key, 'menace'=>$data->menace, 'status'=>'LOW'];
        $low=$hotspotOwasp->countHotspotOwaspMenanceBystatus($data->mode, $map);
        if ($high['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'maven_key' => $data->maven_key,
                'code'=>$low['code'], 'erreur' => $low['erreur'],
                Response::HTTP_OK]);
        }

        dd($high, $medium, $low);
        /**
         * On vérifie la valeur des vulnerabilité de type HIGH, MEDIUMet LOW
         * Si la valeur est null alors on initialise à 0
         */
        if (empty($hhigh)) {
            $high = 0;
        } else {
            $high = $hhigh[0];
        }

        if (empty($mmedium)) {
            $medium = 0;
        } else {
            $medium = $mmedium[0];
        }

        if (empty($llow)) {
            $low = 0;
        } else {
            $low = $llow[0];
        }

        return $response->setData(
            ['high' => $high, 'medium' => $medium, 'low' => $low, Response::HTTP_OK]
        );
    }

}
