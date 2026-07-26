<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/* MODIF 2026-05-24 : la collecte est désormais assurée par
 * ActivityCollecteCommand (cron ou CLI). La route sauvegardeHistorique ne fait
 * plus de collecte — elle agrège les stats depuis les données existantes en base.
 * Supprimé : ClientService, MessageBusInterface, ActivityMessage, lancerBatch(). */
namespace App\Controller\Activity;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use \Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{Activity, ActivityHistorique};

/**
 * [Description ApiActivityController]
 */
class ApiActivityController extends AbstractController
{
    /** Définition des constantes */
    private static string $europeParis   = 'Europe/Paris';
    private static string $erreur400     = "La requête est incorrecte (Erreur 400).";
    private static string $erreur403     = "Vous devez avoir le rôle ACTIVITY pour réaliser cette action (Erreur 403).";
    private static string $loggerE403    = "[Enregistrement] Accès refusé pour l'utilisateur (pas le rôle ROLE_ACTIVITY).";
    private static string $noData        = 'Pas de données';

    /**
     * [Description for __construct]
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private LoggerInterface $logger
    ) {
    }

    private function calculDifferenceDate(\DateTime $premiereDate, \DateTime $secondeDate): int
    {
        return (int) $premiereDate->diff($secondeDate)->format('%a');
    }

    private function formatDureeMax(int $data): string
    {
        return gmdate("H:i:s", $data);
    }

    private function calculAnalyseMoyenne(int $nbJour, int $nbAnalyse): int
    {
        // Moyenne d'analyses PAR JOUR = nombre d'analyses / nombre de jours.
        if ($nbJour === 0) {
            return 0;
        }
        return (int) round($nbAnalyse / $nbJour);
    }

    private function calculeTauxReussite(int $nbAnalyseTotal, int $nbAnalyseReussite): float
    {
        // Taux = réussites / total (borné naturellement à 100 %).
        if ($nbAnalyseTotal === 0) {
            return 0.0;
        }
        return round($nbAnalyseReussite / $nbAnalyseTotal * 100, 1);
    }

    /**
     * [Description for sauvegardeHistorique]
     * Agrège les statistiques d'activité de l'année courante depuis la base.
     * La collecte des tâches SonarQube est assurée par app:activity:collecte (cron).
     *
     * @return JsonResponse
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/activity/sauvegarde', name: 'sauvegarde_historique', methods: ['POST'])]
    public function sauvegardeHistorique(): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/activity/sauvegarde");

        $user = $this->security->getUser();

        if (!$this->isGranted('ROLE_ACTIVITY')) {
            $this->logger->error(self::$loggerE403, ['user' => $user]);
            return new JsonResponse([
                'code'    => 403,
                'type'    => 'warning',
                'message' => self::$erreur403,
            ], Response::HTTP_OK);
        }

        $activityRepos              = $this->em->getRepository(Activity::class);
        $activityHistoriqueRepository = $this->em->getRepository(ActivityHistorique::class);
        $dateActuelle = new \DateTime('now', new \DateTimeZone(self::$europeParis));
        $year         = $dateActuelle->format('Y');

        $result = $activityRepos->premiereDate($year);
        if (empty($result['liste']) || !isset($result['liste']['date'])) {
            return new JsonResponse([
                'code'    => 204,
                'type'    => 'warning',
                'message' => 'Aucune donnée d\'activité pour cette année. Lancez la collecte via la commande app:activity:collecte.',
            ], Response::HTTP_OK);
        }

        $donneeTableau[$year]['day'] = $this->calculDifferenceDate(
            new \DateTime($result['liste']['date']),
            $dateActuelle
        );

        $result = $activityRepos->nombreAnalyse($year);
        $donneeTableau[$year]['analyse'] = (int) ($result['request']['nb_analyse'] ?? 0);

        $result = $activityRepos->nombreStatus($year, 'SUCCESS');
        $donneeTableau[$year]['success'] = (int) ($result['request']['nb_status'] ?? 0);

        $result = $activityRepos->nombreStatus($year, 'FAILED');
        // Clé 'failed' pour correspondre au binding SQL du repository (et non 'fail').
        $donneeTableau[$year]['failed'] = (int) ($result['request']['nb_status'] ?? 0);

        $result = $activityRepos->tempsExecutionMax($year);
        // Stocké en SECONDES (colonne max_time INTEGER) ; formaté H:i:s à l'affichage.
        $donneeTableau[$year]['max_time'] = (int) ($result['request']['max_time'] ?? 0);

        // La colonne « Analyse » conserve le TOTAL ; la moyenne va dans sa colonne dédiée.
        $donneeTableau[$year]['analyse_average'] = $this->calculAnalyseMoyenne(
            $donneeTableau[$year]['day'],
            $donneeTableau[$year]['analyse']
        );
        // Taux de réussite = succès / total (et non l'inverse).
        $donneeTableau[$year]['success_rate'] = $this->calculeTauxReussite(
            $donneeTableau[$year]['analyse'],
            $donneeTableau[$year]['success']
        );
        $donneeTableau[$year]['date_enregistrement'] = $dateActuelle;

        // selectActivity() renvoie la clé 'liste' (et non 'request').
        $verifUpdateOuInsert = $activityHistoriqueRepository->selectActivity($year);
        if (empty($verifUpdateOuInsert['liste'])) {
            $ecriture = $activityHistoriqueRepository->insertHistoriqueActivity($donneeTableau);
        } else {
            $ecriture = $activityHistoriqueRepository->updateHistoriqueActivity($donneeTableau);
        }

        // On NE masque plus un échec d'écriture (sinon le front affiche « Aucune donnée »
        // sans raison). Si l'insert/update échoue, on remonte l'erreur explicitement.
        if (($ecriture['code'] ?? 200) !== 200) {
            $this->logger->error('[API] ❌ Échec écriture activity_historique.', $ecriture);
            return new JsonResponse([
                'code'    => 500,
                'type'    => 'error',
                'message' => "Échec de l'enregistrement des statistiques : " . ($ecriture['erreur'] ?? 'erreur inconnue'),
            ], Response::HTTP_OK);
        }

        $tableHistoriqueActivity = $activityHistoriqueRepository->selectActivity();
        if (!empty($tableHistoriqueActivity['liste'])) {
            // max_time est stocké en secondes → formaté H:i:s pour l'affichage.
            foreach ($tableHistoriqueActivity['liste'] as $i => $ligne) {
                $tableHistoriqueActivity['liste'][$i]['max_time'] = $this->formatDureeMax((int) ($ligne['max_time'] ?? 0));
            }
            $tableHistoriqueActivity['liste'][0]['date_enregistrement'] =
                (new \DateTime($tableHistoriqueActivity['liste'][0]['date_enregistrement']))->format('d-m-Y H:i:s');
        }

        return new JsonResponse([
            'code'       => 200,
            'listeDonnee' => $tableHistoriqueActivity,
        ], Response::HTTP_OK);
    }

    /**
     * [Description for apiDessin]
     * Récupération des données pour les graphiques d'activité.
     *
     * @return JsonResponse
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/activity/dessin', name: 'api_dessin', methods: ['POST'])]
    public function apiDessin(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/activity/dessin");

        $user = $this->security->getUser();

        /** si on n'a pas le rôle ACTIVITY on ne fait rien. */
        if (!$this->isGranted('ROLE_ACTIVITY')) {
            $this->logger->error(self::$loggerE403, ['user' => $user]);
            return new JsonResponse([
                'code'    => 403,
                'type'    => 'warning',
                'message' => self::$erreur403,
            ], Response::HTTP_OK);
        }

