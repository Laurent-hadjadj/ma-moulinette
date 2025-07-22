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

namespace App\Controller\Accueil;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\ListeProjet;
use App\Entity\Profiles;
use App\Entity\Properties;
use App\Entity\Historique;
use App\Entity\MaMoulinette;
use App\Service\Client;
use App\Service\UrlBuilderService;

/**
 * [Description AccueilController]
 */
class AccueilController extends AbstractController
{
    private static $page = 'accueil/index.html.twig';
    private static $sonarUrl = 'sonar.url';
    private static $oups = 'OUPS !!! ';
    private static $europeParis = 'Europe/Paris';
    private static $erreur403 = 'Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).';

    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 22:06:26 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private Client $client,
        private ParameterBagInterface $params,
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger
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

    /************ getter méthode private  ****************/
    public function getCountProjetBD(): int {
        return static::countProjetBD();
    }

    public function getCountProjetSonar(): int {
        return static::countProjetSonar();
    }

    public function getCountProfilBD(): int {
        return static::countProfilBD();
    }

    public function getCountProfilSonar(): int {
        return static::countProfilSonar();
    }

    public function getGetProperties(): array {
        return static::getProperties();
    }

    public function getGetVersion(): string {
        return static::getVersion();
    }

    /****************** FIN *********************** */

    /**
     * [Description for countProjetBD]
     * Récupère le nombre de projet enregistré en base
     *
     * @return int
     *
     * Created at: 15/12/2022, 22:06:59 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function countProjetBD(): int
    {
        $this->logger->info('[Accueil] Récupération du nombre de projets en base.');
        /** On instancie l'entityRepository */
        $listeProjetRepository = $this->em->getRepository(ListeProjet::class);

        /* On récupère le nombre de projet depuis la table liste_projet */
        $nombre = $listeProjetRepository->countListeProjet();

