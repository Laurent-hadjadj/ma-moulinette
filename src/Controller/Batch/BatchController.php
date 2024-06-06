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
        $batchTraitementEntity = $this->em->getRepository(BatchTraitement::class);

        /** On initialise les information pour la bulle d'information */
        $bulle = 'bulle-info-vide';
        $infoNombre = 'x';
        $infoTips = 'Aucun traitement.';
        $render= [
            'salt' => $this->getParameter('csrf.salt'),
            'infoNombre' => $infoNombre,
            'infoTips' => $infoTips,
            'bulle' => $bulle,
            'date' => '01/01/1980',
            'traitements' => [['processus' => 'vide']],
            'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y')
        ];

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_BATCH')) {
            $this->addFlash('message', ['type'=>'alert', 'titre'=>static::$titre, 'message'=>static::$erreur403]);
            return $this->render('batch/index.html.twig', $render);
            }

        /** On crée un objet date */
        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));
        /**
         * On récupère la date du dernier traitement automatique ou programmé
         * Pour le 08/02/2023 : date" => "2023-02-08 08:57:53"
         */
        $r=$batchTraitementEntity->selectBatchTraitementDateEnregistrementLast();
        if ($r['code']!=200) {
            $message='Nous avons rencontré une erreur inattendue ['.$r['code'].']';
            $this->addFlash('message', ['type'=>'alert', 'titre'=>static::$titre, 'message'=>$message]);
            return $this->render(static::$page, $render);
        }

        /** Si on a pas trouvé de traitements dans la table */
        if (empty($r['liste'])) {
            $message = "Aucun traitement trouvé pour aujourd'hui.";
            $this->addFlash('message', ['type'=>'info', 'titre'=>static::$titre, 'message'=>$message]);
            return $this->render(static::$page, $render);
        }

        /**
         * On récupère la liste des traitements planifiés pour la date du jour.
         */
        /** retourne la date de planification */
        $dateDernierBatch = $r['liste'][0]['date'];
        /** retourne la date au format 2023-02-08 */
        $dateTab = explode(" ", $dateDernierBatch);
        $dateLike = $dateTab[0].'%';
        $listeAll=$batchTraitementEntity->selectBatchTraitementLast($dateLike);
        if ($listeAll['code']!=200) {
            $message=$listeAll['erreur'];
        }

        /** On généré les données pour le tableau de suivi */
        $traitements = [];
        foreach ($listeAll['liste'] as $traitement) {
            /** Calcul de l'execution pour un traitement qui a démarré. */
            if (!empty($traitement['debut'])) {
                $resultat = $traitement['resultat'];

                /** on définit le message et la class css */
                if ($resultat == 0) {
                    $message = "Erreur";
                    $css = "ko";
                } else {
                    $message = "Succès";
                    $css = "ok";
                }
                $debut = new \dateTime($traitement['debut']);
                $fin = new \dateTime($traitement['fin']);
                $interval = $debut->diff($fin);
                $execution = $interval->format(static::$timeFormat);
            }

            /** on définit le type */
            if ($traitement['demarrage'] === "Auto") {
                $type = "automatique";
            } else {
                $type = "manuel";
            }

            /** On formate les données pour les batchs qui n'ont pas été lancé (i.e MANUEL) */
            if (empty($traitement['debut'])) {
                $message = "---";
                $css = "oko";
                $execution = "--:--:--";
            }

            $tempo = ["processus" => "Tout va bien !",
                        /** Auto ou Manuel */
                        'demarrage' => $traitement['demarrage'],
                        /** Succès, Erreur */
                        'message' => $message,
                        /** ok, ko */
                        'css' => $css,
                        /** automatique, manuel */
                        'type' => $type,
                        'job' => $traitement['titre'],
                        'portefeuille' => $traitement['portefeuille'],
                        'projet' => $traitement['projet'],
                        'responsable' => $traitement['responsable'],
                        'execution' => $execution];
            array_push($traitements, $tempo);
        }

        return $this->render(
            'batch/index.html.twig',
            [
                'salt' => $this->getParameter('csrf.salt'),
                'date' => $dateDernierBatch,
                'traitements' => $traitements,
                'bulle' => $bulle,
                'infoNombre' => $infoNombre,
                'infoTips' => $infoTips,
                'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y')
            ]
        );
    }

}
