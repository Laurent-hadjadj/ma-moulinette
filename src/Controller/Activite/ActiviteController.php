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

use DateTime;
use DateTimeZone;

use App\Entity\Activite;
use App\Service\ClientActivite;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\ORM\EntityManagerInterface;

class ActiviteController extends AbstractController
{

    public static $sonarUrl = "sonar.url";

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 22:14:50 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(private EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * [Description for index]
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:14:55 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/activite', name: 'activite', methods: 'GET')]
    public function index(ClientActivite $client): Response
    {
        /** On instancie l'EntityRepository */
        $activiteEntity = $this->em->getRepository(Activite::class);

        // On recupere l'anne actuelle
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone('Europe/Paris'));
        $anneeActuelle = $date->format('Y');

        // On forme le tableau qui va etre envoyé dans la vue
        // Le nombre de jour pour cette annee
        $result=$activiteEntity->nombreJourAnneeDonnee($anneeActuelle);
        $request[$anneeActuelle]['nb_jour'] = $result['request']['unique_days'];

        // Le nombre d'analyse pour cette annee
        $result = $activiteEntity->nombreAnalyse($anneeActuelle);
        $request[$anneeActuelle]['nb_analyse'] = $result['request']['nb_analyse'];

        // Le nombre d'analyse reussi ou en echec pour cette annee
        // Reussi
        $statusRechercher = 'SUCCESS';
        $result = $activiteEntity->nombreStatus($anneeActuelle,$statusRechercher);
        $request[$anneeActuelle]['nb_reussi'] = $result['request']['nb_status'];
        // Echec
        $statusRechercher = 'FAILED';
        $result = $activiteEntity->nombreStatus($anneeActuelle,$statusRechercher);
        $request[$anneeActuelle]['nb_echec'] = $result['request']['nb_status'];

        // Le temps max d'execution pour cette annee
        $result=$activiteEntity->tempsExecutionMax($anneeActuelle);
        $request[$anneeActuelle]['max_temps']= static::formatDuréemax($result['request']['max_time']);

        // La moyenne d'analyse par jour
        $request[$anneeActuelle]['moyenne_analyse'] = static::calculAnalyseMoyenne($request[$anneeActuelle]['nb_jour'], $request[$anneeActuelle]['nb_analyse']);

        // Taux d'analyse reussite en '%'
        $request[$anneeActuelle]['taux_reussite'] = static::calculeTauxReussite($request[$anneeActuelle]['nb_analyse'], $request[$anneeActuelle]['nb_reussi']);

        return $this->render('activite/index.html.twig', [
            'activites' => $request,'version' => $this->getParameter('version'), 'dateCopyright' => \date("Y"),
            Response::HTTP_OK]);
    }

    public function formatDuréemax(int $data): String
    {
        return gmdate("H:i:s", $data);
    }

    public function calculAnalyseMoyenne($nbJour,$nbAnalyse): int
    {
        return (int) $nbJour/$nbAnalyse;
    }

    public function calculeTauxReussite($nbAnalyseTotal,$nbAnalyseReussite): float
    {
        return $nbAnalyseTotal/$nbAnalyseReussite*100;
    }
}