        if ($nombre['code'] !== 200 || empty($nombre['request'])) {
            $this->logger->warning('[Accueil] Aucune donnée projet trouvée en base.', $nombre);
            return 0;
        }
        return $nombre['request'][0]['total'];
    }

    /**
     * [Description for countProjetSonar]
     * Récupère le nombre de projet disponible sur le serveur SonarQube
     *
     * @return int
     *
     * Created at: 15/12/2022, 22:07:31 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function countProjetSonar(): int
    {
        /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/components/search',
            ['qualifiers' => 'TRK', 'p' => 1, 'ps' => 500]
        );

        $this->logger->info('[Accueil] Appel à SonarQube pour le comptage de projets.', ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

        /* On affiche un message flash pour un timeout ou si le serveur n'est pas démarré. */
        if ($result['code'] !== 200) {
            $this->logger->error("[Accueil] Erreur lors de l'appel à SonarQube", $result);
            $this->addFlash('notice',
                [
                    'type' => 'alert',
                    'titre' => static::$oups,
                    'message' => $result['erreur']
                ]);
            // On envoi -1 si on a une erreur HTTPClient
            return -1;
        }

        /**
         * On compte le nombre de projet si la table n'est pas vide.
         */
        $count = 0;
        if (isset($result['json']['components'])) {
            foreach ($result['json']['components'] as $component) {
                if (!str_contains($component['project'], '-SVN')) {
                    $count++;
                }
            }
        }

        $this->logger->debug('[Accueil] Nombre de projets valides (hors SVN) trouvés sur SonarQube.', ['total' => $count]);
        return $count;
    }

    /**
     * [Description for countProfilBD]
     * Récupère le nombre de profil enregistré en base
     *
     * @return int
     *
     * Created at: 15/12/2022, 22:07:46 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function countProfilBD(): int
    {
        $this->logger->info('[Accueil] Récupération du nombre de profils en base.');
        /** On instancie l'entityRepository */
        $profilesRepository = $this->em->getRepository(Profiles::class);

        $nombre = $profilesRepository->countProfiles();
        if (empty($nombre['request'])) {
            $this->logger->warning('[Accueil] Aucune donnée profil trouvée en base.', $nombre);
            return 0;
        }
        return $nombre['request'][0]['total'];
    }

    /**
     * [Description for countProfilSonar]
     * Récupère le nombre de profil disponible sur SonarQube
     *
     * @return int
     *
     * Created at: 15/12/2022, 22:07:58 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function countProfilSonar(): int
    {
        /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/qualityprofiles/search',
            ['defaults' => 'true', 'p' => 1, 'ps' => 500]
        );

        $this->logger->info('[Accueil] Appel à SonarQube pour le comptage des profils.', ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

        if ($result['code'] !== 200) {
            $this->addFlash('notice', [
                'type' => 'alert',
                'titre' => static::$oups,
                'message'=> $result['erreur']
                ]);
            return -1;
        }

        /** Si les profils custom n'existent pas on envoi 0 */
        $count = 0;
        if (isset($result['json']['profiles'])) {
            $count = count($result['json']['profiles']);
        }

        $this->logger->debug('[Accueil] Nombre de profils trouvés sur SonarQube.', ['total' => $count]);
        return $count;
    }

    /**
     * [Description for majProperties]
     * On met à jour la table de référence
     *
     * @param string $type
     * @param int $bd
     * @param int $sonar
     *
     * @return void
     *
     * Created at: 15/12/2022, 22:08:18 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function majProperties(string $type, int $bd, int $sonar)
    {
        $this->logger->info('[Accueil] Mise à jour des propriétés.', ['type' => $type, 'bd' => $bd, 'sonar' => $sonar]);
        /** On instancie l'entityRepository */
        $propertiesRepos = $this->em->getRepository(Properties::class);

        /** On met à jour la date de modification */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        $map = [
                'projet_bd' => $bd, 'projet_sonar' => $sonar,
                'profil_bd' => $bd, 'profil_sonar' => $sonar,
                'date_modification_projet' => $date,
                'date_modification_profil' => $date
            ];

        if ($type === 'projet') {
            $propertiesRepos->updatePropertiesProjet($map);
        } else {
            $propertiesRepos->updatePropertiesProfiles($map);
        }
    }

    /**
     * [Description for getProperties]
     * Récupère les properties
     *
     * @return array
     *
     * Created at: 15/12/2022, 22:08:36 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getProperties(): array
    {
        $this->logger->info('[Accueil] Récupération des propriétés projets/profils.');
        $propertiesRepos = $this->em->getRepository(Properties::class);

        /** On récupère le nombre de projet et de profil */
        $getProperties = $propertiesRepos->getProperties('properties');

        /** La table est vide. On initialise les valeurs */
        if (!$getProperties['request']) {
            $this->logger->warning('[Accueil] Table properties vide, initialisation.');

            $date = new \DateTime('now', new \DateTimeZone(static::$europeParis));
            $map = [
                'projet_bd' => 0, 'projet_sonar' => 0,
                'profil_bd' => 0, 'profil_sonar' => 0,
                'date_creation'=> $date,
                'date_modification_projet' => $date,
                'date_modification_profil' => $date
            ];

            $propertiesRepos->insertProperties($map);
            return $map;
        }

        $row = $getProperties['request'][0];
        $this->logger->debug('[Accueil] Propriétés actuelles chargées.', $row);

        return [
            'projet_bd' => $row['projet_bd'],
            'projet_sonar' => $row['projet_sonar'],
            'date_modification_projet' => $row['date_modification_projet'],
            'profil_bd' => $row['profil_bd'],
            'profil_sonar' => $row['profil_sonar'],
            'date_modification_profil' => $row['date_modification_profil']
        ];
    }

    /**
     * [Description for getVersion]
     * On récupère le numéro de version en base
     *
     * @return string
     *
     * Created at: 15/12/2022, 22:09:07 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getVersion(): string
    {
        $this->logger->info('[Accueil] Récupération de la version de ma-moulinette.');
        $repo = $this->em->getRepository(MaMoulinette::class);
        $data = $repo->getMaMoulinetteVersion();

        $version = $data['request'][0]['version'] ?? '0.0.0';
        $this->logger->debug('[Accueil] Version détectée : '.$version);
        return $version;
    }

    /**
     * [Description for getListeFavoriProjet]
     * Récupère la liste des projets favoris.
     *
     * @param Security $security
     *
     * @return JsonResponse
     *
     * Created at: 14/06/2023, 06:35:37 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/accueil/favori/liste/projet', name: 'accueil_favori_liste_projet')]
    private function getListeFavoriProjet(Security $security): JsonResponse
    {
        /** On instancie l'entityRepository */
        $historiqueRepos = $this->em->getRepository(Historique::class);

        /** On récupère le nombre de favori que l'on souhaite afficher au max (10) */
        $nombreProjetFavori = $this->params->get('nombre.favori');

        /** On récupère l'objet User du contexte de sécurité */
        $user = $security->getUser();
        if (!$user) {
            $this->logger->warning('[Accueil] Utilisateur non connecté pour récupération des favoris projets.');
            return new JsonResponse(['code' => 403, 'message' => static::$erreur403], Response::HTTP_OK);
        }
        $preference = $user->getPreference();

        $statutFavoriProjet = $preference['statut']['favori_projet'] ?? [];
        $listeFavoriProjet = $preference['favori_projet'] ?? [];
        $this->logger->info('[Accueil] Chargement des favoris projets.', [
            'statut' => $statutFavoriProjet,
            'nb_favoris' => count($listeFavoriProjet)
        ]);

        $liste = '';
        $listeProjet = '';

        /** Si la liste de favoris pour les projets est activée et que le nombre de projet > 0  */
        if ($statutFavoriProjet === true && count($listeFavoriProjet) > 0) {
            foreach ($listeFavoriProjet as $value) {
                $liste .=  "'{$value}', ";
            }
            /* on prépare les données pour la requête */
            $map = [
                'liste_projet' => rtrim($liste, " , "), 'nombre_projet_favori' => $nombreProjetFavori];
            $listeProjet = $historiqueRepos->selectHistoriqueProjetFavori($map);
        } else {
            $map = [];
        }

        return new JsonResponse([
            'code' => $listeProjet['code'] ?? 200,
            'statut' => $statutFavoriProjet,
            'liste_favori' => $listeProjet,
            'nombre_projet' => count($listeFavoriProjet)
        ], Response::HTTP_OK);
    }

    /**
     * [Description for construitMaRequest]
     *
     * @param array $liste
     * @param array $maven_key
     * @param integer $index
     *
     * @return string
     *
     * Created at: 14/06/2023, 16:06:05 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function construitMaRequest(array $liste, array $maven_key, int $index): string
    {
        //maven_key='fr.ma-petite-entreprise:ma-moulinette' AND (version = '1.2.0-RELEASE' OR version = '1.2.1-RELEASE')
        $m = $maven_key[0];
        $l = "";
        $maven_Key = "maven_key='".$m."'";
        $version = "";

        $versions = array_values($liste[$index]);
        for ($v = 0; $v < count($versions[0]); $v++) {
            $version = $version." version='".$versions[0][$v]."' OR ";
        }
        $l = $l.' '.$maven_Key.' AND ('.$version;

        /** On supprime le dernier OR */
        $rtrimOr = rtrim($l, " OR ");
        return $rtrimOr.')';
    }


    /**
     * [Description for getListeFavoriVersion]
     * Récupération de la liste des projets par version (limité à 4).
     *
     * @param Security $security
     *
     * @return JsonResponse
     *
     * Created at: 14/06/2023, 07:09:32 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/accueil/favori/liste/version', name: 'accueil_favori_liste_version')]
    private function getListeFavoriVersion(Security $security): JsonResponse
    {
        /** On instancie l'entityRepository */
        $historiqueRepos = $this->em->getRepository(Historique::class);

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $security->getUser()->getPreference();
        $statutFavoriVersion = $preference['statut']['favori_version'];
        $listeFavoriVersion = $preference['favori_version'];

        $liste = [];
        /** Si la liste de favoris pour les versions est activée et que le nombre de projet > 0  */
        if ($statutFavoriVersion === true && count($listeFavoriVersion) > 0){
            /** Nombre de projets  */
            $keys = array_values($listeFavoriVersion);
            /** pour chaque projet */
            for ($i = 0; $i < count($keys); $i++) {
                $where = static::construitMaRequest($keys, array_keys($keys[$i]), $i);
                $favori = $historiqueRepos->getProjetFavori($where);
                array_push($liste, $favori);
            }
        }

        $data = [
                    'code' => $liste['code'] ?? 200,
                    'statut' => $statutFavoriVersion,
                    'liste_version' => $liste,
                    'nombre_projet' => count($listeFavoriVersion)
                ];
        return new JsonResponse($data, Response::HTTP_OK);
    }

    /**
     * [Description for index]
     * Affiche ma page d'accueil
     *
     * @param Request $request
     * @param Security $security
     *
     * Created at: 15/12/2022, 22:09:19 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/accueil', name: 'accueil', methods:'GET')]
    public function index(Security $security, Request $request): Response
    {
                /**
         * Description du processus :
         * 1 - On regarde si la base locale a déjà été mise à jour, i.e.
         *     si la base locale a été mise à jour dans la journée alors les propriétés
         *     sont à jour. Alors on renvoi les valeurs de la tables properties, sinon
         * 2 - On récupère le nombre de projet et le nombre de profil disponible sur le serveur
         *     SonarQube. Si les valeurs sont identiques, on renvoi les valeurs récupérés et on
         *     met à jour la date de la table de propriétés. Sinon,
         * 3 - On met à jour la table liste_projet et/ou profiles et on met à jour la table
         *     properties.
         */
        $this->logger->info('[Accueil] Chargement de la page d\'accueil');
        $listeProjetRepo = $this->em->getRepository(ListeProjet::class);

        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        /** On récupère les properties des projets et profils */
        $properties = $this->getProperties();

        /** On initialise le tableau */
        $render = $this->genericRender();
        $render += array_fill_keys([
            'refresh_bd', 'projet_bd', 'projet_sonar', 'profil_bd', 'profil_sonar',
            'composant', 'nombre_projet_favori', 'favori', 'public', 'private',
            'nombre_projet_local', 'nombre_tag'
        ], 0);

        /** 1 - Les Dates  */
        $dateModProjet = $properties['date_modification_projet'];
        $dateModProfil = $properties['date_modification_profil'];

        $dateVerificationProjet = false;
        $dateVerificationProfil = false;

        /** ----- Projet ------ */
        /** Si la base n'a pas été mise à jour, on récupère le nombre de projet */
        if (date_diff($dateModProjet, $date)->format('%a') >=
        $this->getParameter('maj.projet')) {
            $this->logger->info('[Accueil] Vérification projet requise.');
            /** On récupère le nombre de projet depuis le serveur sonar */
            $projetSonar = static::getCountProjetSonar();
            if ($projetSonar === -1){
                return $this->render(static::$page, $render);
            }
            /** On récupère le nombre de projet en base */
            $projetBd = static::getCountProjetBD();
            $dateVerificationProjet = true;
        } else {
            /** Sinon, on récupère les valeurs de la table de properties */
            $projetBd = $properties['projet_bd'];
            $projetSonar = $properties['projet_sonar'];
        }

        /** ---- Profil ----- */
        /**
         * Si la base n'a pas été mise à jour, on récupère le nombre de projet
         */
        if (date_diff($dateModProfil, $date)->format('%a') >=
        $this->getParameter('maj.profil')) {
            $this->logger->info('[Accueil] Vérification profil requise.');
            /** On récupère le nombre de profil en base. */
            $profilBd = static::getCountProfilBD();

            /** On récupère le nombre de projet depuis le serveur sonar */
            $profilSonar = static::getCountProfilSonar();

            if ($profilSonar === -1){
                return $this->render(static::$page, $render);
            }
            $dateVerificationProfil = true;
        } else {
            /** Sinon, on récupère les valeurs de la table de properties */
            $profilBd = $properties['profil_bd'];
            $profilSonar = $properties['profil_sonar'];
        }

        /** 2 - Projet */
        /** Si le référentiel local est différent de celui sur le serveur. */
        if ($dateVerificationProjet && $projetSonar !== $projetBd) {
            /**
             * Si le nombre de projetSonar est différent de projetBD alors on envoi un message à l'utilisateur pour qu'il mette à jour.
             */
            $this->addFlash('info',
                ['type' => 'primary',
                'titre'=>'[Accueil] ',
                'message'=>'Vous devez mettre à jour le référentiel local pour les ',
                'ref'=>'PROJETS']);
        } elseif ($dateVerificationProjet && $projetSonar === $projetBd && $projetSonar !== $properties['projet_sonar']) {
        /**
         * Si le référentiel sonar est égale de celui sur le serveur et que la table de properties n'est pas à jour, on met à jour la table.
         */
            $this->majProperties('projet', $projetBd, $projetSonar);
        }

        /** 3 - PROFIL  */
        /** Si les properties ne sont pas à jour. */
        if ($dateVerificationProfil && $profilSonar !== $profilBd) {
            /**
             * Si le nombre de projetSonar est différent de projetBD alors on envoi un message à l'utilisateur pour qu'il mette à jour.
             */
            $this->addFlash('info',
                ['type'=>'primary',
                'titre' => '[Accueil] ',
                'message'=> 'Vous devez mettre à jour le référentiel local pour les ',
                'ref'=>'PROFILS']);
        } elseif ($dateVerificationProfil && $profilSonar === $profilBd && $profilSonar !== $properties['profil_sonar']){
        /**
         * Si le référentiel sonar est égale de celui sur le serveur et que la table de properties n'est pas à jour, on met à jour la table.
        */
            $this->majProperties('profil', $profilBd, $profilSonar);
        }


        /** 4 - Visibility   */
        $public = $listeProjetRepo->countListeProjetVisibility('public')['request'][0]['visibility'] ?? 0;
        $private = $listeProjetRepo->countListeProjetVisibility('private')['request'][0]['visibility'] ?? 0;

        /** 5 - Tags  */
        /** Renvoi le nombre de projet et le nombre de tags */
        $tag = $listeProjetRepo->countListeProjetTags();

        /** 6 - VERSION  */
        /** On récupère le numero de version en base et de l'application*/
        $versionBd = self::getGetVersion();
        $versionApp = $this->getParameter('version');

        /** si la dernière version en base est inférieure, on renvoie une alerte ; */
        if ($versionApp !== $versionBd) {
            $this->addFlash('notice',
                [
                    'type' => 'warning',
                    'titre' => 'OUPS !!! ',
                    'message' => "La base de données est en version $versionBd. Vous devez passer le script de migration $versionApp."]);
        }

        /** On va chercher les projets favoris ou les versions des projets */
        $favoriProjet = json_decode($this->getListeFavoriProjet($security)->getContent());
        $favoriVersion = json_decode($this->getListeFavoriVersion($security)->getContent());

        /** On a choisi la liste des projets favori
         *  sinon la liste des versions
         *  sinon rien
        */
        if ($favoriProjet->statut && $favoriProjet->code !== 500) {
            $favori = $favoriProjet->liste_favori;
            $nombreProjet = $favoriProjet->nombre_projet;
            $composant = 'projet';
        } elseif ($favoriVersion->statut && $favoriVersion->code !== 500) {
            $favori = $favoriVersion->liste_version;
            $nombreProjet = $favoriVersion->nombre_projet;
            $composant = 'version';
        } else {
            $nombreProjet = 0;
            $favori = [];
            $composant = 'vide';
        }

        /** On récupère le rôle de l'utilisateur  */
        $refreshBd = $this->isGranted('ROLE_COLLECTE');

        /** On met à jour les données avant d'afficher la page */
        $render['refresh_bd'] = $refreshBd;
        $render['projet_bd'] = $projetBd;
        $render['projet_sonar'] = $projetSonar;
        $render['profil_bd'] = $profilBd;
        $render['profil_sonar'] = $profilSonar;
        $render['composant'] = $composant;
        $render['nombre_projet_favori'] = $nombreProjet;
        $render['favori'] = $favori;
        $render['public'] = $public;
        $render['private'] = $private;
        $render['nombre_projet_local'] = $projetBd;
        $render['nombre_tag'] = $tag['nombre'][0]['tag'];

        $this->logger->info('[Accueil] Rendu final de la page d\'accueil.');
        return $this->render(static::$page, $render);
    }
}
