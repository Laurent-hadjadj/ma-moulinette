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

namespace App\Controller\Activite;

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

/** Accès aux tables SLQLite */
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ListeProjet;
use App\Entity\Properties;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/** Client HTTP */
use App\Service\ClientActivite;

use App\Entity\Activite;
use App\Entity\ActiviteHistorique;
use DateTimeImmutable;




/**
 * [Description ApiHomeController]
 */
class ApiActiviteController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $dateFormatShort = "Y-m-d";
    public static $dateFormat = "Y-m-d H:i:s";
    public static $europeParis = "Europe/Paris";
    public static $reference = '<strong>[Accueil]</strong>';
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";
    public static $erreur404 = "Je n'ai pas trouvé de projets sur le serveur sonarqube (Erreur 404).";

    /**
     * [Description for __construct]
     *
     * @param mixed
     *
     * Created at: 15/12/2022, 21:12:55 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->em = $em;
    }

    /**
     * [Description for projetListe]
     * Récupération de la liste des projets.
     * http://{url}}/api/components/search_projects?ps=500
     *
     * @param ClientActivite $client
     * @return response
     *
     * Created at: 15/12/2022, 21:15:04 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/activite/sauvegarde', name: 'api_sauvegarde_historique', methods: ['POST'])]
    public function apiSauvegardeHistorique(ClientActivite $client): response
    {
        $response = new JsonResponse();

        /** On instancie l'EntityRepository */
        $activiteEntity = $this->em->getRepository(Activite::class);
        $historiqueActiviteEntity = $this->em->getRepository(ActiviteHistorique::class);



        $url = $this->getParameter(static::$sonarUrl) . "/api/ce/activity";
        $result = $client->http($url);
        $formatedData = static::organisationDonnée($result);

        $activiteEntity->insertActivites($formatedData);


        // On recupere l'anne actuelle
        $dateMoins1 = new DateTime();
        $dateMoins1->setTimezone(new DateTimeZone('Europe/Paris'));
        $anneeActuelle = $dateMoins1->format('Y');

        // On forme le tableau qui va etre envoyé dans la vue
        // Le nombre de jour pour cette annee
        $result=$activiteEntity->nombreJourAnneeDonnee($anneeActuelle);
        $donneeTableau[$anneeActuelle]['nb_jour'] = $result['request']['unique_days'];

        // Le nombre d'analyse pour cette annee
        $result = $activiteEntity->nombreAnalyse($anneeActuelle);
        $donneeTableau[$anneeActuelle]['nb_analyse'] = $result['request']['nb_analyse'];

        // Le nombre d'analyse reussi ou en echec pour cette annee
        // Reussi
        $statusRechercher = 'SUCCESS';
        $result = $activiteEntity->nombreStatus($anneeActuelle,$statusRechercher);
        $donneeTableau[$anneeActuelle]['nb_reussi'] = $result['request']['nb_status'];
        // Echec
        $statusRechercher = 'FAILED';
        $result = $activiteEntity->nombreStatus($anneeActuelle,$statusRechercher);
        $donneeTableau[$anneeActuelle]['nb_echec'] = $result['request']['nb_status'];

        // Le temps max d'execution pour cette annee
        $result=$activiteEntity->tempsExecutionMax($anneeActuelle);
        $donneeTableau[$anneeActuelle]['max_temps']= static::formatDuréemax($result['request']['max_time']);

        // La moyenne d'analyse par jour
        $donneeTableau[$anneeActuelle]['moyenne_analyse'] = static::calculAnalyseMoyenne($donneeTableau[$anneeActuelle]['nb_jour'], $donneeTableau[$anneeActuelle]['nb_analyse']);

        // Taux d'analyse reussite en '%'
        $donneeTableau[$anneeActuelle]['taux_reussite'] = static::calculeTauxReussite($donneeTableau[$anneeActuelle]['nb_analyse'], $donneeTableau[$anneeActuelle]['nb_reussi']);

        // Date d'enregistrement
        $donneeTableau[$anneeActuelle]['date_enregistrement'] = new DateTimeImmutable();

        $historiqueActiviteEntity->insertActivites($donneeTableau);
        $tableHistoriqueActivite = $historiqueActiviteEntity->selectAllActivite();

        return $response->setData(['code' => 200,'listeDonnee' => $tableHistoriqueActivite, Response::HTTP_OK]);
    }



    private function formatDuréeMax($data): DateTimeImmutable
    {
        return (new DateTimeImmutable())->setTimestamp($data);
    }

    private function calculAnalyseMoyenne($nbJour,$nbAnalyse): int
    {
        return (int) $nbJour/$nbAnalyse;
    }

    private function calculeTauxReussite($nbAnalyseTotal,$nbAnalyseReussite): float
    {
        return $nbAnalyseTotal/$nbAnalyseReussite*100;
    }

    private function organisationDonnée($data): array
    {
        $tab = array();
        $id= 0;
        foreach($data['tasks'] as $value){
        $tab[$id]['maven_key'] = $value['componentKey'];
        $tab[$id]['project_name'] = $value['componentName'];
        $tab[$id]['analyse_id'] = $value['analysisId'];
        $tab[$id]['status'] = $value['status'];
        $tab[$id]['submitter_login']= $value['submitterLogin'];
        $tab[$id]['submitted_at'] =  new DateTimeImmutable($value['submittedAt']);
        $tab[$id]['started_at'] = new DateTimeImmutable($value['startedAt']);
        $tab[$id]['executed_at'] = new DateTimeImmutable($value['executedAt']);
        $tab[$id]['execution_time'] = (int) round($value['executionTimeMs'] / 1000)+1; // Conversion de l'input en ms en s
        $id++;
        }
        return $tab;
    }

    /*if (!$this->isGranted('ROLE_COLLECTE')) {
        return $response->setData([
            'type'=>'warning', 'code' => 403,
            'reference' => static::$reference,
            'message' => static::$erreur403, Response::HTTP_OK]);
    }
    */

}
