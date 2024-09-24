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

namespace App\Controller\Suivi;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Historique;
use App\Entity\ListeProjet;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Exception\FetchDataException;

/**
 * [Description SuiviController]
 */
class SuiviController extends AbstractController
{
    /** Définition des constantes */
    public static $route= "suivi/index.html.twig";
    public static $reference = "[SUIVI]";
    public static $erreur = "Une erreur s'est produite (erreur ";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur404 = "Vous devez être rattaché à une équipe (erreur 404).";
    public static $erreur406 = "Je n'ai pas trouvé de projets pour ton équipe. ".
    "Vérifiez le nom du tag utilisé dans SonarQube (erreur 406).";

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 22:34:06 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private $em;
    private $security;

    public function __construct(
        EntityManagerInterface $em,
        Security $security
    ) {
        $this->em = $em;
        $this->security = $security;
    }

    /**
     * [Description for listeProjet]
     *
     * @param $mavenKey array
     * @param $teams array
     *
     * @return array
     *
     * Created at: 16/07/2024 20:05:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function listeProjet($mavenKey, $teams): array
    {
        /** On instancie l'entityRepository */
        $listeProjetRepository = $this->em->getRepository(ListeProjet::class);

        /** On recherche les projets pour les équipes rattaché à l'utilisateur */
        $in = '';
        foreach ($teams as $team) {
            if ($team !== 'null') {
                /** On met en minuscule */
                $minus = trim(strtolower($team));
                /** On construit la clause in et on remplace les espaces par des tirets  */
                $in = $in." tag LIKE '".preg_replace('/\s+/', '-', $minus)."%' OR ";
            }
        }

        /** On supprime le dernier OR */
        $inTrim = rtrim($in, " OR ");

        /** On construit la requête de selection des projets en fonction de(s) (l')équipes */
        $map=['clause_where'=>$inTrim];
        $requestListe = $listeProjetRepository->selectListeProjetByEquipe($map);
        if ($requestListe['code']!=200) {
            return ['code' => $requestListe['code']];
        }

        $projets = $requestListe['liste'];

        /** j'ai pas trouvé de projet pour cette équipe. */
        if (empty($projets)) {
            return ['code'=>406, 'message' => static::$erreur406];
        }

        $searchId = $mavenKey;
        $idFound = false;

