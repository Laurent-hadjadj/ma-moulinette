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

namespace App\Controller\Activity;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use \Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{Activity, ActivityHistorique};
use App\Message\ActivityMessage;
use App\Service\ClientService;

/**
 * [Description ApiActivityController]
 */
class ApiActivityController extends AbstractController
{
    /** Définition des constantes */
    private static string $sonarUrl = "sonar.url";
    private static string $europeParis = 'Europe/Paris';
    private static string $plus7days = "+7 days";
    private static string $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static string $erreur403 = "Vous devez avoir le rôle ACTIVITY pour réaliser cette action (Erreur 403).";
    private static string $loggerE403 = "[Enregistrement] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_ACTIVITY).";
    private static string $noData = 'Pas de données';

    /**
     * [Description for __construct]
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ClientService $client,
        private MessageBusInterface $messageBus,
        private Security $security,
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for lancerBatch]
     *  Lance le batch pour récupérer les tâches et les publier dans Messenger
     *
     * @param  \DateTime $oldestDate
     *
     * @return void
     *
     * Created at: 01/01/2025 11:29:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function lancerBatch(\DateTime $oldestDate): void
    {
        $dateFin = (new \DateTime('now', new \DateTimeZone(self::$europeParis)))->modify('-1 day'); // Jusqu'à J-1

        while ($oldestDate <= $dateFin) {
            $fromDate = $oldestDate->format('Y-m-d H:i:s');
            $toDate = $oldestDate->modify('+1 day')->format('Y-m-d H:i:s');

            // Créer un ActivityMessage pour cet intervalle
            $activityMessage = new ActivityMessage($fromDate, $toDate);

            // Publier le message dans Messenger
            $this->messageBus->dispatch($activityMessage);

            // Passer au jour suivant
            $oldestDate = new \DateTime($toDate); // Éviter les références partagées
        }
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
        if ($nbAnalyse === 0) {
            return 0;
        }
        return (int) ($nbJour / $nbAnalyse);
    }

    private function calculeTauxReussite(int $nbAnalyseTotal, int $nbAnalyseReussite): float
    {
        if ($nbAnalyseReussite === 0) {
            return 0.0;
        }
        return $nbAnalyseTotal / $nbAnalyseReussite * 100;
    }

    /**
     * [Description for organisationDonnee]
     * Cette fonction génère le tableau des données à injecter en base
     *
     *
     * @return array<int|string, mixed>
     *
     * Created at: 24/12/2024 17:46:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function organisationDonnee(array $data): array
    {
        $map = [];
        $id = 0;
        foreach ($data['tasks'] as $value) {
            $map[$id]['maven_key'] = $value['componentKey'];
            $map[$id]['project_name'] = $value['componentName'];
            $map[$id]['analyse_id'] = $value['analysisId'];
            $map[$id]['status'] = $value['status'];
            $map[$id]['submitter_login'] = $value['submitterLogin'];
            $map[$id]['submitted_at'] =  new \DateTimeImmutable($value['submittedAt']);
            $map[$id]['started_at'] = new \DateTimeImmutable($value['startedAt']);
            $map[$id]['executed_at'] = new \DateTimeImmutable($value['executedAt']);
            $map[$id]['execution_time'] = (int) round($value['executionTimeMs'] / 1000) + 1; // Conversion de l'input en ms en s
            $id++;
        }
        return $map;
    }

    /**
     * [Description for projetListe]
     * Récupération de la liste des projets.
     * http://{url}}/api/components/search_projects?ps=500
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

        /** On instancie la classe */
        $activityRepos = $this->em->getRepository(Activity::class);
        $activityHistoriqueRepository = $this->em->getRepository(ActivityHistorique::class);

