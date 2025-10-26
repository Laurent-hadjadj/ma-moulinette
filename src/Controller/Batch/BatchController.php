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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\BatchTraitement;
use App\Entity\BatchExecution;
use App\Service\PdfExportService;

/**
 * [Description BatchController]
 */
class BatchController extends AbstractController
{
    private static $timeFormat = "%H:%I:%S";
    private static $europeParis = "Europe/Paris";
    private static $page = 'batch/index.html.twig';
    private static $erreur403 = "⚠️ Vous devez avoir le rôle 'BATCH' pour gérer les traitements (Erreur 403).";

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
        private PdfExportService $pdfExportService
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
    #[Route('/api/traitement/rapport/pdf/{id}', name: 'batch_execution_journal_pdf')]
    public function exportPdf(BatchExecution $batch): Response
    {
        return $this->pdfExportService->generateBatchPdf($batch);
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
        $render['salt'] = $this->getParameter('csrf.salt');
        $render['info_nombre'] = 'x';
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
        $r = $batchTraitementRepos->selectBatchTraitementDateEnregistrementLast();

        if ($r['code'] !== 200) {
            $this->logger->error("[Traitement-Suivi] Échec de la requête selectBatchTraitementDateEnregistrementLast.", [
                'code' => $r['code'] ?? null
            ]);

            $message = "❌ 01 - Nous avons rencontré une erreur inattendue ({$r['code']}).";
            $this->addFlash('notice', [
                'type' => 'alert',
                'message' => $message,
                'debug' => $r['erreur'] ?? null
            ]);

            return $this->render(static::$page, $render);
        }

        // Si aucun traitement n'a été trouvé
        if (empty($r['liste'])) {
            $message = "📌 Aucun traitement trouvé pour aujourd'hui.";
            $this->logger->info("[Traitement-Suivi] {$message}");

            $this->addFlash('notice', [
                'type' => 'info',
                'message' => $message
            ]);

            return $this->render(static::$page, $render);
        }

        // Permet d'obtenir la liste des traitements programmés pour la journée en cours
        // 2024-06-14 17:00:11+02
        $dateTimeDernierBatch = new \DateTime($r['liste'][0]['date'], new \DateTimeZone(static::$europeParis));
        $listeAll = $batchTraitementRepos->selectBatchTraitementLast($dateTimeDernierBatch->format('Y-m-d'));

        if ($listeAll['code'] !== 200) {
            $this->logger->error("[Traitement-Suivi] Échec de la requête selectBatchTraitementLast.", [
                'code' => $r['code'] ?? null,
                'date' => $dateTimeDernierBatch->format('Y-m-d') ?? null
            ]);

            $message = "❌ 02 - Nous avons rencontré une erreur inattendue ({$listeAll['code']}).";
            $this->addFlash('notice', [
                'type' => 'alert',
                'message' => $message,
                'debug' => $listeAll['erreur'] ?? \null
            ]);

            return $this->render(static::$page, $render);
        }

        // Génère les données pour le tableau de suivi
        $traitements = [];
        foreach ($listeAll['liste'] as $traitement) {
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
                $execution = "--:--:--";
            }

            $type = ($traitement['mode_collecte'] === "Auto") ? "automatique" : "manuel";
            $traitements[] = [
                'processus' =>"Tout va bien !",
                'mode_collecte' =>  $traitement['mode_collecte'],
                'message' =>  $message,
                'css' =>  $css,
                'type' =>  $type,
                'titre' =>  $traitement['titre'],
                'portefeuille' =>  $traitement['portefeuille'],
                'projet' =>  $traitement['projet'],
                'responsable' =>  $traitement['responsable'],
                'execution' =>  $execution
            ];
        }

        $render['date'] =  $r['liste'][0]['date'] ?? 'inconnu';
        $render['traitements'] = $traitements;

        return $this->render(static::$page, $render);
    }

/*#[Route('/rapports', name: 'rapport_index')]
public function rapports(BatchExecutionRepository $batchRepo): Response
{
    $batches = $batchRepo->findBy([], ['dateLancement' => 'DESC']);

    // Statistiques
    $total = count($batches);
    $nbErreur = 0;
    $nbSucces = 0;

    foreach ($batches as $batch) {
        $codes = array_map(fn($j) => $j->getCode(), $batch->getCollectes()->toArray());
        if (in_array(500, $codes)) {
            $nbErreur++;
        } else {
            $nbSucces++;
        }
    }

    // Récupération des utilisateurs distincts
    $users = $batchRepo->createQueryBuilder('b')
        ->select('DISTINCT b.utilisateurCollecte')
        ->where('b.utilisateurCollecte IS NOT NULL')
        ->orderBy('b.utilisateurCollecte', 'ASC')
        ->getQuery()
        ->getSingleColumnResult(); // Retourne un tableau de chaînes

    return $this->render('rapport/index.html.twig', [
        'batches' => $batches,
        'users' => $users,
        'total' => $total,
        'nbErreur' => $nbErreur,
        'nbSucces' => $nbSucces,
    ]);
}

public function index(EntityManagerInterface $em)
{
    // ... tes stats existantes
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

}