        foreach ($projets as $item) {
            if (isset($item['id']) && $item['id'] === $searchId) {
                $idFound = true;
                break;
            }
        }
        if ($idFound===false) {
            return ['code'=>406, 'message' => "Le projet n'est pas présent dans la liste de projets de l'utilisateur."];
        }
        return ['code'=>200];
    }

    #[Route('/suivi/set', name: 'suivi_set', methods: ['GET'])]
    public function setSession(Request $request)
    {
        $mavenKey = $request->get('mavenKey');
        // Stocker des données dans la session via l'objet Request
        $session = $request->getSession();
        $session->set('mavenKey', $mavenKey);
        // Rediriger vers la route sans les paramètres dans l'URL
        return $this->redirectToRoute('suivi');
    }

    /**
     * [Description for suivi]
     * On remonte les 10 dernières version + la version initiale
     *
     * @param Request $request
     * @param Security $security
     *
     * @return response
     *
     * Created at: 15/12/2022, 22:34:25 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/suivi', name: 'suivi', methods: ['GET'])]
    public function suivi(Request $request): Response
    {
        $session = $request->getSession();

        // Instanciation des repositories
        $historiqueRepository = $this->em->getRepository(Historique::class);

        // Initialisation des variables
        $mavenKey = $session->get('mavenKey');
        $teams = $this->security->getUser()->getEquipe();
        $render = $this->getDefaultRender($mavenKey);
        $debug='';

        // Vérifications initiales
        if (empty($mavenKey)) {
            return $this->addFlashAndRender('alert', static::$erreur400, $debug, $render);
        }

        if (empty($teams)) {
            return $this->addFlashAndRender('warning', static::$erreur404, $debug, $render);
        }

        // Vérification du projet
        $listeProjet = self::listeProjet($mavenKey, $teams);
        if ($listeProjet['code'] === 406) {
            return $this->addFlashAndRender('warning', $listeProjet['message'], $debug, $render);
        }

        // Vérification dans l'historique
        $map = ['maven_key' => $mavenKey];
        $liste = $historiqueRepository->countHistoriqueProjet($map);
        if ($liste['code'] != 200 || $liste['nombre'] === 0) {
            return $this->addFlashAndRender('warning', "Le projet n'a pas été sauvegardé dans l'historique.", $debug, $render);
        }

        // Construction du tableau des données pour les requêtes
        $map['limit'] = $this->getParameter('nombre.favori');

        try {
            // Récupération des données
            $suivi = $this->fetchData($historiqueRepository, 'selectUnionHistoriqueProjet', $map);
            $severity = $this->fetchData($historiqueRepository, 'selectUnionHistoriqueAnomalie', $map);
            $details = $this->fetchData($historiqueRepository, 'selectUnionHistoriqueDetails', $map);
            $graph = $this->fetchData($historiqueRepository, 'selectHistoriqueAnomalieGraphique', $map);

            // Traitement des données graphiques
            $graphData = $this->processGraphData($graph['request']);

            // Mise à jour du rendu
            $render = array_merge($render, [
                'suivi' => $suivi['request'],
                'severity' => $severity['request'],
                'details' => $details['request'],
                'nom' => $suivi['request'][0]["nom"],
                'data1' => json_encode($graphData['bug']),
                'data2' => json_encode($graphData['sec']),
                'data3' => json_encode($graphData['codeSmell']),
                'labels' => json_encode($graphData['date'])
            ]);

            $this->addFlash('notice', ['type' => 'success', 'reference' => static::$reference, 'message' => "Les données ont été correctement récupérées."]);
            return $this->render(static::$route, $render);
        } catch (FetchDataException $e) {
            return $this->addFlashAndRender('alert', $e->getMessage(), $e->getDebug(), $e->getRender());
        }
    }

    /**
     * [Description for getDefaultRender]
     *
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 17/07/2024 08:58:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getDefaultRender($mavenKey): array
    {
        return [
            'suivi' => [], 'severity' => [], 'details' => [],
            'nom' => 'N.C', 'mavenKey' => $mavenKey ?? '',
            'data1' => 0, 'data2' => 0, 'data3' => 0, 'labels' => 0,
            'marque_entreprise_short' => $this->getParameter('marque.entreprise.short'),
            'marque_entreprise_long' => $this->getParameter('marque.entreprise.long'),
            'logo_entreprise' => $this->getParameter('logo.entreprise'),
            'env' => $this->getParameter('environnement'),
            'version' => $this->getParameter('version'), 'dateCopyright' => date('Y'),
            Response::HTTP_OK
        ];
    }

    /**
     * [Description for addFlashAndRender]
     *
     * @param string $type
     * @param string $message
     * @param string debug
     * @param array $render
     *
     * @return Response
     *
     * Created at: 17/07/2024 08:58:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function addFlashAndRender(string $type, string $message, string $debug, array $render): Response
    {
        $this->addFlash('notice', ['type' => $type, 'reference' => static::$reference, 'message' => $message, 'debug'=>$debug] );
        return $this->render(static::$route, $render);
    }

    /**
     * [Description for fetchData]
     *
     * @param mixed $repository
     * @param string $method
     * @param array $map
     *
     * @return array
     *
     * Created at: 17/07/2024 08:58:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function fetchData($repository, string $method, array $map)
    {
        $data = $repository->$method($map);
        if ($data['code'] != 200) {
            $message = static::$erreur . $data['code'] . ').';
            $debug = $method.'--->'.$data['erreur'];
            throw new FetchDataException($message, $debug, $this->getDefaultRender($map['maven_key']));
        }
        return $data;
    }

    /**
     * [Description for processGraphData]
     *
     * @param array $graphRequest
     *
     * @return array
     *
     * Created at: 17/07/2024 08:58:34 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function processGraphData(array $graphRequest): array
    {
        $nl = count($graphRequest);
        $bug = $sec = $codeSmell = $date = [];

        for ($i = 0; $i < $nl; $i++) {
            $bug[$i] = $graphRequest[$i]["bug"];
            $sec[$i] = $graphRequest[$i]["sec"];
            $codeSmell[$i] = $graphRequest[$i]["code_smell"];
            $date[$i] = $graphRequest[$i]["date"];
        }

        // Ajout d'une valeur null à la fin de chaque série
        $bug[$nl] = $sec[$nl] = $codeSmell[$nl] = 0;
        $dd = new \DateTime($graphRequest[$nl - 1]["date"]);
        $dd->modify('+1 day');
        $date[$nl] = $dd->format('Y-m-d');

        return ['bug' => $bug, 'sec' => $sec, 'codeSmell' => $codeSmell, 'date' => $date];
    }

}
