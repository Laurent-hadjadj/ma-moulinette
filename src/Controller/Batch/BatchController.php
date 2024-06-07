<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2022.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Batch;

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BatchTraitement;
use DateTime;

/**
 * [Description BatchController]
 */
class BatchController extends AbstractController
{
    public static $timeFormat = "%H:%I:%S";
    public static $europeParis = "Europe/Paris";
    public static $page = 'batch/index.html.twig';
    public static $titre = 'Traitement';
    public static $erreur403 = "Vous devez avoir le rôle 'BATCH' pour gérer les traitements [Erreur 403].";

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
    ) {
        $this->em = $em;
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
    public function traitementSuivi(Request $request): Response
    {
      /** On instancie l'EntityRepository */
        $batchTraitementRepository = $this->em->getRepository(BatchTraitement::class);

        // Initialisation des informations pour la bulle d'information
        $render = [
            'salt' => $this->getParameter('csrf.salt'),
            'infoNombre' => 'x',
            'infoTips' => 'Aucun traitement.',
            'bulle' => 'bulle-info-vide',
            'date' => '01/01/1980',
            'traitements' => [['processus' => 'vide']],
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y')
        ];

        // Vérifier si l'utilisateur a le rôle 'ROLE_BATCH'.
        if (!$this->isGranted('ROLE_BATCH')) {
            $this->addFlash('message', ['type'=>'alert', 'titre'=>static::$titre, 'message'=>static::$erreur403]);
            return $this->render('batch/index.html.twig', $render);
        }

        // Créer un objet date avec le fuseau horaire Europe/Paris
        $date = new \DateTimeImmutable();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        // Obtenir la date du dernier traitement automatique ou programmé
        $r = $batchTraitementRepository->selectBatchTraitementDateEnregistrementLast();
        if ($r['code'] != 200) {
            $message = '1-Nous avons rencontré une erreur inattendue [' . $r['code'] . ']';
            $this->addFlash('message', ['type' => 'alert', 'titre' => static::$titre, 'message' => $message, 'erreur' => $r['erreur']]);
            return $this->render(static::$page, $render);
        }

        // Si aucun traitement n'a été trouvé
        if (empty($r['liste'])) {
            $message = "Aucun traitement trouvé pour aujourd'hui.";
            $this->addFlash('message', ['type' => 'info', 'titre' => static::$titre, 'message' => $message]);
            return $this->render(static::$page, $render);
        }

        // Permet d'obtenir la liste des traitements programmés pour la journée en cours
        $dateTimeDernierBatch = new \DateTime($r['liste'][0]['date']);
        $listeAll = $batchTraitementRepository->selectBatchTraitementLast($dateTimeDernierBatch->format('Y-m-d'));
        if ($listeAll['code'] != 200) {
            $message = '2-Nous avons rencontré une erreur inattendue [' . $listeAll['code'] . ']';
            $this->addFlash('message', ['type' => 'alert', 'titre' => static::$titre, 'message' => $message, 'erreur' => $listeAll['erreur']]);
            return $this->render(static::$page, $render);
        }

        // Génère les données pour le tableau de suivi
        $traitements = [];
        foreach ($listeAll['liste'] as $traitement) {
            if (!empty($traitement['debut'])) {
                $resultat = $traitement['resultat'];

                // Définition du message et de la classe CSS
                $message = ($resultat == 0) ? "Erreur" : "Succès";
                $css = ($resultat == 0) ? "ko" : "ok";

                $debut = new \DateTime($traitement['debut']);
                $fin = new \DateTime($traitement['fin']);
                $interval = $debut->diff($fin);
                $execution = $interval->format(static::$timeFormat);
            } else {
                $message = "---";
                $css = "oko";
                $execution = "--:--:--";
            }

            $type = ($traitement['demarrage'] === "Auto") ? "automatique" : "manuel";

            $traitements[] = [
                'processus' => "Tout va bien !",
                'demarrage' => $traitement['demarrage'],
                'message' => $message,
                'css' => $css,
                'type' => $type,
                'job' => $traitement['titre'],
                'portefeuille' => $traitement['portefeuille'],
                'projet' => $traitement['projet'],
                'responsable' => $traitement['responsable'],
                'execution' => $execution
            ];
        }

        return $this->render(
            'batch/index.html.twig',
            array_merge($render, [
                'date' => $r['liste'][0]['date'],
                'traitements' => $traitements
            ])
        );
    }

}
