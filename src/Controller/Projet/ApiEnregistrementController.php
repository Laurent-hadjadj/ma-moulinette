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

use App\Controller\Traits\AppUserAware;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\{JsonResponse, Response, Request};
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Historique;


/**
 * [Description ApiEnregistrementController]
 */
class ApiEnregistrementController extends AbstractController
{
    use AppUserAware;

    /** Définition des constantes */
    private static string $europeParis = "Europe/Paris";
    private static string $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static string $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";
    private static string $loggerE403 = "[Enregistrement] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_COLLECTE).";

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 21:25:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

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
    #[Route('/api/secure/enregistrement', name: 'enregistrement', methods: ['PUT'])]
    public function enregistrement(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/enregistrement");

        $user = $this->appUser();

        /** On instancie l'entityRepository */
        $historiqueRepos = $this->em->getRepository(Historique::class);

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->error(self::$loggerE403, ['user' => $user]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403
            ], Response::HTTP_OK);
        }

        /** On décode le body. */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null) {
            $this->logger->error(
                "[Enregistrement] ❌ Requête invalide : clé 'data' manquante ou JSON mal formé.",
                ['payload' => $data]
            );

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** On créé un objet date Immutable, avec la date courante. */
        $dateEnregistrement = new \DateTimeImmutable('now', new \DateTimeZone(self::$europeParis));

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->appUser()->getCourriel();

        try {
            $json = [];
            $map = [
                'maven_key' => $data->maven_key,
                'analyse_key' => $data->analyse_key,
                'version' => $data->version,
                'date_version' => $data->date_version,
                'project_name' => $data->project_name,
                'version_release' => $data->version_release,
                'version_snapshot' => $data->version_snapshot,
                'version_autre' => $data->version_autre,
                'suppress_warning' => $data->suppress_warning,
                'no_sonar' => $data->no_sonar,
                'todo' => $data->todo,
                'logger_info' => $data->logger_info,
                'logger_warn' => $data->logger_warn,
                'logger_error' => $data->logger_error,
                'logger_debug' => $data->logger_debug,
                'lines' => $data->lines,
                'ncloc' => $data->ncloc,
                'files' => $data->files,
                'classes' => $data->classes,
                'functions' => $data->functions,
                'coverage' => $data->coverage,
                'duplicated_lines_density' => $data->duplicated_lines_density,
                'sqale_debt_ratio' => $data->sqale_debt_ratio,
                'tests' => $data->tests,
                'violations' => $data->violations,
                'sqale_index' => $data->sqale_index,
                'bugs' => $data->bugs,
                'vulnerabilities' => $data->vulnerabilities,
                'code_smells' => $data->code_smells,
                'bug_blocker' => $data->bug_blocker,
                'bug_critical' => $data->bug_critical,
                'bug_major' => $data->bug_major,
                'bug_minor' => $data->bug_minor,
                'bug_info' => $data->bug_info,
                'vulnerability_blocker' => $data->vulnerability_blocker,
                'vulnerability_critical' => $data->vulnerability_critical,
                'vulnerability_major' => $data->vulnerability_major,
                'vulnerability_minor' => $data->vulnerability_minor,
                'vulnerability_info' => $data->vulnerability_info,
                'code_smell_blocker' => $data->code_smell_blocker,
                'code_smell_critical' => $data->code_smell_critical,
                'code_smell_major' => $data->code_smell_major,
                'code_smell_minor' => $data->code_smell_minor,
                'code_smell_info' => $data->code_smell_info,
                'repartition_frontend' => $data->repartition_frontend,
                'repartition_backend' => $data->repartition_backend,
                'repartition_autre' => $data->repartition_autre,
                'repartition_inconnu' => $data->repartition_inconnu,
                'blocker_violations' => $data->blocker_violations,
                'critical_violations' => $data->critical_violations,
                'major_violations' => $data->major_violations,
                'minor_violations' => $data->minor_violations,
                'info_violations' => $data->info_violations,
                'reliability_rating' => $data->reliability_rating,
                'security_rating' => $data->security_rating,
                'sqale_rating' => $data->sqale_rating,
                'security_review_rating' => $data->security_review_rating,
                'menace_potentielle_totale' => $data->menace_potentielle_totale,
                'menace_potentielle_to_review_high' => $data->menace_potentielle_to_review_high,
                'menace_potentielle_to_review_medium' => $data->menace_potentielle_to_review_medium,
                'menace_potentielle_to_review_low' => $data->menace_potentielle_to_review_low,
                'menace_potentielle_reviewed_high' => $data->menace_potentielle_reviewed_high,
                'menace_potentielle_reviewed_medium' => $data->menace_potentielle_reviewed_medium,
                'menace_potentielle_reviewed_low' => $data->menace_potentielle_reviewed_low,
                'mode_collecte' => 'COLLECTE',
                'utilisateur_collecte' => $utilisateur_collecte,
                'date_enregistrement' => $dateEnregistrement
            ];

            /** Normalise les valeurs string 'null' / '' / null en vrai null PHP pour
             *  les champs numériques (Postgres refuse 'null' string sur INT/FLOAT).
             *  Les champs string conservés tels quels via la whitelist $stringKeys. */
            $stringKeys = ['maven_key', 'analyse_key', 'version', 'date_version',
                           'project_name', 'mode_collecte', 'utilisateur_collecte',
                           'date_enregistrement'];
            foreach ($map as $k => $v) {
                if (in_array($k, $stringKeys, true)) {
                    continue;
                }
                if ($v === null || $v === '' || $v === 'null') {
                    $map[$k] = null;
                }
            }

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
                    'message' => "Une erreur lors de l'ajout de données est survenue (Erreur {$historique['code']}).",
                    'trace' => $historique['erreur']
                ], Response::HTTP_OK);
            }

            if ($historique['code'] === 23505) {
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
                'message' => "Erreur globale lors de l'enregistrement des données.",
                'trace' => $e->getMessage()
            ], Response::HTTP_OK);
        }

        /** Tout va bien ! */
        return new JsonResponse(['code' => 200], Response::HTTP_OK);
    }
}
