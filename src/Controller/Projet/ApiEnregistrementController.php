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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Historique;

/**
 * [Description ApiEnregistrementController]
 */
class ApiEnregistrementController extends AbstractController
{
    /** Définition des constantes */
    private static $europeParis = "Europe/Paris";
    private static $titreJS = "<strong>[Enregistrement]</strong> ";
    private static $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static $erreur401 = "Utilisateur non authentifié (Erreur 401).";
    private static $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";
    private static $loggerE401 = "[Enregistrement] ❌ Aucun utilisateur connecté.";
    private static $loggerE403 = "[Enregistrement] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_COLLECTE).";

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 21:25:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private LoggerInterface $logger
    ) {
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
        $user = $this->security->getUser();
        if (!$user) {
            $this->logger->error(static::$loggerE401);
            return new JsonResponse([
                'code' => 401,
                'type' => 'alert',
                'message' => static::$titreJS . static::$erreur401
            ], Response::HTTP_OK);
        }

        /** On instancie l'entityRepository */
        $historiqueRepos = $this->em->getRepository(Historique::class);

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
        $this->logger->error(static::$loggerE403, [ 'user' => $user ]);

        return new JsonResponse(
            [
                'code' => 403,
                'type'=>'warning',
                'message' => static::$titreJS . static::$erreur403
            ], Response::HTTP_OK);
        }

        /** On décode le body. */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null) {
        $this->logger->error("[Enregistrement] ❌ Requête invalide : clé 'data' manquante ou JSON mal formé.",
            [ 'payload' => $data ]);

        return new JsonResponse([
            'code' => 400,
            'type' => 'alert',
            'message' => static::$titreJS . static::$erreur400
            ], Response::HTTP_OK);
        }

        /** On créé un objet date Immutable, avec la date courante. */
        $dateEnregistrement = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));
        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();

        try {
            $json = '{}';
            $map = [
                'maven_key' => $data->maven_key, 'analyse_key' => $data->analyse_key,
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
                'note_sqale' => $data->note_sqale, 'note_hotspot' => $data->note_hotspot, 'menace_potentielle_totale' => $data->menace_potentielle_totale,
                'menace_potentielle_to_review_high' => $data->menace_potentielle_to_review_high,
                'menace_potentielle_to_review_medium' => $data->menace_potentielle_to_review_medium,
                'menace_potentielle_to_review_low' => $data->menace_potentielle_to_review_low,
                'menace_potentielle_reviewed_high' => $data->menace_potentielle_reviewed_high,
                'menace_potentielle_reviewed_medium' => $data->menace_potentielle_reviewed_medium,
                'menace_potentielle_reviewed_low' => $data->menace_potentielle_reviewed_low,
                'mode_collecte' => 'COLLECTE', 'utilisateur_collecte' => $utilisateur_collecte,
                'date_enregistrement' => $dateEnregistrement
            ];

            /** Enregistrement dans le table historique */
            $historique = $historiqueRepos->insertHistoriqueAjoutProjet($map, $json);
            if ($historique['code'] != 200 && $historique['code'] != 23505) {
                $this->logger->error("[Enregistrement] ❌ Échec de la requête insertHistoriqueAjoutProjet.", [
                    'code' => $historique['code'],
                    'erreur' => $historique['erreur'] ?? "aucun message d'erreur remonté"
                ]);

                return new JsonResponse([
                    'code' => $historique['code'],
                    'type' => 'alert',
                    'message' => static::$titreJS . "Une erreur lors de l'ajout de données est survenue (Erreur {$historique['code']}).",
                    'trace' => $historique['erreur']
                ], Response::HTTP_OK);
            }

            if ($historique['code'] === 23505){
                $this->logger->info("[Enregistrement] ❌ détection de doublon.", [
                    'code' => $historique['code'],
                    'erreur' => $historique['erreur'],
                    'payload' => $map
                ]);

                return new JsonResponse([
                    'code' => $historique['code'],
                    'erreur' => $historique['erreur']
                ], Response::HTTP_OK);
            }
        } catch (\Throwable $e) {
            $this->logger->critical("[Enregistrement] 🔴 Erreur lors de l'enregistrement des données.", ['exception' => $e]);
            return new JsonResponse([
                'code' => 500,
                'type' => 'critical',
                'message' => "[Enregistrement] Erreur lors de l'enregistrement des données.",
                'trace' => $e->getMessage()
            ], Response::HTTP_OK);
        }

    /** Tout va bien ! */
    return new JsonResponse(['code' => 200], Response::HTTP_OK);
    }

}
