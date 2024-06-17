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

/** Securité */
use Symfony\Bundle\SecurityBundle\Security;

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
    public static $erreur403 = "Vous devez avoir le rôle ACTIVITE pour réaliser cette action (Erreur 403).";
    public static $erreur404 = "Je n'ai pas trouvé de projets sur le serveur sonarqube (Erreur 404).";

    /**
     * [Description for __construct]
     *
     * @param mixed
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
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
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/activite/sauvegarde', name: 'api_sauvegarde_historique', methods: ['POST'])]
    public function apiSauvegardeHistorique(ClientActivite $client, Security $security): response
    {
        $response = new JsonResponse();

        /** si on est pas GESTIONNAIRE on ne fait rien. */
        if (!$security->isGranted('ROLE_ACTIVITE')){
            return $response->setData(['code' => 403, Response::HTTP_OK]);
        }
        /** On instancie l'EntityRepository */
        $activiteEntity = $this->em->getRepository(Activite::class);
        $historiqueActiviteEntity = $this->em->getRepository(ActiviteHistorique::class);

        $url = $this->getParameter(static::$sonarUrl) . "/api/ce/activity";
        $result = $client->http($url);
        // dateBase represente la date la plus recente dans la base de donnée
        // On ne prend pas directement la donnee de date parce que la base peux ne peut pas avoir de donnée
        $dateBase = $activiteEntity->dernierDate();

        if (empty($dateBase['request'])){
            // methode pour inserer si la base est vierge
            $formatedData = static::organisationDonnee($result);
            $activiteEntity->insertActivites($formatedData);
        }else{
            // Méthode pour insérer si la base n'est pas vierge.
            // Cette méthode consiste à prendre toutes les analyse dans un intervalle de date représenter par dateMin et dateMax.
            // Puis vas insérer ces analyses
            $dateBase = (new DateTime($dateBase['request'][0]['date']))->modify('+1 days');
            $dateActuelle = new DateTime();
            $dateActuelleMoins1 = $dateActuelle->modify('-1 days');
            $dateMin = clone $dateBase;
            $dateMax = (clone $dateMin)->modify('+7 days');
            while($dateMin < $dateActuelleMoins1){
                $url = $this->getParameter(static::$sonarUrl) . "/api/ce/activity?minSubmittedAt=".$dateMin->format('Y-m-d')."&maxExecutedAt=".$dateMax->format('Y-m-d')."";
                $result = $client->http($url);
                $formatedData = static::organisationDonnee($result);
                $activiteEntity->insertActivites($formatedData);
                $dateMin = $dateMin->modify('+7 days');
                $dateMax = $dateMax->modify('+7 days');
            }
        }



        // On recupere l'anne actuelle
        $dateActuelle = new DateTime();
        $dateActuelle->setTimezone(new DateTimeZone('Europe/Paris'));
        $anneeActuelle = $dateActuelle->format('Y');

        // On forme le tableau qui va etre envoyé dans la vue
        // Le nombre de jour pour cette annee
        $result=$activiteEntity->premiereDate($anneeActuelle);
        $donneeTableau[$anneeActuelle]['nb_jour'] = static::calculDifferenceDate(new DateTime ($result['request'][0]['date']),$dateActuelle);

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
        $donneeTableau[$anneeActuelle]['max_temps']= static::formatDureemax($result['request']['max_time']);

        // La moyenne d'analyse par jour
        $donneeTableau[$anneeActuelle]['moyenne_analyse'] = static::calculAnalyseMoyenne($donneeTableau[$anneeActuelle]['nb_jour'], $donneeTableau[$anneeActuelle]['nb_analyse']);

        // Taux d'analyse reussite en '%'
        $donneeTableau[$anneeActuelle]['taux_reussite'] = static::calculeTauxReussite($donneeTableau[$anneeActuelle]['nb_analyse'], $donneeTableau[$anneeActuelle]['nb_reussi']);

        // Date d'enregistrement
        $donneeTableau[$anneeActuelle]['date_enregistrement'] = new DateTimeImmutable();

        $verifUpdateOuInsert = $historiqueActiviteEntity->selectActivite($anneeActuelle);
        if (empty($verifUpdateOuInsert['request'])) { // Utilisation de empty() pour vérifier si le tableau est vide
            $historiqueActiviteEntity->insertHistoriqueActivites($donneeTableau);
        } else {
            $historiqueActiviteEntity->updateHistoriqueActivites($donneeTableau);
        }
        $tableHistoriqueActivite = $historiqueActiviteEntity->selectActivite();
        $tableHistoriqueActivite['request'][0]["date_enregistrement"] = (new DateTime($tableHistoriqueActivite['request'][0]["date_enregistrement"]))->format('d-m-Y H:i:s');

        return $response->setData(['code' => 200,'listeDonnee' => $tableHistoriqueActivite, Response::HTTP_OK]);
    }

    /**
     * [Description for projetListe]
     * Récupération de la liste des projets.
     * http://{url}}/api/components/search_projects?ps=500
     *
     * @param ClientActivite $client
     * @return response
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/activite/dessin', name: 'api_dessin', methods: ['POST'])]
    public function apiRecupereDonnee(Request $request): response
    {
        /**On recupere la date actuelle */
        $dateActuelle = new DateTime();

        /** On instancie la classe */
        $activiteEntity = $this->em->getRepository(Activite::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On instancie une nouvelle response */
        $response = new JsonResponse();

      /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'source')) {
            return $response->setData(
                ['data'=>$data,'code'=>400, Response::HTTP_BAD_REQUEST]);
        }

        /**On récupère les données demandés */

        $source = $data->source;
        switch ($source) {
            case 'analyse':
                $response = $activiteEntity->listeAnalyseJour($dateActuelle->format('Y'));
                break;
            case 'projet':
                $response = $activiteEntity->listeProjectAnalyse($dateActuelle->format('Y'));
                break;
            case 'projet_analyse':
                $response = $response = $activiteEntity->listeAnalyseProjet($dateActuelle->format('Y'));
                break;
            default:
            //to.do gestion des insertions de donnée des utilisateurs
                break;
        }
        return new JsonResponse(['code' => 200, 'listeDonnee' => $response], Response::HTTP_OK);
    }

    private function calculDifferenceDate(DateTime $premiereDate, DateTime $secondeDate) : int
    {
        return (int) $premiereDate->diff($secondeDate)->format('%a');
    }


    private function formatDureeMax($data): string
    {
        return gmdate("H:i:s", $data);
    }

    private function calculAnalyseMoyenne($nbJour,$nbAnalyse): int
    {
        return (int) $nbJour/$nbAnalyse;
    }

    private function calculeTauxReussite($nbAnalyseTotal,$nbAnalyseReussite): float
    {
        return $nbAnalyseTotal/$nbAnalyseReussite*100;
    }

    private function organisationDonnee($data): array
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

}
