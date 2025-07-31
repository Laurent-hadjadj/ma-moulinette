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

namespace App\Controller\Projet;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\Historique;
use Doctrine\ORM\EntityManagerInterface;

/**
 * [Description ApiEnregistrementController]
 */
class ApiEnregistrementController extends AbstractController
{
    /** Définition des constantes */
    public static $europeParis = "Europe/Paris";
    public static $reference = "<strong>[Enregistrement]</strong> ";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 21:25:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private $security;
    private $em;

    public function __construct(
        EntityManagerInterface $em,
        Security $security
    ) {
        $this->em = $em;
        $this->security = $security;
    }

    /**
     * [Description for enregistrement]
     * Enregistrement des données du projet
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:44:09 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/enregistrement', name: 'enregistrement', methods: ['PUT'])]
    public function enregistrement(Request $request): JsonResponse
    {
        /** On instancie l'entityRepository */
        $historiqueRepository = $this->em->getRepository(Historique::class);

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
        return new JsonResponse(
            ['code' => 403, 'type'=>'warning',
            'message' => static::$reference . static::$erreur403], Response::HTTP_OK);
        }

        /** On décode le body. */
        $data = json_decode($request->getContent());
        /** On teste si la clé est valide */
        if ($data === null) {
        return new JsonResponse([
            'data' => $data,'code' => 400, 'type' => 'alert',
            'message' => static::$reference . static::$erreur400], Response::HTTP_OK);
        }

        /** On créé un objet date Immutable, avec la date courante. */
        $dateEnregistrement = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));
        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();

        $json = '{}';
        $map = ['maven_key' => $data->maven_key, 'analyse_key' => $data->analyse_key,
            'version' => $data->version, 'date_version' => $data->date_version,
            'nom_projet' => $data->nom_projet, 'version_release' => $data->version_release, 'version_snapshot' => $data->version_snapshot, 'version_autre' => $data->version_autre,
            'suppress_warning' => $data->suppress_warning, 'no_sonar' => $data->no_sonar,
            'todo' => $data->todo,
            'logger_info' => $data->logger_info, 'logger_warn' => $data->logger_warn, 'logger_error' => $data->logger_error, 'logger_debug' => $data->logger_debug,
            'nombre_ligne' => $data->nombre_ligne, 'nombre_ligne_code' => $data->nombre_ligne_code,
            'files' => $data->files, 'classes' => $data->classes, 'functions' => $data->functions,
            'coverage' => $data->coverage, 'duplicated_lines_density' => $data->duplicated_lines_density, 'sqale_debt_ratio' => $data->sqale_debt_ratio, 'tests' => $data->tests, 'violations' => $data->violations, 'dette' => $data->dette,
            'nombre_bug' => $data->nombre_bug, 'nombre_vulnerability' => $data->nombre_vulnerability, 'nombre_code_smell' => $data->nombre_code_smell,
            'bug_blocker' => $data->bug_blocker, 'bug_critical' => $data->bug_critical,
            'bug_major' => $data->bug_major, 'bug_minor' => $data->bug_minor, 'bug_info' => $data->bug_info,
            'vulnerability_blocker' => $data->vulnerability_blocker, 'vulnerability_critical' => $data->vulnerability_critical, 'vulnerability_major' => $data->vulnerability_major,
            'vulnerability_minor' => $data->vulnerability_minor, 'vulnerability_info' => $data->vulnerability_info,
            'code_smell_blocker' => $data->code_smell_blocker, 'code_smell_critical' => $data->code_smell_critical, 'code_smell_major' => $data->code_smell_major,
            'code_smell_minor' => $data->code_smell_minor, 'code_smell_info' => $data->code_smell_info,
            'frontend' => $data->frontend, 'backend' => $data->backend, 'autre' => $data->autre, 'inconnu' => $data->inconnu,
            'nombre_anomalie_bloquant' => $data->nombre_anomalie_bloquant, 'nombre_anomalie_critique' => $data->nombre_anomalie_critique,
            'nombre_anomalie_majeur' => $data->nombre_anomalie_majeur,
            'nombre_anomalie_mineur' => $data->nombre_anomalie_mineur, 'nombre_anomalie_info' => $data->nombre_anomalie_info,
            'note_reliability' => $data->note_reliability, 'note_security' => $data->note_security,
            'note_sqale' => $data->note_sqale, 'note_hotspot' => $data->note_hotspot, 'nombre_hotspot' => $data->nombre_hotspot,
            'hotspot_high' => $data->hotspot_high, 'hotspot_medium' => $data->hotspot_medium, 'hotspot_low' => $data->hotspot_low,
            'mode_collecte' => 'COLLECTE', 'utilisateur_collecte' => $utilisateur_collecte,
            'date_enregistrement' => $dateEnregistrement
        ];

       /** Enregistrement dans le table historique */
        $historique = $historiqueRepository->insertHistoriqueAjoutProjet($map, $json);
        if ($historique['code'] != 200 && $historique['code'] != 23505) {
            return new JsonResponse(['code' => $historique['code'], 'erreur' => $historique['erreur']]);
        }
        if ($historique['code'] === 23505){
            return new JsonResponse(['code' => $historique['code'], 'erreur' => $historique['erreur']], Response::HTTP_OK);
        }

    /** Tout va bien ! */
    return new JsonResponse(['code' => 200], Response::HTTP_OK);
    }

}
