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

namespace App\Controller\Suivi;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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
    public static $page= "suivi/index.html.twig";
    public static $reference = "[Suivi]";
    public static $erreur = "Une erreur s'est produite (erreur ";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur404 = "Vous devez être rattaché à une équipe (Erreur 404).";
    public static $erreur406 = "Je n'ai pas trouvé de projets pour ton équipe. ".
    "Vérifies le nom du tag utilisé dans SonarQube (Erreur 406).";

    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

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
        Security $security,
        private ParameterBagInterface $params
    ) {
        $this->em = $em;
        $this->security = $security;
        $this->logoEntreprise = $params->get('logo.entreprise');
        $this->marqueEntrepriseShort = $params->get('marque.entreprise.short');
        $this->marqueEntrepriseLong = $params->get('marque.entreprise.long');
        $this->environnement = $params->get('environnement');
        $this->version = $params->get('version');
        $this->dateCopyright = \date('Y');
    }

    /**
     * [Description for genericRender]
     *
     * @return array
     *
     * Created at: 30/10/2024 08:21:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function genericRender(): array
    {
        return [
            'type_footer' => null,
            'logo_entreprise' => $this->logoEntreprise,
            'marque_entreprise_short' => $this->marqueEntrepriseShort,
            'marque_entreprise_long' => $this->marqueEntrepriseLong,
            'env' => $this->environnement,
            'version' => $this->version,
            'date_copyright' => $this->dateCopyright];
    }

    /**
     * [Description for listeProjet]
     *
     * @param $maven_key array
     * @param $teams array
     *
     * @return array
     *
     * Created at: 16/07/2024 20:05:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function listeProjet($maven_key, $teams): array
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
        $map=['clause_where' => $inTrim];
        $requestListe = $listeProjetRepository->selectListeProjetByEquipe($map);
        if ($requestListe['code'] != 200) {
            return ['code' => $requestListe['code']];
        }

        $projets = $requestListe['liste'];

        /** j'ai pas trouvé de projet pour cette équipe. */
        if (empty($projets)) {
            return ['code' => 406, 'message' => static::$erreur406];
        }

        $searchId = $maven_key;
        $idFound = false;

        foreach ($projets as $item) {
            if (isset($item['id']) && $item['id'] === $searchId) {
                $idFound = true;
                break;
            }
        }
        if ($idFound === false) {
            return ['code' => 406, 'message' => "Le projet n'est pas présent dans la liste de projets de l'utilisateur."];
        }
        return ['code' => 200];
    }

    /**
     * [Description for setSession]
     *
     * @param Request $request
     *
     * @return [type]
     *
     * Created at: 12/11/2024 17:39:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/suivi/set', name: 'suivi_set', methods: ['GET'])]
    public function setSession(Request $request)
    {
        $maven_key = $request->get('maven_key');
        // Stocker des données dans la session via l'objet Request
        $session = $request->getSession();
        $session->set('maven_key', $maven_key);
        // Rediriger vers la route sans les paramètres dans l'URL
        return $this->redirectToRoute('suivi');
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
        $this->addFlash('notice', ['type' => $type, 'reference' => static::$reference,
        'message' => $message, 'debug' => $debug] );
        return $this->render(static::$page, $render);
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
            $bug[$i] = $graphRequest[$i]['bug'];
            $sec[$i] = $graphRequest[$i]['sec'];
            $codeSmell[$i] = $graphRequest[$i]['code_smell'];
            $date[$i] = $graphRequest[$i]['date'];
        }

        /** On ajoute une marge plus importante dans chartjs à la place */

        // Ajout d'une valeur avant la première entrée (on utilise une date un jour avant la première)
        //$firstDate = new \DateTime($graphRequest[0]['date']);
        //$firstDate->modify('-1 day'); // Recule d'un jour
        //array_unshift($bug, 0);  // Ajoute 0 au début de la série "bug"
        //array_unshift($sec, 0);   // Ajoute 0 au début de la série "sec"
        //array_unshift($codeSmell, 0);  // Ajoute 0 au début de la série "codeSmell"
        //array_unshift($date, $firstDate->format('Y-m-d'));  // Ajoute la date modifiée

        // Ajout d'une valeur après la dernière entrée (on utilise une date un jour après la dernière)
        //$lastDate = new \DateTime($graphRequest[$nl - 1]['date']);
        //$lastDate->modify('+1 day');  // Avance d'un jour
        //$bug[$nl] = $sec[$nl] = $codeSmell[$nl] = 0;  // Valeur 0 pour le dernier point
        //$date[$nl] = $lastDate->format('Y-m-d');  // Ajoute la date modifiée

        return ['bug' => $bug, 'sec' => $sec, 'codeSmell' => $codeSmell, 'date' => $date];
    }

    /**
     * [Description for suivi]
     * On remonte les 10 dernières version + la version initiale
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 22:34:25 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/suivi', name: 'suivi', methods: ['GET'])]
    public function suivi(Request $request): response
    {
        $session = $request->getSession();

        // Instanciation des repositories
        $historiqueRepository = $this->em->getRepository(Historique::class);

        // Initialisation des variables
        $maven_key = $session->get('maven_key');
        $teams = $this->security->getUser()->getEquipe();
        $debug='';

        /** On charge le template du render */
        $render=static::genericRender();
        $render['suivi'] = $render['mesure'] = $render['severity'] = $render['details'] = [];
        $render['nom'] = 'N.C';
        $render['mavenKey'] = $maven_key ?? '';
        $render['data1'] = $render['data2'] = $render['data3'] = $render['labels'] = 0;

        // Vérifications initiales
        if (empty($maven_key)) {
            return $this->addFlashAndRender('alert', static::$erreur400, $debug, $render);
        }

        if (empty($teams)) {
            return $this->addFlashAndRender('warning', static::$erreur404, $debug, $render);
        }

        // Vérification du projet
        $listeProjet = self::listeProjet($maven_key, $teams);
        if ($listeProjet['code'] === 406) {
            return $this->addFlashAndRender('warning', $listeProjet['message'], $debug, $render);
        }

        // Vérification dans l'historique
        $map = ['maven_key' => $maven_key];
        $liste = $historiqueRepository->countHistoriqueProjet($map);
        if ($liste['code'] != 200 || $liste['nombre'] === 0) {
            return $this->addFlashAndRender('warning', "Le projet n'a pas été sauvegardé dans l'historique.", $debug, $render);
        }

        // Construction du tableau des données pour les requêtes
        $map['limit'] = $this->getParameter('nombre.favori');

        try {
            // Récupération des données
            $suivi = $this->fetchData($historiqueRepository, 'selectUnionHistoriqueProjet', $map);
            $mesure = $this->fetchData($historiqueRepository, 'selectUnionHistoriqueMesure', $map);
            $severity = $this->fetchData($historiqueRepository, 'selectUnionHistoriqueAnomalie', $map);
            $details = $this->fetchData($historiqueRepository, 'selectUnionHistoriqueDetails', $map);
            $graph = $this->fetchData($historiqueRepository, 'selectHistoriqueAnomalieGraphique', $map);

            // Traitement des données graphiques
            $graphData = $this->processGraphData($graph['request']);

            // Mise à jour du rendu
            $render['suivi'] = $suivi['request'];
            $render['mesure'] = $mesure['request'];
            $render['severity'] = $severity['request'];
            $render['details'] = $details['request'];
            $render['nom'] = $suivi['request'][0]['nom'];
            $render['data1'] = json_encode($graphData['bug']);
            $render['data2'] = json_encode($graphData['sec']);
            $render['data3'] = json_encode($graphData['codeSmell']);
            $render['labels'] = json_encode($graphData['date']);

            $this->addFlash('notice', ['type' => 'success', 'reference' => static::$reference, 'message' => "Les données ont été correctement récupérées."]);

            return $this->render(static::$page, $render);
        } catch (FetchDataException $e) {
            return $this->addFlashAndRender('alert', $e->getMessage(), $e->getDebug(), $e->getRender());
        }
    }

}