        /** si on est pas GESTIONNAIRE on ne fait rien. */
        if (!$this->isGranted('ROLE_ACTIVITY')) {
            $this->logger->error(self::$loggerE403, ['user' => $user]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403
            ], Response::HTTP_OK);
        }

        /* Récupération de la date la plus ancienne :
         * pour SonarQube 8 ps=1,
         * pour SonarQube 9 p=1, ps=1  ==>  page.total
         */
        $url = $this->getParameter(self::$sonarUrl);
        $queryParams = ['p' => 1, 'ps' => 1];
        $result = $this->client->httpActivity("$url/api/ce/activity?" . http_build_query($queryParams));

        if (isset($result['code']) && in_array($result['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error('[Activity-Sauvegarde] ❌ Échec de l\'appel httpActivity.', [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => $result['code'],
                'type' => 'error',
                'message' => "Une erreur s'est produite lors de la collecte des indicateurs d'activité (Erreur {$result['code']}).",
                'trace' => $result['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        $tasks = $result['json']['tasks'] ?? [];
        $paging = $result['json']['paging'] ?? [];

        if (empty($tasks)) {
            return new JsonResponse([
                'code' => 204,
                'type' => 'warning',
                'message' => 'Aucune tâche disponible'
            ], Response::HTTP_OK);
        }

        // Récupérer la date la plus ancienne
        if (empty($paging)) {
            // SonarQube 8 : Date de la dernière tâche dans les 1000 premières
            $oldestDate = end($tasks)['submitted_at'];
        } else {
            // SonarQube 9+ : Récupération de la dernière page
            $totalTasks = $paging['total'];
            $lastPage = (int) ceil($totalTasks / 1000);

            $queryParams['p'] = $lastPage;
            $result = $this->client->httpActivity("$url/api/ce/activity?" . http_build_query($queryParams));

            $tasks = $result['json']['tasks'] ?? [];
            if (empty($tasks)) {
                $this->logger->warning('[Activity-Sauvegarde] ⚠️ Dernière page sans tâches, abandon.');
                return new JsonResponse([
                    'code' => 204,
                    'type' => 'warning',
                    'message' => 'Aucune tâche disponible sur la dernière page.'
                ], Response::HTTP_OK);
            }
            $oldestDate = end($tasks)['submitted_at'];
        }

        // Lancer le batch pour traiter les tâches de cette date jusqu’à J-1
        $this->lancerBatch(new \DateTime($oldestDate));

        // dateBase représente la date la plus récente dans la base de donnée
        // On ne prend pas directement la donnee de date parce que la base peux ne peut pas avoir de donnée
        $dateBase = $activityRepos->dernierDate();
        $dateActuelle = new \DateTime('now', new \DateTimeZone(self::$europeParis));

        /* La liste est vide ? */
        if (empty($dateBase['liste'])) {
            // méthode pour insérer si la base est vierge
            $map = $this->organisationDonnee($result['json']);
            $insert = $activityRepos->insertActivity($map);
            if ($insert['code'] !== 200) {
                $this->logger->error('[Activity-Sauvegarde] ❌ Échec de la requête insertActivity.', [
                    'code' => $insert['code'],
                    'erreur' => $insert['erreur'] ?? self::$noData
                ]);
                return new JsonResponse([
                    'code' => $insert['code'],
                    'type' => 'error',
                    'message' => "Une erreur s'est produite lors de l'insertion des indicateurs d'activité (Erreur {$insert['code']}).",
                    'trace' => $insert['erreur'] ?? self::$noData
                ], Response::HTTP_OK);
            }
        } else {
            // Méthode pour insérer si la base n'est pas vierge.
            // Cette méthode consiste à prendre toutes les analyse dans un intervalle de date représenter par dateMin et dateMax.
            // Puis vas insérer ces analyses
            $dateBase = (new \DateTime($dateBase['liste'][0]['date']))->modify('+1 days');
            $dateActuelleMoins1 = (clone $dateActuelle)->modify('-1 days');
            $dateMin = clone $dateBase;
            $dateMax = (clone $dateMin)->modify(self::$plus7days);
            while ($dateMin < $dateActuelleMoins1) {
                $url = $this->getParameter(self::$sonarUrl) . "/api/ce/activity?minSubmittedAt=" . $dateMin->format('Y-m-d') . "&maxExecutedAt=" . $dateMax->format('Y-m-d') . "";
                $result = $this->client->httpActivity($url);
                $formattedData = $this->organisationDonnee($result['json']);
                $insertBatch = $activityRepos->insertActivity($formattedData);
                if ($insertBatch['code'] !== 200) {
                    $this->logger->error('[Activity-Sauvegarde] ❌ Échec d\'une insertion en batch.', [
                        'code' => $insertBatch['code'],
                        'erreur' => $insertBatch['erreur'] ?? self::$noData,
                        'date_min' => $dateMin->format('Y-m-d'),
                        'date_max' => $dateMax->format('Y-m-d')
                    ]);
                }
                $dateMin = $dateMin->modify(self::$plus7days);
                $dateMax = $dateMax->modify(self::$plus7days);
            }
        }

        // On récupère l'année actuelle
        $date = new \DateTime('now', new \DateTimeZone(self::$europeParis));
        $year = $date->format('Y');

        // On forme le tableau qui va être envoyé dans la vue
        // Le nombre de jour pour cette année
        $result = $activityRepos->premiereDate($year);
        $donneeTableau[$year]['day'] = $this->calculDifferenceDate(new \DateTime($result['liste'][0]['date']), $dateActuelle);

        // Le nombre d'analyse pour cette année
        $result = $activityRepos->nombreAnalyse($year);
        $donneeTableau[$year]['analyse'] = $result['liste']['nb_analyse'];

        // Le nombre d'analyse réussi ou en échec pour cette année
        // Réussi
        $statusRechercher = 'SUCCESS';
        $result = $activityRepos->nombreStatus($year, $statusRechercher);
        $donneeTableau[$year]['success'] = $result['liste']['nb_status'];
        // Échec
        $statusRechercher = 'FAILED';
        $result = $activityRepos->nombreStatus($year, $statusRechercher);
        $donneeTableau[$year]['fail'] = $result['liste']['nb_status'];

        // Le temps max d'execution pour cette année
        $result = $activityRepos->tempsExecutionMax($year);
        $donneeTableau[$year]['max_time'] = $this->formatDureeMax($result['liste']['max_time']);

        // La moyenne d'analyse par jour
        $donneeTableau[$year]['analyse'] = $this->calculAnalyseMoyenne($donneeTableau[$year]['day'], $donneeTableau[$year]['analyse']);

        // Taux d'analyse réussite en '%'
        $donneeTableau[$year]['success_rate'] = $this->calculeTauxReussite($donneeTableau[$year]['analyse'], $donneeTableau[$year]['success']);

        // Date d'enregistrement
        $donneeTableau[$year]['date_enregistrement'] = $date;

        $verifUpdateOuInsert = $activityHistoriqueRepository->selectActivity($year);
        if (empty($verifUpdateOuInsert['request'])) {
            // Utilisation de empty() pour vérifier si le tableau est vide
            $activityHistoriqueRepository->insertHistoriqueActivity($donneeTableau);
        } else {
            $activityHistoriqueRepository->updateHistoriqueActivity($donneeTableau);
        }
        $tableHistoriqueActivity = $activityHistoriqueRepository->selectActivity();
        $tableHistoriqueActivity['request'][0]["date_enregistrement"] = (new \DateTime($tableHistoriqueActivity['request'][0]["date_enregistrement"]))->format('d-m-Y H:i:s');

        return new JsonResponse([
            'code' => 200,
            'listeDonnee' => $tableHistoriqueActivity
        ], Response::HTTP_OK);
    }

    /**
     * [Description for projetListe]
     * Récupération de la liste des projets.
     * http://{url}}/api/components/search_projects?ps=500
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
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403
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
            $this->logger->error("[Activity-Dessin] ❌ Requête invalide : clé 'source' manquante ou JSON mal formé.", [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** On récupère les données demandées */
        $source = $data->source;
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
                    'code' => 400,
                    'type' => 'error',
                    'message' => "La source demandée est inconnue (Erreur 400)."
                ], Response::HTTP_OK);
        }

        return new JsonResponse([
            'code' => 200,
            'listeDonnee' => $response
        ], Response::HTTP_OK);
    }

}
