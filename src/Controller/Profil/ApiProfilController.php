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

namespace App\Controller\Profil;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;


use App\Entity\Properties;
use App\Entity\Profiles;
use App\Entity\ProfilesHistorique;
use App\service\ClientService;
use App\Service\UrlBuilderService;

/**
 * [Description ApiProfilController]
 */
class ApiProfilController extends AbstractController
{
    /** Définition des constantes */
    private static $europeParis = "Europe/Paris";
    private static $dateFormatShort = "Y-m-d";
    private static $sonarUrl = "sonar.url";
    private static $page = "profil/details.html.twig";
    private static $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static $erreur403 = "Vous devez avoir le rôle GESTIONNAIRE pour réaliser cette action (Erreur 403).";
    public static $erreur404 = "Vous devez au moins avoir un profil déclaré sur le serveur SonarQube (Erreur 404).";

    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

    /**
     * [Description for __construct]
     *  EntityManagerInterface = em
     *
     * Created at: 13/02/2023, 08:57:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ClientService $client,
        private Security $security,
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
        private UrlBuilderService $urlBuilder,
    ) {
        $this->params = $params;
        $this->logoEntreprise = $params->get('logo.entreprise');
        $this->marqueEntrepriseShort = $params->get('marque.entreprise.short');
        $this->marqueEntrepriseLong = $params->get('marque.entreprise.long');
        $this->environnement = $params->get('environnement');
        $this->version = $params->get('version');
        $this->dateCopyright = \date('Y');
    }

    /**
     * [Description for getCount]
     *
     * @param mixed $repo
     * @param string $language
     * @param string $action
     *
     * @return int
     *
     * Created at: 19/10/2025 10:49:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getCount($repo, string $language, string $action): int
    {
        $result = $repo->selectProfilesHistoriqueAction([
            'language' => $language,
            'action' => $action
        ]);

        return $result['nombre'][0]['nombre'] ?? 0;
    }

    /**
     * [Description for getDateTri]
     *
     * @param mixed $repo
     * @param string $language
     * @param string $tri
     *
     * @return array|null
     *
     * Created at: 19/10/2025 10:49:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getDateTri($repo, string $language, string $tri): ?array
    {
        $result = $repo->selectProfilesHistoriqueDateTri([
            'language' => $language,
            'tri' => $tri,
            'limit' => 1
        ]);
        return $result['liste'][0] ?? null;
    }

    /**
     * [Description for genericDetailsRender]
     *
     * @return array
     *
     * Created at: 19/10/2025 10:49:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function genericDetailsRender(): array
    {
        return [
            'profil' => 'NC',
            'langage' => 'aucun',
            'opened' => 0,
            'closed' => 0,
            'updated' => 0,
            'total_rule' => null,
            'premier' => null,
            'dernier' => null,
            'date_groupe' => null,
            'nombre_groupe' => null,
            'liste' => null,
            'badge' => null
        ];
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
            'date_copyright' => $this->dateCopyright
        ];
    }

    /**
     * [Description for listeQualityProfiles]
     * Renvoie la liste des profils qualité
     * http://{url}/api/qualityprofiles/search?qualityProfile={name}
     * RÔLE-GESTIONNAIRE
     * @return JsonResponse
     *
     * Created at: 07/05/2023, 21:12:09 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/quality/profiles', name: 'liste_quality_profiles', methods: ['POST'])]
    public function listeQualityProfiles(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/quality/profiles");

        /** On instancie l'entityRepository */
        $profilesRepos = $this->em->getRepository(Profiles::class);
        $propertiesRepos = $this->em->getRepository(Properties::class);

        /** si on est pas GESTIONNAIRE on ne fait rien. */
        if (!$this->security->isGranted('ROLE_GESTIONNAIRE')){
            $this->logger->warning("[Profil] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_GESTIONNAIRE).");

            return new JsonResponse([
                'code' => 403,
                'alert' => 'warning',
                'message' => static::$erreur403
            ], Response::HTTP_OK);
        }

