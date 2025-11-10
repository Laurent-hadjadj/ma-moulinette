<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2024.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Batch;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\{JsonResponse, Response, Request};
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{BatchTraitement, BatchExecution};
use Doctrine\DBAL\Types\DateTimeTzType;

//use App\Service\PdfExportService;

/**
 * [Description BatchController]
 */
class BatchController extends AbstractController
{
    private static $timeFormat = "%H:%I:%S";
    private static $europeParis = "Europe/Paris";
    private static $page = 'batch/index.html.twig';
    private static $erreur403 = "⚠️ Vous devez avoir le rôle 'BATCH' pour gérer les traitements (Erreur 403).";
    private static $erreur400 = "La requête est incorrecte (Erreur 400).";

    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
        private Security $security,
        //private PdfExportService $pdfExportService
    ) {
        $this->params = $params;
        $this->logoEntreprise = $params->get('logo.entreprise');
        $this->marqueEntrepriseShort = $params->get('marque.entreprise.short');
        $this->marqueEntrepriseLong = $params->get('marque.entreprise.long');
        $this->environnement = $params->get('environnement');
        $this->version = $params->get('version');
        $this->dateCopyright = \date('Y');
    }

    private function genericRender(): array
    {
        return [
            'type_footer' => null,
            'logo_entreprise' => $this->logoEntreprise,
            'marque_entreprise_short' => $this->marqueEntrepriseShort,
            'marque_entreprise_long' => $this->marqueEntrepriseLong,
            'env' => $this->environnement,
            'version' => $this->version,
            'date_copyright' => $this->dateCopyright
        ];
    }

    /**
     * [Description for traitementInformation]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 10/11/2025 14:57:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/traitement/information', name: 'traitement_information', methods:'POST')]
    public function traitementInformation(Request $request): JsonResponse
    {
        /** On instancie l'EntityRepository */
        $batchTraitementRepos = $this->em->getRepository(BatchTraitement::class);
        $user = $this->security->getUser();

        // Vérifier si l'utilisateur a le rôle 'ROLE_BATCH'.
        if (!$this->isGranted('ROLE_BATCH')) {
            $this->logger->warning("[Information-Traitement] 🚫 Accès refusé pour l'utilisateur (ROLE_BATCH absent).");

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => "Accès refusé pour l'utilisateur (Erreur 403).",
            ], Response::HTTP_OK);
        }

        /** On récupère les données du POST */
        $data = json_decode($request->getContent());


        if ($data === null || !property_exists($data, 'traitement_id')){
            $this->logger->error("[Information-Traitement] ❌ Requête invalide : clé 'traitement_id' manquante ou JSON mal formé.",[
                'utilisateur' => $user,
                'payload' => $data
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => static::$erreur400
            ], Response::HTTP_OK);
        }

        /** On va chercher les infos du traitement en utilisant traitement_id */
        $traitement_info = $batchTraitementRepos->selectBatchTraitementByTraitementId($data->traitement_id);

        if ($traitement_info['code'] !== 200){
            $this->logger->alert("[Information-Traitement] ❌ Échec de la requête selectBatchTraitementByTraitementId($data->traitement_id.", [
            'code' => $traitement_info,
            'message' => $traitement_info['erreur'] ?? null
            ]);

            return new JsonResponse([
                'code' => $traitement_info['code'],
                'type' => 'error',
                'message' => "Il n'est pas possible de récupèrer les informations du traitement n°<strong>{data->traitement_id}</strong> (Erreur {$traitement_info['code']}).",
                'trace' => $traitement_info['erreur']
            ], Response::HTTP_OK);
        }

        $traitement_info = $traitement_info['traitement'];
        $map = [
            'traitement_id' => $data->traitement_id,
            'mode_collecte' => $traitement_info['mode_collecte'],
            'statut' =>  $traitement_info['success'],
            'nom_traitement' => $traitement_info['titre'],
            'portefeuille' => $traitement_info['portefeuille'],
            'nombre_projet' => $traitement_info['nombre_projet'],
            'start_at' => $traitement_info['debut_traitement'],
            'end_at' => $traitement_info['fin_traitement'],
            'is_activated' => $traitement_info['activated']
        ];
        /*"map": {
                "traitement_id": "019a696f-72b5-517e-21f2-e7a3b3607cb4",
                "mode_collecte": "TRAITEMENT AUTOMATIQUE",
                "statut": true,
                "nom_traitement": "TEST PERFORMANCE",
                "portefeuille": "APPLICATIONS JAVA - LOT 5",
                "nombre_projet": 46,
                "start_at": "2025-11-09 20:45:49+01",
                "end_at": "2025-11-09 20:50:32+01",
                "is_activated": false
            }*/
        return new JsonResponse([
                'code' => 200,
                'message' => "Récupération des données d'information sur le traitement {$data->traitement_id}.",
                'map' => $map,
        ], Response::HTTP_OK);
    }

    /**
     * [Description for traitementSuivi]
     * Interface web : affiche la liste des traitements disponibles.
     *
     * @return Response
     *
     * Created at: 04/12/2022, 08:54:16 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/traitement/suivi', name: 'traitement_suivi', methods:'GET')]
    public function traitementSuivi(): Response
    {
        /** On instancie l'EntityRepository */
        $batchTraitementRepos = $this->em->getRepository(BatchTraitement::class);

        // Initialisation des informations pour la bulle d'information
        /** On initialise le tableau */
        $render = $this->genericRender();
        $render['info_nombre'] = '-';
        $render['info_tips'] = 'Aucun traitement.';
        $render['bulle'] = 'bulle-info-vide';
        $render['date'] = '01/01/1980';
        $render['traitements'] = [['processus' => 'vide']];

        // Vérifier si l'utilisateur a le rôle 'ROLE_BATCH'.
        if (!$this->isGranted('ROLE_BATCH')) {
            $this->logger->warning("[Traitement-Suivi] 🚫 Accès refusé pour l'utilisateur (ROLE_BATCH absent).");

            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => static::$erreur403
            ]);

            return $this->render(static::$page, $render);
        }

        // Obtenir la date du dernier traitement automatique ou programmé
        $r = $batchTraitementRepos->selectBatchTraitementActivated();

        if ($r['code'] !== 200) {
            $this->logger->error("[Traitement-Suivi] Échec de la requête selectBatchTraitementActivated().", [
                'code' => $r['code'] ?? null,
                'erreur' => $r['erreur']
            ]);

            $message = "❌ Il n'est pas possible de récupérer la liste des traitement ({$r['code']}).";
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => $message,
                'debug' => $r['erreur'] ?? null
            ]);

            return $this->render(static::$page, $render);
        }

        // Si aucun traitement n'a été trouvé
        if (empty($r['liste'])) {
            $message = "📌 Aucun traitement trouvé.";
            $this->logger->info("[Traitement-Suivi] {$message}");

            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => $message
            ]);

            return $this->render(static::$page, $render);
        }

        // Génère les données pour le tableau de suivi
        $traitements = [];
        foreach ($r['liste'] as $traitement) {
            if (!empty($traitement['debut'])) {
                // Définition du message et de la classe CSS
                $message = ($traitement['success'] == 0) ? "Erreur" : "Succès";
                $css = ($traitement['success'] == 0) ? "ko" : "ok";

                $debut = new \DateTime($traitement['debut'], new \DateTimeZone(static::$europeParis));
                $fin = new \DateTime($traitement['fin'], new \DateTimeZone(static::$europeParis));
                $interval = $debut->diff($fin);
                $execution = $interval->format(static::$timeFormat);
            } else {
                $message = "---";
                $css = "oko";
                $execution = "--:--.--";
            }

            $type = ($traitement['mode_collecte'] === "TRAITEMENT AUTOMATIQUE") ? "automatique" : "manuel";

            $responsable = $traitement['responsable_short'];
            $responsable_short = $traitement['responsable_short'];
            $usernameParts = explode(' ', $responsable_short, 2);
            $initiales = (count($usernameParts) === 2) ? $usernameParts[0] : '';
            $nom = (count($usernameParts) === 2) ? $usernameParts[1] : $usernameParts[0];
            $hash = array_reduce(str_split($responsable), function ($acc, $char) {
                return $acc + ord($char);
            }, 0);

            $traitements[] = [
                'processus' =>"Tout va bien !",
                'mode_collecte' =>  $traitement['mode_collecte'],
                'message' =>  $message,
                'css' =>  $css,
                'type' =>  $type,
                'titre' =>  $traitement['titre'],
                'portefeuille' =>  $traitement['portefeuille'],
                'projet' =>  $traitement['projet'],
                'responsable' =>  $responsable,
                'initiales' => $initiales,
                'nom' => $nom,
                'color' => $hash % 10,
                'traitement_id' => $traitement['traitement_id'],
                'execution' =>  $execution
            ];
        }
        $render['date'] = new \DateTime('now', new \DateTimeZone(static::$europeParis));
        $render['traitements'] = $traitements;
        return $this->render(static::$page, $render);
    }

    #[Route('/rapports', name: 'rapport_index')]
    public function rapports(): Response
    {
        /** On instancie l'EntityRepository */
        $batchExecutionRepos = $this->em->getRepository(BatchExecution::class);
        $traitements = $batchExecutionRepos->findBy([], ['dateEnregistrement' => 'DESC']);

        // Statistiques
        $total = count($traitements);
        $nombre_success = $nombre_erreur = $nombre_bypass =0;

        foreach ($traitements as $traitement) {
            $codes = array_map(fn($j) => $j->getCode(), $traitement->getCollectes()->toArray());
            $nombre_success = (in_array(200, $codes)) ? $nombre_success++ : $nombre_success;
            $nombre_bypass = (in_array(202, $codes)) ? $nombre_bypass++ : $nombre_bypass;
            $nombre_erreur = (in_array([400, 404, 422, 500, 503], $codes)) ? $nombre_erreur++ : $nombre_erreur;
        }

        // Récupération des utilisateurs distincts
        $users = $batchExecutionRepos->createQueryBuilder('b')
            ->select('DISTINCT b.utilisateurCollecte')
            ->where('b.utilisateurCollecte IS NOT NULL')
            ->orderBy('b.utilisateurCollecte', 'ASC')
            ->getQuery()
            ->getSingleColumnResult(); // Retourne un tableau de chaînes

        return $this->render('batch/rapport.html.twig', [
            'traitements' => $traitements,
            'users' => $users,
            'total' => $total,
            'nombre_erreur' => $nombre_erreur,
            'nombre_success' => $nombre_success,
            'nombre_bypass' => $nombre_bypass
        ]);
    }
}

    /**
     * [Description for exportPdf]
     *
     * @param BatchExecution $batch
     * @param PdfExportService $pdfExportService
     *
     * @return Response
     *
     * Created at: 25/10/2025 22:15:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    /*#[Route('/api/secure/traitement/rapport/pdf/{id}', name: 'batch_execution_journal_pdf')]
    public function exportPdf(BatchExecution $batch): Response
    {
        //return $this->pdfExportService->generateBatchPdf($batch);
    }*/

/*public function index(EntityManagerInterface $em)
{
    // ... stats existantes
    $dateDebut = new \DateTime('-30 days');

    $qb = $em->createQueryBuilder();
    $qb->select('DATE(r.date) as jour, COUNT(r.id) as nbJobs')
        ->from(Rapport::class, 'r')
        ->where('r.date >= :dateDebut')
        ->setParameter('dateDebut', $dateDebut)
        ->groupBy('jour')
        ->orderBy('jour', 'ASC');

    $result = $qb->getQuery()->getResult();

    // Préparer pour JS
    $jours = array_map(fn($r) => $r['jour']->format('Y-m-d'), $result);
    $nbJobs = array_map(fn($r) => $r['nbJobs'], $result);

    return $this->render('rapport/index.html.twig', [
        'total' => $total,
        'nbSucces' => $nbSucces,
        'nbErreur' => $nbErreur,
        'jours' => json_encode($jours),
        'nbJobs' => json_encode($nbJobs),
    ]);
}*/

/**
 * $connection = $this->em->getConnection();
 * $deleted = $connection->fetchOne("SELECT ma_moulinette.purge_batch_profiling(90)");
* $this->logger->info("Purge des batch_profiling : $deleted lignes supprimées");
 */
