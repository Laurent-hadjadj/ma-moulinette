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

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\JsonResponse;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

use App\Entity\Main\Historique;
use Doctrine\ORM\EntityManagerInterface;

/**
 * [Description ApiEnregistrementController]
 */
class ApiEnregistrementController extends AbstractController
{
    /** Définition des constantes */
    public static $europeParis = "Europe/Paris";

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 21:25:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em
    ) {
        $this->em = $em;
    }

    /**
     * [Description for enregistrement]
     * Enregistrement des données du projet
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:44:09 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/enregistrement', name: 'enregistrement', methods: ['PUT'])]
    public function enregistrement(Request $request): response
    {
        /** On décode le body. */
        $data = json_decode($request->getContent());
        /** On créé un objet response pour le retour JSON. */
        $response = new JsonResponse();

        /** On créé un objet date, avec la date courante. */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        /** Enregistrement */
        $save = new Historique();

        /** Informations version. */
        $save->setMavenKey($data->mavenKey);
        $save->setNomProjet($data->nomProjet);
        $save->setVersionRelease($data->versionRelease);
        $save->setVersionSnapshot($data->versionSnapshot);
        $save->setVersionAutre($data->versionAutre);
        $save->setVersion($data->version);
        $save->setDateVersion($data->dateVersion);

        /** Informations sur les exceptions. */
        $save->setSuppressWarning($data->suppressWarning);
        $save->setNoSonar($data->noSonar);

        /** Informations projet. */
        $save->setNombreLigne($data->nombreLigne);
        $save->setNombreLigneCode($data->nombreLigneDeCode);
        $save->setCouverture($data->couverture);
        $save->setDuplication($data->duplication);
        $save->setTestsUnitaires($data->testsUnitaires);
        $save->setNombreDefaut($data->nombreDefaut);

        /** Dette technique. */
        $save->setDette($data->dette);

        /** Nombre de défaut. */
        $save->setNombreBug($data->nombreBug);
        $save->setNombreVulnerability($data->nombreVulnerability);
        $save->setNombreCodeSmell($data->nombreCodeSmell);

        /** répartition par module (Java). */
        $save->setFrontend($data->frontend);
        $save->setBackend($data->backend);
        $save->setAutre($data->autre);

        /** Répartition par type. */
        $save->setNombreAnomalieBloquant($data->nombreAnomalieBloquant);
        $save->setNombreAnomalieCritique($data->nombreAnomalieCritique);
        $save->setNombreAnomalieInfo($data->nombreAnomalieInfo);
        $save->setNombreAnomalieMajeur($data->nombreAnomalieMajeur);
        $save->setNombreAnomalieMineur($data->nombreAnomalieMineur);

        /** Notes Fiabilité, sécurité, hotspots et mauvaises pratique. */
        $save->setNoteReliability($data->noteReliability);
        $save->setNoteSecurity($data->noteSecurity);
        $save->setNoteSqale($data->noteSqale);
        $save->setNoteHotspot($data->noteHotspot);

        /** Répartition des hotspots. */
        $save->setHotspotHigh($data->hotspotHigh);
        $save->setHotspotMedium($data->hotspotMedium);
        $save->setHotspotLow($data->hotspotLow);
        $save->setHotspotTotal($data->hotspotTotal);

        /** Je suis une verion initiale ?  0 (false) and 1 (true). */
        /** On récupère 0 ou 1 et non FALSE et TRUE */
        $save->setInitial($data->initial);

        /** Nombre de défaut par sévérité. */
        /** Les BUG. */
        $save->setBugBlocker($data->bugBlocker);
        $save->setBugCritical($data->bugCritical);
        $save->setBugMajor($data->bugMajor);
        $save->setBugMinor($data->bugMinor);
        $save->setBugInfo($data->bugInfo);

        /** Les VULNERABILITY. */
        $save->setVulnerabilityBlocker($data->vulnerabilityBlocker);
        $save->setVulnerabilityCritical($data->vulnerabilityCritical);
        $save->setVulnerabilityMajor($data->vulnerabilityMajor);
        $save->setVulnerabilityMinor($data->vulnerabilityMinor);
        $save->setVulnerabilityInfo($data->vulnerabilityInfo);

        /** Les CODE SMELL. */
        $save->setCodeSmellBlocker($data->codeSmellBlocker);
        $save->setCodeSmellCritical($data->codeSmellCritical);
        $save->setCodeSmellMajor($data->codeSmellMajor);
        $save->setCodeSmellMinor($data->codeSmellMinor);
        $save->setCodeSmellInfo($data->codeSmellInfo);

        /** On ajoute la date et on enregistre. */
        $save->setDateEnregistrement($date);
        $this->em->persist($save);

        // On catch l'erreur sur la clé composite : maven_key, version, date_version
        try {
            $this->em->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            /** General error: 5 database is locked" */
            /** General error: 19 violation de clé */
            if ($e->getCode() === 19) {
                $code = 19;
            } else {
                $code = $e;
            }
            return $response->setData(["code" => $code, Response::HTTP_OK]);
        }
    /** Tout va bien ! */
    return $response->setData(["code" => "OK", Response::HTTP_OK]);
    }
}