        /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/qualityprofiles/search'
        );

        /** On définit l'URL et on ajoute le nom des profils SonarQube*/
        $this->logger->info('[Profil] ℹ️ Appel à SonarQube pour récupérer les profils qualité',[
            'url' => $url
        ]);

        $result = $this->client->httpSonarQube($url);

        if (in_array($result['code'] ?? -1, [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error('[Profil] ❌ Erreur retour API SonarQube', [
            'code' => $result['code'],
            'erreur' => $result['erreur'] ?? null,
            ]);

            return new JsonResponse([
                'code' => $result['code'],
                'alert' => 'alert',
                'message' => $result['erreur'] ?? 'Erreur SonarQube'
            ], Response::HTTP_OK);
        }

        /** On Vérifie qu'il existe au moins un profil */
        if (empty($result['json']['profiles'])) {
            $this->logger->warning('[Profil] ⚠️ Aucun profil qualité trouvé sur SonarQube.');
            return new JsonResponse([
                'code' => 404,
                'type' => 'warning',
                'message' => static::$erreur404
            ], Response::HTTP_OK);
        }

        /*** Super on a récupéré la liste des profils par langage */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));
        $profilsCount = count($result['json']['profiles']);

        $this->logger->info('[Profil] ℹ️ Nombre de profils qualité SonarQube récupérés', [
            'total' => $profilsCount,
            'date' => $date->format('Y-m-d H:i')]);

        /** On supprime les données de la table avant d'importer les données;*/
        $r1 = $profilesRepos->deleteProfiles();
        if ($r1['code'] !== 200) {
            $this->logger->error('[Profil] ❌ Échec de la requête deleteProfiles.', [
            'code' => $r1['code'],
            'total' => $profilsCount ?? 'inconnu',
            'erreur' => $r1['erreur'] ?? null
        ]);

            $message = "Une erreur s'est produite lors de la suppression des données (Erreur {$r1['code']}).";
            return new JsonResponse([
                'code' => $r1['code'],
                'type' => 'alert',
                'message' => $message,
                'trace' => $r1['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        /** On insert les profils dans la table profiles. */
        $map = [
            'profiles' => $result['json']['profiles'],
            'date_enregistrement' => $date
        ];

        $r2 = $profilesRepos->insertProfiles($map);
        if ($r2['code'] !== 200) {
            $this->logger->error('[Profil] ❌ Échec de la requête insertProfiles.', [
                'code' => $r2['code'],
                'profiles' => $result['json']['profiles'] ?? 'inconnu',
                'erreur' => $r2['erreur'] ?? null
            ]);

            $message = "Une erreur s'est produite lors de l'enregistrement des profils (Erreur {$r2['code']}).";
            return new JsonResponse([
                'code' => $r2['code'],
                'type' => 'alert',
                'message' => $message,
                'trace' => $r2['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        /** On récupère la nouvelle liste des profils */
        $r3 = $profilesRepos->selectProfiles();
        if ($r3['code'] !== 200) {
            $this->logger->error('[Profil] ❌ Échec de la requête selectProfiles.', [
                'code' => $r3['code'],
                'erreur' => $r3['erreur'] ?? null]);

            $message = "Une erreur s'est produite lors de la recherche des informations (Erreur 500).";
            return new JsonResponse([
                'code' => $r3['code'],
                'type' => 'alert',
                'message' => $message,
                'trace' => $r3['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        /** On met à jour la table propriétés */
        $map = [
                'profil_bd' => $r2['nombre'],
                'profil_sonar'=> $r2['nombre'],
                'date_modification_profil' => $date
        ];

        $r4 = $propertiesRepos->updatePropertiesProfiles($map);
        if ($r4['code'] !== 200) {
            $this->logger->error('[Profil] ❌ Échec de la requête updatePropertiesProfiles', [
            'code' => $r4['code'],
            'data' => $map ?? 'inconnu',
            'erreur' => $r4['erreur'] ?? null]);

            $message = "Une erreur s'est produite lors de la mise à jour des données (Erreur 500).";
            return new JsonResponse([
                'code' => $r4['code'],
                'type' => 'alert',
                'message' => $message,
                'trace' => $r4['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Profil] ℹ️ Mise à jour des profils qualité réussie', [
            'total' => count($r3['liste']),
            'date' => $date->format('Y-m-d H:i')
        ]);

        return new JsonResponse([
            'code' => 200,
            'liste_profil' => $r3['liste']
        ], Response::HTTP_OK);
    }

    /**
     * [Description for listeQualityLangage]
     * Revoie le tableau des labels et des dataset
     * Permet de tracer un joli dessin sur la répartition des langages de programmation.
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:24:33 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/quality/langage', name: 'liste_quality_langage', methods: ['POST'])]
    public function listeQualityLangage(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/quality/langage");

        /** On instancie la classe */
        $profilesRepos = $this->em->getRepository(Profiles::class);

        $listeLabel = [];
        $listeDataset = [];

        /** On récupère la liste des langages */
        $r1 = $profilesRepos->selectProfilesLanguage();
        if ($r1['code'] !== 200) {
            $this->logger->error('[Profil] ❌ Échec de la requête selectProfilesLanguage.', [
                'code' => $r1['code'],
                'erreur' => $r1['erreur'] ?? null
            ]);

            $message = "Une erreur s'est produite lors du chargement des données concernant les langages (Erreur {$r1['code']}).";
            return new JsonResponse([
                'code' => $r1['code'],
                'type' => 'alert',
                'message' => $message,
                'trace' => $r1['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        /** On créé la liste des libellés et des données */
        foreach ($r1['labels'] as $label) {
            array_push($listeLabel, $label['profile']);
        }

        $this->logger->info('[Profil] ℹ️ Libellés de langages récupérés', [
            'total' => count($listeLabel),
            'labels' => $listeLabel
        ]);

        /** On récupère le nombre de règle de chaque profil */
        $r2 = $profilesRepos->selectProfilesRuleCount();
        if ($r2['code'] !== 200 || empty($r2['data-set'])) {
            $this->logger->error('[Profil] ❌ Échec de la requête selectProfilesRuleCount.', [
                'code' => $r2['code'],
                'erreur' => $r2['erreur'] ?? null]);

            return new JsonResponse([
                'code' => $r2['code'] ?? 500,
                'type' => 'alert',
                'message' => "Une erreur s'est produite lors de la récupération des données (Erreur {$r2['code']}).",
                'trace' => $r2['erreur'] ?? null,
            ], Response::HTTP_OK);
        }

        foreach ($r2['data-set'] as $dataSet) {
            array_push($listeDataset, $dataSet['total']);
        }

        $this->logger->info('[Profil] ℹ️ Dataset des règles construit', [
            'total' => count($listeDataset),
            'dataset' => $listeDataset]);

        return new JsonResponse([
            'code' => '200',
            'label' => $listeLabel,
            'dataset' => $listeDataset
        ], Response::HTTP_OK);
    }

    /**
     * [Description for profilDetails]
     * Affichage des règles par profils avec les changement.
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 11/03/2023, 23:08:43 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/profil/details', name: 'profil_details', methods: ['GET'])]
    public function profilDetails(Request $request)
    {
        $this->logger->info("[API] 📥 Requête reçue sur /profil/details");

        $profilesHistoriqueRepository = $this->em->getRepository(ProfilesHistorique::class);

        $token = $request->get('token');
        if (empty($token)) {
            $this->logger->warning('[Profil-Détail] ⚠️ Token vide ou manquant');
            return $this->render(static::$page, ['profil' => 'NC']);
        }

        try {
            //b64=bnVsbHxDU1N8RnJhbmNlQWdyaU1lciB2Mi4wLjAgKDIwMjEp
            //rot13=oaIfoUkQH1A8EaWuozAyDJqlnH1ypvO2Zv4jYwNtXQVjZwRc
            $string = str_rot13($token);
            $decode = base64_decode($string);
            $explode = preg_split("/[|]+/", $decode);
        } catch (\Throwable $e) {
            $this->logger->error('[Profil-Détail] ❌ Erreur lors du décodage du token.', ['exception' => $e]);
            return $this->redirectToRoute('profil');
        }

        $render = array_merge(
            $this->genericRender(),        // contient : date_copyright, titre, etc.
            $this->genericDetailsRender()  // contient : profil, langage, opened, closed, etc.
        );

        $render += [
            'profil' => 'NC', 'langage' => 'aucun', 'opened' => 0, 'closed' => 0,
            'updated' => 0, 'total_rule' => null, 'premier' => null, 'dernier' => null,
            'date_groupe' => null, 'nombre_groupe' => null, 'liste' => null, 'badge' => null
        ];

        if (count($explode) !== 3) {
            $this->logger->warning('[Profil-Détail] ⚠️ Token invalide.', [
                'token' => $token
            ]);
            $this->addFlash('notice', [
                'type' => 'alert',
                'message' => "⚠️ Le token fourni est invalide ou mal formé (Erreur 422)."]);
            return $this->render(static::$page, $render);
        }

        [$salt, $language, $profil] = $explode;
        $this->logger->debug("[Profil-Détail] 🛠️ Décomposition du token", [
            'salt' => $salt,
            'language' => $language,
            'profil' => $profil
        ]);

        $language = strtolower($language);

        $sonarLanguage = ['delphi', 'css', 'jsp', 'py', 'js', 'secrets', 'ruby', 'java', 'web', 'xml', 'php', 'json', 'text', 'grvy', 'ts', 'yaml'];
        $maMoulinetteLanguage = ['java properties', 'javascript', 'html', 'typescript', 'python', 'groovy'];

        if (!in_array($language, [...$sonarLanguage, ...$maMoulinetteLanguage])) {
            $this->logger->warning('[Profil-Détail] ⚠️ Langage non supporté.', ['langage' => $language]);

            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => "⚠️ Le langage sélectionné n'est pas supporté (Erreur 404)."
            ]);
            return $this->render(static::$page, $render);
        }

        $language = match ($language) {
            'java properties' => 'jproperties',
            'javascript' => 'js',
            'html' => 'web',
            'typescript' => 'ts',
            'python' => 'py',
            'groovy' => 'grvy',
            default => $language,
        };

        /* On récupère que les 500 premiers */
        $url = $this->urlBuilder->build(
        $this->getParameter(static::$sonarUrl),
        '/api/qualityprofiles/changelog',
        ['language' => $language, 'qualityProfile' => $profil, 'ps' => 500, 'p' => 1]);

        $this->logger->info('[Profil-Détail] ℹ️ Appel API SonarQube', ['url' => $url]);

        $result = $this->client->httpSonarQube($url);
        if (in_array($result['code'] ?? -1, [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error('[Profil-Détail] ❌ Erreur API SonarQube.', [
                'code' => $result['code'],
                'url' => $url,
                'result' => $result
            ]);

            $this->addFlash('notice', [
                'type' => 'alert',
                'message' => '❌' . $result['erreur']
            ]);
            return $this->render(static::$page, $render);
        }

        $date = new \DateTime('now', new \DateTimeZone(static::$europeParis));
        $events = $result['json']['events'] ?? [];
        $total = $result['json']['total'] ?? null;

        /** On met à jour la table contenant l'historique des changements. */
        foreach ($events as $event) {
            try {
                $dc = new \DateTime($event['date'], new \DateTimeZone(static::$europeParis));
                $map = [
                    'date_courte' => $dc->format(static::$dateFormatShort),
                    'language' => $language,
                    'date' => $event['date'],
                    'action' => $event['action'],
                    'auteur' => $event['authorName'] ?? 'Non défini',
                    'rule' => $event['ruleKey'],
                    'description' => $event['ruleName'],
                    'detail' => json_encode($event['params']),
                    'date_enregistrement' => $date
                ];
                $profilesHistoriqueRepository->insertProfilesHistorique($map);
            } catch (\Throwable $e) {
                $this->logger->error("[Profil-Détail] ❌ Insertion de la requête insertProfilesHistorique.", [
                    'map' => $map ?? 'inconnu',
                    'exception' => $e
                ]);
            }
        }

        $this->logger->info('ℹ️ [Profil Détail] Insertion des événements SonarQube réussie', ['nombre_evenements' => count($events)]);

        $render['profil'] = $profil;
        $render['langage'] = $language;
        $render['opened'] = $this->getCount($profilesHistoriqueRepository, $language, 'ACTIVATED');
        $render['closed'] = $this->getCount($profilesHistoriqueRepository, $language, 'DEACTIVATE');
        $render['updated'] = $this->getCount($profilesHistoriqueRepository, $language, 'UPDATED');
        $render['total_rule'] = $total;
        $render['premier'] = $this->getDateTri($profilesHistoriqueRepository, $language, 'ASC');
        $render['dernier'] = $this->getDateTri($profilesHistoriqueRepository, $language, 'DESC');

        $groupes = $profilesHistoriqueRepository->selectProfilesHistoriqueDateCourteGroupeBy(['language' => $language]);
        $render['nombre_groupe'] = count($groupes['liste']);

        $liste = [];
        $dateGroupes = [];
        $badges = [];
        $i = 0;

        foreach ($groupes['liste'] as $groupe) {
            $date = $groupe['date_courte'];
            $modif = $profilesHistoriqueRepository->selectProfilesHistoriqueLangageDateCourte([
                'language' => $language,
                'date_courte' => $date
            ]);

            $dateGroupes[] = $date;

            $groupModifications = [];
            $badgeU = $badgeD = $badgeA = 0;

            foreach ($modif['liste'] as $m) {
                $groupModifications[] = [
                    'groupe' => $i,
                    'date' => $m['date'],
                    'action' => $m['action'],
                    'auteur' => $m['auteur'],
                    'rule' => $m['rule'],
                    'description' => $m['description'],
                    'detail' => $m['detail']
                ];

                match ($m['action']) {
                    'UPDATED' => $badgeU++,
                    'DEACTIVATED' => $badgeD++,
                    'ACTIVATED' => $badgeA++,
                    default => null
                };
            }

            $liste[] = $groupModifications;
            $badges[] = [
                'badgeU' => $badgeU,
                'badgeD' => $badgeD,
                'badgeA' => $badgeA
            ];

            $i++;
        }

        $render['date_groupe'] = $dateGroupes;
        $render['liste'] = $liste;
        $render['badge'] = $badges;

        $this->logger->info('[Profil Détail] ℹ️ Données prêtes à être affichées', [
            'profil' => $profil,
            'langage' => $language
        ]);

        return $this->render(static::$page, $render);
    }

    /**
     * [Description for listeQualityOff]
     * Renvoie la liste des profils qui ne ont pas actif pour un language donné
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/07/2025 09:50:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/quality/off', name: 'liste_quality_off', methods: ['POST'])]
    public function listeQualityOff(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/quality/off");

        $profilesRepos = $this->em->getRepository(Profiles::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

      /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'langage')) {
            $this->logger->error("[Profil] ❌ Requête invalide : 'langage' manquant ou malformé", ['data' => $data]);

            return new JsonResponse([
                    'code' => 400,
                    'type' => 'alert',
                    'message' => static::$erreur400
                ], Response::HTTP_OK);
        }

        /** On récupère le language */
        $langage = $data->langage;
        try {
                $referential_default = 'false';

                $liste = $profilesRepos->selectProfiles($referential_default, $langage);
                $count = $profilesRepos->countProfiles($referential_default, $langage);
                // Log des résultats
                $this->logger->info('[Profil] ℹ️ non actifs récupérés', [
                    'langage' => $langage,
                    'count' => $count['request'][0]['total'] ?? 0
                ]);

                return new JsonResponse([
                    'code' => 200,
                    'listeProfil' => $liste['liste'],
                    'countProfil' => $count,
                    //'autreVersion' => count($liste['liste']) ?? 0
                ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('[Profil] 🔴 Erreur lors de la récupération des profils non actifs', [
                'langage' => $langage,
                'exception' => $e->getMessage()
            ]);

            return new JsonResponse([
                'code' => 500,
                'type' => 'alert',
                'message' => 'Une erreur est survenue lors du traitement des profils',
                'trace' => $e->getMessage()
            ], Response::HTTP_OK);
        }
    }
}