        /** On récupère la date actuelle */
        $dateActuelle = new \DateTime('now', new \DateTimeZone(self::$europeParis));

        /** On instancie la class */
        $activityRepos = $this->em->getRepository(Activity::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'source') || !is_string($data->source)) {
            $this->logger->error("[Activity-Dessin] Requête invalide : clé 'source' manquante ou JSON mal formé.", [
                'payload' => $data ?? self::$noData,
            ]);

            return new JsonResponse([
                'code'    => 400,
                'type'    => 'error',
                'message' => self::$erreur400,
            ], Response::HTTP_OK);
        }

        $source   = $data->source;
        $response = [];
        switch ($source) {
            case 'analyse':
                $response = $activityRepos->listeAnalyseJour($dateActuelle->format('Y'));
                break;
            case 'projet':
                $response = $activityRepos->listeProjectAnalyse($dateActuelle->format('Y'));
                break;
            case 'projet_analyse':
                $response = $activityRepos->listeAnalyseProjet($dateActuelle->format('Y'));
                break;
            default:
                $this->logger->error("[Activity-Dessin] ❌ Source inconnue.", ['source' => $source]);

                return new JsonResponse([
                    'code'    => 400,
                    'type'    => 'error',
                    'message' => "La source demandée est inconnue (Erreur 400).",
                ], Response::HTTP_OK);
        }

        return new JsonResponse([
            'code' => 200,
            'listeDonnee' => $response
        ], Response::HTTP_OK);
    }
}
