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

use App\Controller\Traits\AppUserAware;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{Historique, ListeProjet};
use App\Repository\ListeProjetRepository;
use App\Exception\FetchDataException;
use App\Service\UserAgentTrackingFacade;

/**
 * [Description SuiviController]
 */
class SuiviController extends AbstractController
{
    use AppUserAware;

    /** Définition des constantes */
    private static string $page = "suivi/index.html.twig";
    private static string $erreur400 = "❌ La requête est incorrecte (Erreur 400).";
    private static string $erreur404 = "⚠️ Vous devez être rattaché à une équipe (Erreur 404).";
    private static string $erreur406 = "⚠️ Je n'ai pas trouvé de projets pour ton équipe. " .
        "Vérifies le nom du tag utilisé dans SonarQube (Erreur 406).";

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 22:34:06 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        ParameterBagInterface $params,
        private LoggerInterface $logger,
        private UserAgentTrackingFacade $tracking
    ) {
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
     * @return array<int|string, mixed>
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
            'date_copyright' => $this->dateCopyright
        ];
    }

    /**
     * [Description for listeProjet]
     *
     * @param $maven_key array
     * @param $groupes array
     *
     * @return array<int|string, mixed>
     *
     * Created at: 16/07/2024 20:05:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function listeProjet(string $maven_key, array $groupes): array
    {
        /** On instancie l'entityRepository */
        $listeProjetRepository = $this->em->getRepository(ListeProjet::class);

        $tagPrefixes = ListeProjetRepository::normalizeGroupesToTagPrefixes($groupes);
        $requestListe = $listeProjetRepository->selectListeProjetByGroupe($tagPrefixes);
        if ($requestListe['code'] != 200) {
            return ['code' => $requestListe['code']];
        }

        $projets = $requestListe['liste'];

        /** j'ai pas trouvé de projet pour cette équipe. */
        if (empty($projets)) {
            return [
                'code' => 406,
                'message' => self::$erreur406
            ];
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
            $message = "[Suivi] ⚠️ Le projet n'est pas présent dans la liste de projets de l'utilisateur.";
            $this->logger->warning($message);

            return [
                'code' => 406,
                'message' => $message
            ];
        }
        return ['code' => 200];
    }

    /**
     * [Description for setSession]
     *
     * @param Request $request
     *
     *
     * Created at: 12/11/2024 17:39:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/suivi/set', name: 'suivi_set', methods: ['GET'])]
    public function setSession(Request $request): Response
    {
        $maven_key = $request->query->get('maven_key');
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
     * @param string $debug
     * @param array<int|string, mixed> $render
     *
     * @return Response
     *
     * Created at: 17/07/2024 08:58:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function addFlashAndRender(string $type, string $message, string $debug, array $render): Response
    {
        $this->addFlash('notice', [
            'type' => $type,
            'message' => $message,
            'debug' => $debug
        ]);
        return $this->render(self::$page, $render);
    }

    /**
     * [Description for fetchData]
     *
     * @param mixed $repository
     * @param string $method
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 17/07/2024 08:58:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     * @param array<int|string, mixed> $map
     */
    private function fetchData($repository, string $method, array $map)
    {
        $data = $repository->$method($map);
        if ($data['code'] != 200) {
            $this->logger->error("[Suivi] ❌ Une erreur s'est produite.", [
                'method' => $method,
                'erreur' => $data['erreur']
            ]);

            $debug = $method . ' ---> ' . $data['erreur'];
            throw new FetchDataException("❌ Une erreur s'est produite (Erreur {$data['code']}).", $debug, $this->genericRender());
        }
        return $data;
    }

    /**
     * [Description for processGraphData]
     *
     * @param array<int|string, mixed> $graphRequest
     *
     * @return array<int|string, mixed>
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

        return ['bug' => $bug, 'sec' => $sec, 'codeSmell' => $codeSmell, 'date' => $date];
    }

    /**
     * [Description for suivi]
     * On remonte les 10 dernières version + la version initiale
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:34:25 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/suivi', name: 'suivi', methods: ['GET'])]
    public function suivi(Request $request): Response
    {
        $this->tracking->track('SUIVI');

        $session = $request->getSession();

        // Instanciation des repositories
        $historiqueRepos = $this->em->getRepository(Historique::class);

        // Initialisation des variables
        $maven_key = $session->get('maven_key');
        $groupes = $this->appUser()->getListeGroupeFonctionnel();
        $debug = '';

        /** On charge le template du render */
        $render = $this->genericRender();
        $render['suivi'] = $render['mesure'] = $render['severity'] = $render['details'] = [];
        $render['nom'] = 'N.C';
        $render['mavenKey'] = $maven_key ?? '';
        $render['data1'] = $render['data2'] = $render['data3'] = $render['labels'] = 0;

        // Vérifications initiales
        if (empty($maven_key)) {
            $this->logger->error('[Suivi] ❌ ' . self::$erreur400);
            return $this->addFlashAndRender('error', self::$erreur400, $debug, $render);
        }

        if (empty($groupes)) {
            $this->logger->warning('[Suivi] ❌ ' . self::$erreur404);
            return $this->addFlashAndRender('warning', self::$erreur404, $debug, $render);
        }

        // Vérification du projet
        $listeProjet = $this->listeProjet($maven_key, $groupes);
        if ($listeProjet['code'] === 406) {
            $this->logger->warning('[Suivi] ⚠️ ' . $listeProjet['message']);
            return $this->addFlashAndRender('warning', $listeProjet['message'], $debug, $render);
        }

        // Vérification dans l'historique
        $map = ['maven_key' => $maven_key];
        $liste = $historiqueRepos->countHistoriqueProjet($map);
        if ($liste['code'] != 200 || $liste['nombre'] === 0) {
            $message = "⚠️ Le projet n'a pas été sauvegardé dans l'historique (Erreur 500).";
            $this->logger->warning('[Suivi] ' . $message);
            return $this->addFlashAndRender('warning', $message, $debug, $render);
        }

        // Construction du tableau des données pour les requêtes
        $map['limit'] = $this->getParameter('nombre.favori');

        try {
            // Récupération des données
            $suivi = $this->fetchData($historiqueRepos, 'selectUnionHistoriqueProjet', $map);
            $mesure = $this->fetchData($historiqueRepos, 'selectUnionHistoriqueMesure', $map);
            $severity = $this->fetchData($historiqueRepos, 'selectUnionHistoriqueAnomalie', $map);
            $details = $this->fetchData($historiqueRepos, 'selectUnionHistoriqueDetails', $map);
            $graph = $this->fetchData($historiqueRepos, 'selectHistoriqueAnomalieGraphique', $map);

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

            $message = "✅ Les données ont été correctement récupérées.";
            $this->logger->info('[Suivi] ' . $message);
            $this->addFlash('notice', [
                'type' => 'success',
                'message' => $message
            ]);

            return $this->render(self::$page, $render);
        } catch (FetchDataException $e) {
            $this->logger->critical(
                "[Suivi] 🔴 une erreur inattendue s'est produite (Erreur 500).",
                [
                    'message' => $e->getMessage(),
                    'debug' => $e->getDebug()
                ]
            );
            return $this->addFlashAndRender('error', $e->getMessage(), $e->getDebug(), $e->getRender());
        }
    }
}
