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

namespace App\Controller\Projet;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

// Gestion de accès aux API
use Symfony\Component\HttpFoundation\JsonResponse;

/** Securité */
use Symfony\Bundle\SecurityBundle\Security;

// Accès aux tables SLQLite
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\InformationProjet;
use App\Entity\Anomalie;
use App\Entity\AnomalieDetails;
use App\Entity\Mesures;
use App\Entity\NoSonar;
use App\Entity\Hotspots;
use App\Entity\Notes;
use App\Entity\Todo;

/**
 * [Description ApiPeintureController]
 */
class ApiPeintureController extends AbstractController
{
    /**
     * [Description for __construct]
     *
     * @param mixed
     *
     * Created at: 11/10/2023 13:38:04 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->em = $em;
    }

    /** Définition des constantes */
    public static $removeReturnline = "/\s+/u";
    public static $reference = "<strong>[Peinture]</strong> ";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur406 = "Je n'ai pas trouvé les données. Vous devez lancer une collecte (Erreur 406)";
    public static $erreur500 = "Je n'ai pas trouvé d'analyse. (Erreur 500).";

    /**
     * [Description for isValide]
     * Vérification de l'existence du projet dans la table information_projet
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 15/12/2022, 21:51:16 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function isValide($mavenKey): array
    {
        /** On instancie l'entityRepository */
        $informationProjetEntity = $this->em->getRepository(InformationProjet::class);

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On regarde si une analyse a été réalisée. */
        $map=['maven_key'=>$mavenKey];
        $request=$informationProjetEntity->selectInformationProjetisValide($map);
        if ($request['code']!=200) {
            return $response->setData([
                'code' => $request['code'],
                Response::HTTP_OK]);
        }
        return ['code' => 200];
    }

    /**
     * [Description for calculNoteHospot]
     * retourne la note A, B, C, D ou E en fonction du ratio sonarqube.
     *
     * @param int $toReview
     * @param int $reviewed
     *
     * @return string
     *
     * Created at: 20/03/2024 19:34:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function calculNoteHospot($toReview, $reviewed): string
    {
        $ratio = intval($reviewed) * 100 / intval($toReview) + intval($reviewed);
        if ($ratio >= 80) {
            $note = "A";
        }
        if ($ratio >= 70 && $ratio < 80) {
            $note = "B";
        }
        if ($ratio >= 50 && $ratio < 70) {
            $note = "C";
        }
        if ($ratio >= 30 && $ratio < 50) {
            $note = "D";
        }
        if ($ratio < 30) {
            $note = "E";
        }
        return $note;
    }

    /**
     * [Description for projetMesApplicationsListe]
     * Récupère la liste des projets que j'ai visité et ceux en favori
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:51:40 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/mes-applications/liste', name: 'projet_mes_applications_liste', methods: ['POST'])]
    public function projetMesApplicationsListe(Request $request, Security $security): response
    {
        /** On instancie l'entityRepository */
        $anomalieEntity = $this->em->getRepository(Anomalie::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null) {
            return $response->setData([
                'data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference,
                'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

        /**
         * On récupère la liste des projets ayant déjà fait l'objet d'une analyse.
         * On n'utilise plus le critère liste=TRUE/FALSE car on utilise les préferences
         * de l'utilisateur
         */
        $request = $anomalieEntity->selectAnomalieByProjectName();
        if ($request['code']!=200) {
            return $response->setData(['code' => $request['code'], Response::HTTP_OK]);
        }

        /** Si on a pas trouvé d'application. */
        if (empty($request['liste'])) {
            $type = 'primary';
            $reference = static::$reference;
            $message = static::$erreur406;
            return $response->setData(['code'=>406, 'type' => $type, 'reference' => $reference, 'message' => $message, Response::HTTP_OK]);
        }

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $security->getUser()->getPreference();

        /**
         * Pour chaque projet de la liste de préference,
         * on regarde si le projet a déjà fait l'objet d'une analyse
         * et si le projet est en favori.
         */
        $mesProjets = $preference['projet'];
        $mesFavoris = $preference['favori'];
        $projets = [];
        foreach ($mesProjets as $projet) {
            if (in_array(['key' => $projet], $request)) {
                $t = explode(':', $projet);
                array_push($projets, ['key' => $projet, 'name' => $t[1], 'favori' => in_array($projet, $mesFavoris)]);
            }
        }

        return $response->setData([
            'code' => 200,
            'projets' => $projets,
            Response::HTTP_OK]);
    }


    /**
     * [Description for peintureProjetVersion]
     * Récupère les informations sur le projet : type de version, dernière version,
     * date de l'audit
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:53:31 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/projet/version', name: 'peinture_projet_version', methods: ['POST'])]
    public function peintureProjetVersion(Request $request): response
    {
        /** On instancie l'entityRepository */
        $informationProjetEntity = $this->em->getRepository(InformationProjet::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') ) {
            return $response->setData([
                'data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference,
                'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

        /** On regarde si le projet existe */
        $isValide = $this->isValide($data->maven_key);
        if ($isValide['code'] === 406) {
            return $response->setData([
                'code'=>406, 'type'=> 'secondary', 'reference'=> static::$reference,
                'message' => static::$erreur406, Response::HTTP_NOT_ACCEPTABLE]);
        }

        /** Toutes les versions par type (RELEASE, SNAPSHOT, AUTRE) */
        $map=['maven_key'=>$data->maven_key];
        $toutesLesVersions=$informationProjetEntity->countInformationProjetAllType($map);
        if ($toutesLesVersions['code']!=200) {
            return $response->setData([
                'code' => $toutesLesVersions['code'], 'type'=>'alert',
                'reference'=> static::$reference, 'message'=> static::$erreur500, Response::HTTP_OK]);
        }

        /** Les releases */
        $map=['maven_key'=>$data->maven_key, 'type'=>'RELEASE'];
        $release=$informationProjetEntity->countInformationProjetType($map);
        if ($release['code']!=200) {
            return $response->setData(['code' => $release['code'], Response::HTTP_OK]);
        }

        /** Les snapshots */
        $map=['maven_key'=>$data->maven_key, 'type'=>'SNAPSHOT'];
        $snapshot=$informationProjetEntity->countInformationProjetType($map);
        if ($snapshot['code']!=200) {
            return $response->setData([ 'code' => $snapshot['code'], Response::HTTP_OK]);
        }

        /** On calcul la valeur pour les autres types de version */
        $lesAutres = $toutesLesVersions['nombre'][0]['total'] - $release['nombre'][0]['total'] - $snapshot['nombre'][0]['total'];

        /** On récupére le nombre de version par type pour le graphique */
        $map=['maven_key'=>$data->maven_key];
        $infoVersion=$informationProjetEntity->selectInformationProjetTypeIndexed($map);
        if ($snapshot['code']!=200) {
            return $response->setData(['code' => $snapshot['code'], Response::HTTP_OK]);
        }

        $label = [];
        $dataset = [];
        foreach ($infoVersion['liste'] as $key => $value) {
            array_push($label, $key);
            array_push($dataset, $value['total']);
        }

        /** On récupère la dernière version et sa date de publication */
        $map=['maven_key'=>$data->maven_key];
        $infoRelease=$informationProjetEntity->selectInformationProjetVersionLast($map);
        if ($infoRelease['code']!=200) {
            return $response->setData(['code' => $infoRelease['code'], Response::HTTP_OK]);
        }

        /** Contrôle de la valeur des versions release, snapshot et release */
        $release = 0;
        if (!empty($release['nombre'][0]['total'])) {
            $release = $release['nombre'][0]['total'];
        }

        $snapshot = 0;
        if (!empty($snapshot['nombre'][0]['total'])) {
            $snapshot = $snapshot['nombre'][0]['total'];
        }

        return $response->setData(
            [
                'code'=>200,
                'release' => $release, 'snapshot' => $snapshot,  'autre' => $lesAutres,
                'label' => $label,
                'dataset' => $dataset, 'projet' => $infoRelease['version'][0]['projet'],
                'date' => $infoRelease['version'][0]['date'],
                Response::HTTP_OK]
        );
    }

    /**
     * [Description for peintureProjetMesures]
     * Récupère les informations sur le projet : type de version, dernière version,
     * date de l'audit
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:53:58 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/projet/mesures', name: 'peinture_projet_mesures', methods: ['POST'])]
    public function peintureProjetMesures(Request $request): response
    {
        /** On instancie l'entityRepository */
        $mesuresEntity = $this->em->getRepository(Mesures::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') ) {
            return $response->setData([
                'data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference,
                'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

        /** On regarde si le projet existe */
        $isValide = $this->isValide($data->maven_key);
        if ($isValide['code'] === 406) {
            return $response->setData([
                'code'=>406, 'type'=> 'secondary', 'reference'=> static::$reference,
                'message' => static::$erreur406, Response::HTTP_NOT_ACCEPTABLE]);
        }

        /** On récupère la dernière version et sa date de publication */
        $map=['maven_key'=>$data->maven_key];
        $request=$mesuresEntity->selectMesuresVersionLast($map);
        if ($request['code']!=200) {
            return $response->setData([
                'code' => $request['code'], Response::HTTP_OK]);
        }

        return $response->setData([
            'code'=>200,
            'name' => $request['mesures'][0]['name'], 'ncloc' => $request['mesures'][0]['ncloc'],
            'lines' => $request['mesures'][0]['lines'],
            'coverage' => $request['mesures'][0]['coverage'],
            'sqaleDebtRatio' => $request['mesures'][0]['sqale_debt_ratio'],
            'duplication' => $request['mesures'][0]['duplication'],
            'tests' => $request['mesures'][0]['tests'],
            'issues' => $request['mesures'][0]['issues'],
            Response::HTTP_OK
        ]);
    }

    /**
     * [Description for peintureProjetAnomalie]
     * Récupère les informations sur la dette technique et les anamalies
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:54:30 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/projet/anomalie', name: 'peinture_projet_anomalie', methods: ['POST'])]
    public function peintureProjetAnomalie(Request $request): response
    {
        /** On instancie l'entityRepository */
        $anomalieEntity = $this->em->getRepository(Anomalie::class);
        $notesEntity = $this->em->getRepository(Notes::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') ) {
            return $response->setData([
                'data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference,
                'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

        /** On regarde si le projet existe */
        $isValide = $this->isValide($data->maven_key);
        if ($isValide['code'] === 406) {
            return $response->setData([
                'code'=>406, 'type'=> 'secondary', 'reference'=> static::$reference,
                'message' => static::$erreur406, Response::HTTP_NOT_ACCEPTABLE]);
        }

        /** On récupère la dernière version et sa date de publication */
        $map=['maven_key'=>$data->maven_key];
        $anomalie=$anomalieEntity->selectAnomalie($map);
        if ($anomalie['code']!=200) {
            return $response->setData(['code' => $anomalie['code'], Response::HTTP_OK]);
        }

        /**
         * Dette : Dette total, répartition de la dette en fonction du type.
         * On récupère la valeur en jour, heure, minute pour l'affichage et la valeur en minutes
         * pour la historique (i.e on fera les comparaison sur cette valeur)
        */
        $dette = $anomalie['liste'][0]['dette'];
        $detteMinute = $anomalie['liste'][0]['dette_minute'];
        $detteReliability = $anomalie['liste'][0]['dette_reliability'];
        $detteReliabilityMinute = $anomalie['liste'][0]['dette_reliability_minute'];
        $detteVulnerability = $anomalie['liste'][0]['dette_vulnerability'];
        $detteVulnerabilityMinute = $anomalie['liste'][0]['dette_vulnerability_minute'];
        $detteCodeSmell = $anomalie['liste'][0]['dette_code_smell'];
        $detteCodeSmellMinute = $anomalie['liste'][0]['dette_code_smell_minute'];

        /** Types */
        $typeBug = $anomalie['liste'][0]['bug'];
        $typeVulnerability = $anomalie['liste'][0]['vulnerability'];
        $typeCodeSmell = $anomalie['liste'][0]['code_smell'];

        /** Severity */
        $severityBlocker = $anomalie['liste'][0]['blocker'];
        $severityCritical = $anomalie['liste'][0]['critical'];
        $severityMajor = $anomalie['liste'][0]['major'];
        $severityInfo = $anomalie['liste'][0]['info'];
        $severityMinor = $anomalie['liste'][0]['minor'];

        /** Module */
        $frontend = $anomalie['liste'][0]['frontend'];
        $backend = $anomalie['liste'][0]['backend'];
        $autre = $anomalie['liste'][0]['autre'];

        /* On récupère les notes (A-F) */
        $types = ['reliability', 'security', 'sqale'];
        $noteReliability = $noteSecurity = $noteSqale = 'N/A';

        foreach ($types as $type) {
            $map=['maven_key'=>$data->maven_key, 'type'=>$type];
            $note=$notesEntity->selectNotesMavenType($map);
            if ($note['code']!=200) {
                return $response->setData([
                    'code' => $note['code'],
                    Response::HTTP_OK]);
            }

            if (isset($note['liste'][0]['value'])) {
                if ($type == 'reliability') {
                    $noteReliability = $note['liste'][0]['value'];
                } elseif ($type == 'security') {
                    $noteSecurity = $note['liste'][0]['value'];
                } elseif ($type == 'sqale') {
                    $noteSqale = $note['liste'][0]['value'];
                }
            }
        }

        return $response->setData([
            'code'=>200,
            'dette' => $dette,
            'detteReliability' => $detteReliability,
            'detteVulnerability' => $detteVulnerability,
            'detteCodeSmell' => $detteCodeSmell,
            'detteMinute' => $detteMinute,
            'detteReliabilityMinute' => $detteReliabilityMinute,
            'detteVulnerabilityMinute' => $detteVulnerabilityMinute,
            'detteCodeSmellMinute' => $detteCodeSmellMinute,
            'bug' => $typeBug, 'vulnerability' => $typeVulnerability, 'codeSmell' => $typeCodeSmell,
            'blocker' => $severityBlocker,
            'critical' => $severityCritical,
            'info' => $severityInfo,
            'major' => $severityMajor,
            'minor' => $severityMinor,
            'frontend' => $frontend, 'backend' => $backend, 'autre' => $autre,
            'noteReliability' => $noteReliability,
            'noteSecurity' => $noteSecurity,
            'noteSqale' => $noteSqale, Response::HTTP_OK
        ]);
    }

    /**
     * [Description for peintureProjetAnomalieDetails]
     * Récupère le détails des anomalies pour chaque type
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:56:17 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/projet/anomalie/details', name: 'peinture_projet_anomalie_details', methods: ['POST'])]
    public function peintureProjetAnomalieDetails(Request $request): response
    {
        /** On instancie l'entityRepository */
        $anomalieDetailsEntity = $this->em->getRepository(AnomalieDetails::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') ) {
            return $response->setData([
                'data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference,
                'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

        /** On regarde si le projet existe */
        $isValide = $this->isValide($data->maven_key);
        if ($isValide['code'] === 406) {
            return $response->setData([
                'code'=>406, 'type'=> 'secondary', 'reference'=> static::$reference,
                'message' => static::$erreur406, Response::HTTP_NOT_ACCEPTABLE]);
        }

        /** On récupère les données pour le projet */
        $map=['maven_key'=>$data->maven_key];
        $details=$anomalieDetailsEntity->selectAnomalieDetailsMavenKey($map);
        if ($details['code']!=200) {
            return $response->setData([
                'code' => $details['code'], Response::HTTP_OK]);
        }

        $bugBlocker = $details['liste'][0]['bug_blocker'];
        $bugCritical = $details['liste'][0]['bug_critical'];
        $bugMajor = $details['liste'][0]['bug_major'];
        $bugMinor = $details['liste'][0]['bug_minor'];
        $bugInfo = $details['liste'][0]['bug_info'];

        $vulnerabilityBlocker = $details['liste'][0]['vulnerability_blocker'];
        $vulnerabilityCritical = $details['liste'][0]['vulnerability_critical'];
        $vulnerabilityMajor = $details['liste'][0]['vulnerability_major'];
        $vulnerabilityMinor = $details['liste'][0]['vulnerability_minor'];
        $vulnerabilityInfo = $details['liste'][0]['vulnerability_info'];

        $codeSmellBlocker = $details['liste'][0]['code_smell_blocker'];
        $codeSmellCritical = $details['liste'][0]['code_smell_critical'];
        $codeSmellMajor = $details['liste'][0]['code_smell_major'];
        $codeSmellMinor = $details['liste'][0]['code_smell_minor'];
        $codeSmellInfo = $details['liste'][0]['code_smell_info'];

        return $response->setData([
            "code" => 200,
            "bugBlocker" => $bugBlocker,
            "bugCritical" => $bugCritical,
            "bugMajor" => $bugMajor,
            "bugMinor" => $bugMinor,
            "bugInfo" => $bugInfo,
            "vulnerabilityBlocker" => $vulnerabilityBlocker,
            "vulnerabilityCritical" => $vulnerabilityCritical,
            "vulnerabilityMajor" => $vulnerabilityMajor,
            "vulnerabilityMinor" => $vulnerabilityMinor,
            "vulnerabilityInfo" => $vulnerabilityInfo,
            "codeSmellBlocker" => $codeSmellBlocker,
            "codeSmellCritical" => $codeSmellCritical,
            "codeSmellMajor" => $codeSmellMajor,
            "codeSmellMinor" => $codeSmellMinor,
            "codeSmellInfo" => $codeSmellInfo,
            Response::HTTP_OK
        ]);
    }

    /**
     * [Description for peintureProjetHotspots]
     * Récupère les hotspots du projet
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:56:43 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/projet/hotspots', name: 'peinture_projet_hotspots', methods: ['POST'])]
    public function peintureProjetHotspots(Request $request): response
    {
        /** On instancie l'entityRepository */
        $hotspotsEntity = $this->em->getRepository(Hotspots::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') ) {
            return $response->setData(['data'=>$data,'code'=>400, 'type'=>'alert',
            'reference'=> static::$reference, 'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

        /** On regarde si le projet existe */
        $isValide = $this->isValide($data->maven_key);
        if ($isValide['code'] === 406) {
            return $response->setData([
                'code'=>406, 'type'=> 'secondary', 'reference'=> static::$reference,
                'message' => static::$erreur406, Response::HTTP_NOT_ACCEPTABLE]);
        }

        /** On compte le nombre de hotspot au statut TO_REVIEW */
        $map=['maven_key'=>$data->maven_key, 'status'=>'TO_REVIEW'];
        $toReview=$hotspotsEntity->countHotspotsStatus($map);
        if ($toReview['code']!=200) {
            return $response->setData(['code' => $toReview['code'], Response::HTTP_OK]);
        }

        /** On compte le nombre de hotspot au statut REVIEWED */
        $map=['maven_key'=>$data->maven_key, 'status'=>'REVIEWED'];
        $reviewed=$hotspotsEntity->countHotspotsStatus($map);
        if ($reviewed['code']!=200) {
            return $response->setData(['code' => $reviewed['code'], Response::HTTP_OK]);
        }

        /** On calul la note sonar */
        if (empty($toReview['nombre'][0]['to_review'])) {
            $note = "A";
        } else {
            $note=static::calculNoteHospot($toReview['nombre'][0]['to_review'], $reviewed['nombre'][0]['reviewed']);
        }

        return $response->setData(['code'=>200, 'note' => $note, Response::HTTP_OK]);
    }

    /**
     * [Description for peintureProjetHotspotsDetails]
     * Récupère le détails des hotspots du projet
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:57:40 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/projet/hotspots/details', name: 'peinture_projet_hotspots_details', methods: ['POST'])]
    public function peintureProjetHotspotsDetails(Request $request): response
    {
        /** On instancie l'entityRepository */
        $hotspotsEntity = $this->em->getRepository(Hotspots::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') ) {
            return $response->setData([
                'data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference,
                'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }
        /** On regarde si le projet existe */
        $isValide = $this->isValide($data->maven_key);
        if ($isValide['code'] === 406) {
            return $response->setData([
                'code'=>406, 'type'=> 'secondary', 'reference'=> static::$reference,
                'message' => static::$erreur406, Response::HTTP_NOT_ACCEPTABLE]);
        }

        $total = $high = $medium = $low = 0;
        /** On récupère la dernière version et sa date de publication. */
        $map=['maven_key'=>$data->maven_key, 'status'=>'REVIEWED'];
        $niveaux=$hotspotsEntity->selectHotspotsByNiveau($map);
        if ($niveaux['code']!=200) {
            return $response->setData(['code' => $niveaux['code'], Response::HTTP_OK]);
        }
        if (!$niveaux){
            foreach ($niveaux as $niveau) {
                if ($niveau['liste']['niveau'] == '1') {
                    $high = $niveau['liste']['hotspot'];
                }
                if ($niveau['liste']['niveau'] == '2') {
                    $medium = $niveau['liste']['hotspot'];
                }
                if ($niveau['liste']['niveau'] == '3') {
                    $low = $niveau['liste']['hotspot'];
                }
            }
            $total = intval($high) + intval($medium) + intval($low);
        }
        return $response->setData(
            ['code' => 200, 'total' => $total, 'high' => $high,
            'medium' => $medium, 'low' => $low, Response::HTTP_OK]
        );
    }

    /**
     * [Description for peintureProjetNosonar]
     * Récupère les exclusions nosonar et suppressWarning pour Java
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:58:42 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/peinture/projet/nosonar', name: 'peinture_projet_nosonar', methods: ['POST'])]
    public function peintureProjetNosonarDetails(Request $request): response
    {
        /** On instancie l'entityRepository */
        $noSonarEntity = $this->em->getRepository(NoSonar::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null) {
            return $response->setData(['data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference, 'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

        /** On regarde si le projet existe */$nosonar = new NoSonar();
        $isValide = $this->isValide($data->maven_key);
        if ($isValide['code'] === 406) {
            return $response->setData([
                'code'=>406, 'type'=> 'secondary', 'reference'=> static::$reference,
                'message' => static::$erreur406, Response::HTTP_NOT_ACCEPTABLE]);
        }
        /** On récupère la liste des règles et leur nombre pour un projet. */
        $map=['maven_key'=>$data->maven_key];
        $rules=$noSonarEntity->selectNoSonarRuleGroupByRule($map);
        if ($rules['code']!=200) {
            return $response->setData(['code' => $rules['code'], Response::HTTP_OK]);
        }

        $sonar1309 = $nosonar = $total = 0;
        if (!empty($rules)) {
            foreach ($rules['liste'] as $rule) {
                if ($rule['rule'] == 'java:S1309') {
                    $sonar1309 = $rule['total'];
                }
                if ($rule['rule'] == 'java:NoSonar') {
                    $nosonar = $rule['total'];
                }
            }
            $total = intval($sonar1309, 10) + intval($nosonar, 10);
        }

        return $response->setData(
            ['code'=>200, 'total' => $total, 's1309' => $sonar1309, 'nosonar' => $nosonar, Response::HTTP_OK]
        );
    }

    /**
       * [Description for peintureProjetTodo]
       * Récupère les tags to do pour : java, js, ts, html et xml
       *
       * @param Request $request
       *
       * @return response
       *
       * Created at: 10/04/2023, 17:54:08 (Europe/Paris)
       * @author    Laurent HADJADJ <laurent_h@me.com>
       * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
       */
    #[Route('/api/peinture/projet/todo', name: 'peinture_projet_todo', methods: ['POST'])]
    public function peintureProjetTodo(Request $request): response
    {
        /** On instancie l'entityRepository */
        $todoEntity = $this->em->getRepository(Todo::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') ) {
            return $response->setData([
                'data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference,
                'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

        /** On regarde si le projet existe */
        $isValide = $this->isValide($data->maven_key);
        if ($isValide['code'] === 406) {
            return $response->setData([
                'code'=>406, 'type'=> 'secondary', 'reference'=> static::$reference,
                'message' => static::$erreur406, Response::HTTP_NOT_ACCEPTABLE]);
        }

        /** On récupère la liste des to do pour le projet. */
        $map=['maven_key'=>$data->maven_key];
        $rules=$todoEntity->selectTodoRuleGroupByRule($map);
        if ($rules['code']!=200) {
            return $response->setData(['code' => $rules['code'], Response::HTTP_OK]);
        }

        $todo = $java = $javascript = $typescript = $html = $xml = 0;

        if (!empty($rules)) {
            /** On récupère le nombre total de To do par langage */
            foreach ($rules['liste'] as $rule) {
                if ($rule['rule'] == 'java:S1135') {
                    $java = $rule['total'];
                }
                if ($rule['rule'] == 'javascript:S1135') {
                    $javascript = $rule['total'];
                }
                if ($rule['rule'] == 'typescript:S1135') {
                    $typescript = $rule['total'];
                }
                if ($rule['rule'] == 'Web:S1135') {
                    $html = $rule['total'];
                }
                if ($rule['rule'] == 'xml:S1135') {
                    $xml = $rule['total'];
                }
            }
            $todo = intval($java, 10) + intval($javascript, 10) + intval($typescript, 10) + intval($html, 10) + intval($xml, 10);
        }

        /** On récupère la liste détaillée. */
        $map=['maven_key'=>$data->maven_key];
        $details=$todoEntity->selectTodoComponentOrderByRule($map);
        if ($details['code']!=200) {
            return $response->setData(['code' => $details['code'], Response::HTTP_OK]);
        }

        return $response->setData(
            ['code'=>200, 'todo' => $todo, 'java' => $java, 'javascript' => $javascript,
                'typescript' => $typescript, 'html' => $html, 'xml' => $xml, "details" => $details, Response::HTTP_OK]
        );
    }

}
