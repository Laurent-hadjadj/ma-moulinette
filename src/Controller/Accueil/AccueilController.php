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

use App\Controller\Traits\AppUserAware;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Response};
use \Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{ListeProjet, Profiles, Properties, Historique, MaMoulinette};
use App\Service\{ClientService, UrlBuilderService};
use App\Service\UserAgent\UserAgentTrackingFacade;

/**
 * [Description AccueilController]
 */
class AccueilController extends AbstractController
{
    use AppUserAware;

    private static string $page = 'accueil/index.html.twig';
    private static string $sonarUrl = 'sonar.url';
    private static string $europeParis = 'Europe/Paris';
    private static string $noData = 'Pas de données disponibles.';

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 22:06:26 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ClientService $client,
        private ParameterBagInterface $params,
        private UrlBuilderService $urlBuilder,
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

    /************ getter méthode private  ****************/
    public function getCountProjetBD(): int
    {
        return $this->countProjetBD();
    }

    public function getCountProjetSonar(): int
    {
        return $this->countProjetSonar();
    }

    public function getCountProfilBD(): int
    {
        return $this->countProfilBD();
    }

    public function getCountProfilSonar(): int
    {
        return $this->countProfilSonar();
    }

    /** @return array<int|string, mixed> */
    public function getGetProperties(): array
    {
        return $this->getProperties();
    }

    public function getGetVersion(): string
    {
        return $this->getVersion();
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
        $this->logger->info('[Accueil] ℹ️ Récupération du nombre de projets en base.');
        /** On instancie l'entityRepository */
        $listeProjetRepository = $this->em->getRepository(ListeProjet::class);

        /* On récupère le nombre de projet depuis la table liste_projet */
        $nombre = $listeProjetRepository->countListeProjet();

        if ($nombre['code'] !== 200 || empty($nombre['request'])) {
            $this->logger->warning('[Accueil] ⚠️ Aucune donnée projet trouvée en base.', $nombre);
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
            $this->getParameter(self::$sonarUrl),
            '/api/components/search',
            ['qualifiers' => 'TRK', 'p' => 1, 'ps' => 500]
        );

        $this->logger->info('[Accueil] ℹ️ Appel à SonarQube pour le comptage de projets.', ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

        /* On affiche un message flash pour un timeout ou si le serveur n'est pas démarré. */
        if ($result['code'] !== 200) {
            $this->logger->error("[Accueil] ❌ Erreur lors de l'appel à SonarQube", $result);
            $this->addFlash(
                'notice',
                [
                    'type' => 'error',
                    'message' => "❌ {$result['erreur']}"
                ]
            );
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

        $this->logger->debug('[Accueil] 🛠️ Nombre de projets valides (hors SVN) trouvés sur SonarQube.', [
            'total' => $count
        ]);
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
        $this->logger->info('[Accueil] ℹ️ Récupération du nombre de profils en base.');
        /** On instancie l'entityRepository */
        $profilesRepository = $this->em->getRepository(Profiles::class);

        $nombre = $profilesRepository->countProfiles();
        if (empty($nombre['request'])) {
            $this->logger->warning('[Accueil] ⚠️ Aucune donnée profil trouvée en base.', $nombre);
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
            $this->getParameter(self::$sonarUrl),
            '/api/qualityprofiles/search',
            ['defaults' => 'true', 'p' => 1, 'ps' => 500]
        );

        $this->logger->info('[Accueil] ℹ️ Appel à SonarQube pour le comptage des profils.', ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

        if ($result['code'] !== 200) {
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => "❌ {$result['erreur']}"
            ]);
            return -1;
        }

        /** Si les profils custom n'existent pas on envoi 0 */
        $count = 0;
        if (isset($result['json']['profiles'])) {
            $count = count($result['json']['profiles']);
        }

        $this->logger->debug('[Accueil] 🛠️ Nombre de profils trouvés sur SonarQube.', [
            'total' => $count
        ]);
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
        $this->logger->info('[Accueil] ℹ️ Mise à jour des propriétés.', [
            'type' => $type,
            'bd' => $bd,
            'sonar' => $sonar
        ]);
        /** On instancie l'entityRepository */
        $propertiesRepos = $this->em->getRepository(Properties::class);

        /** On met à jour la date de modification */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(self::$europeParis));

        $map = [
            'projet_bd' => $bd,
            'projet_sonar' => $sonar,
            'profil_bd' => $bd,
            'profil_sonar' => $sonar,
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
     * @return array<int|string, mixed>
     *
     * Created at: 15/12/2022, 22:08:36 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getProperties(): array
    {
        $this->logger->info('[Accueil] ℹ️ Récupération des propriétés projets/profils.');
        $propertiesRepos = $this->em->getRepository(Properties::class);

        /** On récupère le nombre de projet et de profil */
        $getProperties = $propertiesRepos->getProperties('properties');

        /** La table est vide. On initialise les valeurs */
        if (!$getProperties['request']) {
            $this->logger->warning('[Accueil] ⚠️ Table properties vide, initialisation.');

            $date = new \DateTime('now', new \DateTimeZone(self::$europeParis));
            $map = [
                'projet_bd' => 0,
                'projet_sonar' => 0,
                'profil_bd' => 0,
                'profil_sonar' => 0,
                'date_creation' => $date,
                'date_modification_projet' => $date,
                'date_modification_profil' => $date
            ];

            $insert = $propertiesRepos->insertProperties($map);
            if ($insert['code'] !== 200) {
                $this->logger->error("[Accueil] ❌ Échec de la requête insertProperties", [
                    'code' => $insert['code'],
                    'erreur' => $insert['erreur'] ?? self::$noData
                ]);

                $this->addFlash(
                    'notice',
                    [
                        'type' => 'error',
                        'message' => "❌ {$insert['erreur']}"
                    ]
                );
                return ['code' => 500];
            }
            return $map;
        }

        $row = $getProperties['request'][0];
        $this->logger->debug('[Accueil] 🛠️ Propriétés actuelles chargées.', $row);

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
        $this->logger->info('[Accueil] ℹ️ Récupération de la version de ma-moulinette.');
        $repo = $this->em->getRepository(MaMoulinette::class);
        $data = $repo->getMaMoulinetteVersion();

        $version = $data['request'][0]['version'] ?? '0.0.0';
        $this->logger->debug("[Accueil] 🛠️ Version détectée : {$version}");
        return $version;
    }

    /**
     * [Description for getSonarQubeVersion]
     *
     * @return int|string
     *
     * Created at: 21/10/2025 10:56:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getSonarQubeVersion(): int|string
    {
        $this->logger->info("[Accueil] ℹ️ Récupération de la version de l'application SonarQube.");

        /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(self::$sonarUrl),
            '/api/server/version',
            []
        );

        $this->logger->info('[Accueil] ℹ️ Appel à SonarQube pour le comptage de projets.', ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

        /* On affiche un message flash pour un timeout ou si le serveur n'est pas démarré. */
        if ($result['code'] !== 200) {
            $this->logger->error("[Accueil] ❌ Erreur lors de l'appel à SonarQube", $result);
            $this->addFlash(
                'notice',
                [
                    'type' => 'error',
                    'message' => "❌ {$result['erreur']}"
                ]
            );
            // On envoi -1 si on a une erreur HTTPClient
            return -1;
        }

        $this->logger->debug('[Accueil] 🛠️ Version du serveur SonarQube.', [
            'version' => $result['json']['texte']
        ]);
        return $result['json']['texte'];
    }

    /**
     * [Description for getListeFavoriProjet]
     * Récupère la liste des projets favoris.
     *
     * @return JsonResponse
     *
     * Created at: 14/06/2023, 06:35:37 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/accueil/favori/liste/projet', name: 'accueil_favori_liste_projet', defaults: ['role' => 'ROLE_GESTIONNAIRE'])]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    private function getListeFavoriProjet(): JsonResponse
    {
        /** On instancie l'entityRepository */
        $historiqueRepos = $this->em->getRepository(Historique::class);

        /** On récupère le nombre de favori que l'on souhaite afficher au max (10) */
        $nombreProjetFavori = $this->params->get('nombre.favori');

        /** On récupère l'objet User du contexte de sécurité */
        $user = $this->appUser();
        $preference = $user->getPreference();

        $statutFavoriProjet = $preference['statut']['favori_projet'] ?? [];
        $listeFavoriProjet = $preference['favori_projet'] ?? [];
        $this->logger->info('[Accueil] ℹ️ Chargement des favoris projets.', [
            'statut' => $statutFavoriProjet,
            'nb_favoris' => count($listeFavoriProjet)
        ]);

        $listeProjet = '';
        $desync = [];

        /** Si la liste de favoris pour les projets est activée et que le nombre de projet > 0  */
        if ($statutFavoriProjet === true && count($listeFavoriProjet) > 0) {
            $map = [
                'liste_projet' => array_values(array_map('strval', $listeFavoriProjet)),
                'nombre_projet_favori' => (int) $nombreProjetFavori,
            ];
            $listeProjet = $historiqueRepos->selectHistoriqueProjetFavori($map);

            /**
             * Désynchronisation : un projet favori des préférences peut ne plus exister
             * dans l'historique (changement de serveur SonarQube, purge SQL). On calcule
             * les clés maven présentes en préférence mais absentes de l'historique.
             */
            $mavenPresents = array_column((array) ($listeProjet['liste'] ?? []), 'mavenkey');
            $desync = array_values(array_diff(
                array_map('strval', $listeFavoriProjet),
                array_map('strval', $mavenPresents)
            ));
        } else {
            $map = [];
        }

        return new JsonResponse([
            'code' => $listeProjet['code'] ?? 200,
            'statut' => $statutFavoriProjet,
            'liste_favori' => $listeProjet,
            'nombre_projet' => count($listeFavoriProjet),
            'desync' => $desync
        ], Response::HTTP_OK);
    }

    /**
     * [Description for getListeFavoriVersion]
     * Récupération de la liste des projets par version (limité à 4).
     *
     *
     * @return JsonResponse
     *
     * Created at: 14/06/2023, 07:09:32 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/accueil/favori/liste/version', name: 'accueil_favori_liste_version')]
    private function getListeFavoriVersion(): JsonResponse
    {
        /** On instancie l'entityRepository */
        $historiqueRepos = $this->em->getRepository(Historique::class);

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $this->appUser()->getPreference();
        $statutFavoriVersion = $preference['statut']['favori_version'];
        $listeFavoriVersion = $preference['favori_version'];

        $liste = [];
        $desync = [];
        /** Si la liste de favoris pour les versions est activée et que le nombre de projet > 0  */
        if ($statutFavoriVersion === true && count($listeFavoriVersion) > 0) {
            /** Structure attendue : [{maven_key: [version1, version2, ...]}, ...] */
            foreach ($listeFavoriVersion as $entry) {
                $mavenKey = (string) array_key_first($entry);
                $versions = array_map('strval', (array) $entry[$mavenKey]);
                $resultat = $historiqueRepos->getProjetFavori($mavenKey, $versions);
                $liste[] = $resultat;
                /** Aucune version d'historique remontée pour ce favori → désynchronisé. */
                if (empty($resultat['version'])) {
                    $desync[] = $mavenKey;
                }
            }
        }

        $data = [
            'code' => 200,
            'statut' => $statutFavoriVersion,
            'liste_version' => $liste,
            'nombre_projet' => count($listeFavoriVersion),
            'desync' => $desync
        ];
        return new JsonResponse($data, Response::HTTP_OK);
    }

    /**
     * [Description for flashDesyncFavori]
     * Signale à l'utilisateur une désynchronisation : un ou plusieurs favoris présents
     * dans ses préférences n'ont plus de correspondance dans l'historique (changement de
     * serveur SonarQube ou purge SQL). On l'oriente vers les deux corrections possibles :
     * relancer une collecte du projet, ou retirer le favori depuis la page Préférences.
     *
     * @param array<int, string> $orphelins Clés maven orphelines.
     *
     * @return void
     *
     * Created at: 14/07/2026 09:59:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function flashDesyncFavori(array $orphelins): void
    {
        if ($orphelins === []) {
            return;
        }

        $this->addFlash('notice', [
            'type' => 'warning',
            'message' => sprintf(
                "⚠️ Favori désynchronisé — absent de l'historique : %s. "
                . "\nRelancez une collecte de ce projet, ou retirez-le depuis la page Préférences.",
                implode(', ', $orphelins)
            ),
        ]);
    }

    /**
     * [Description for index]
     * Affiche ma page d'accueil
     *
     *
     * Created at: 15/12/2022, 22:09:19 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/accueil', name: 'accueil', methods: 'GET')]
    public function index(): Response
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
        $this->tracking->track('ACCUEIL');

        $this->logger->info("[Accueil] ℹ️ Chargement de la page d'accueil");
        $listeProjetRepo = $this->em->getRepository(ListeProjet::class);

        $date = new \DateTimeImmutable('now', new \DateTimeZone(self::$europeParis));

        /** On récupère les properties des projets et profils */
        $properties = $this->getProperties();

        /** On initialise le tableau */
        $render = $this->genericRender();
        $render += array_fill_keys([
            'refresh_bd',
            'projet_bd',
            'projet_sonar',
            'profil_bd',
            'profil_sonar',
            'composant',
            'nombre_projet_favori',
            'favori',
            'public',
            'private',
            'nombre_projet_local',
            'nombre_tag'
        ], 0);

        /**
         * La version du serveur SonarQube est un texte, pas un compteur : on lui
         * donne un défaut « inconnu » explicite (et non 0), écrasé plus bas si
         * l'appel aboutit. Ainsi les retours anticipés (properties en erreur ou
         * SonarQube injoignable) affichent « Inconnu » au lieu d'un « 0 » trompeur.
         */
        $render['version_serveur_sonar'] = 'Version inconnue.';

        /** Le tableau des properties et il y a un code 500 */
        if (!empty($properties) && array_key_exists('code', $properties) && $properties['code'] !== 200) {
            return $this->render(self::$page, $render);
        }

        /** 1 - Les Dates  */
        $dateModificationProjet = $properties['date_modification_projet'];
        $dateModificationProfil = $properties['date_modification_profil'];

        $dateModProjet = (gettype($dateModificationProjet) == 'object') ? $dateModificationProjet : new \DateTime($dateModificationProjet);
        $dateModProfil = (gettype($dateModificationProfil) == 'object') ? $dateModificationProfil : new \DateTime($dateModificationProfil);

        $dateVerificationProjet = false;
        $dateVerificationProfil = false;

        /** ----- Projet ------ */
        /** Si la base n'a pas été mise à jour, on récupère le nombre de projet */
        // $dateModProjet doit être de type DateTimeInterface.
        if (
            date_diff($dateModProjet, $date)->format('%a') >=
            $this->getParameter('maj.projet')
        ) {
            $this->logger->info('[Accueil] ℹ️ Vérification projet requise.');
            /** On récupère le nombre de projet depuis le serveur sonar */
            $projetSonar = $this->getCountProjetSonar();
            if ($projetSonar === -1) {
                return $this->render(self::$page, $render);
            }
            /** On récupère le nombre de projet en base */
            $projetBd = $this->getCountProjetBD();
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
        if (
            date_diff($dateModProfil, $date)->format('%a') >=
            $this->getParameter('maj.profil')
        ) {
            $this->logger->info('[Accueil] ℹ️ Vérification profil requise.');
            /** On récupère le nombre de profil en base. */
            $profilBd = $this->getCountProfilBD();

            /** On récupère le nombre de projet depuis le serveur sonar */
            $profilSonar = $this->getCountProfilSonar();

            if ($profilSonar === -1) {
                return $this->render(self::$page, $render);
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
            $this->addFlash(
                'info',
                [
                    'type' => 'primary',
                    'message' => '📌 Vous devez mettre à jour le référentiel local pour les ',
                    'ref' => 'PROJETS'
                ]
            );
        } elseif ($dateVerificationProjet && $projetSonar === $projetBd && $projetSonar !== $properties['projet_sonar']) {
            /**
             * Si le référentiel sonar est égale de celui sur le serveur et que la table de properties n'est pas à jour, on met à jour la table.
             */
            $this->majProperties('projet', $projetBd, $projetSonar);
            // MODIF 2026-05-26 : else terminal requis par S126 (branche intentionnellement vide).
        } else {
            $this->logger->debug('[Accueil] 🔍 Référentiel PROJET : aucune condition vérifiée, aucune action requise.');
        }

        /** 3 - PROFIL  */
        /** Si les properties ne sont pas à jour. */
        if ($dateVerificationProfil && $profilSonar !== $profilBd) {
            /**
             * Si le nombre de projetSonar est différent de projetBD alors on envoi un message à l'utilisateur pour qu'il mette à jour.
             */
            $this->addFlash(
                'info',
                [
                    'type' => 'primary',
                    'message' => '📌 Vous devez mettre à jour le référentiel local pour les ',
                    'ref' => 'PROFILS'
                ]
            );
        } elseif ($dateVerificationProfil && $profilSonar === $profilBd && $profilSonar !== $properties['profil_sonar']) {
            /**
             * Si le référentiel sonar est égale de celui sur le serveur et que la table de properties n'est pas à jour, on met à jour la table.
             */
            $this->majProperties('profil', $profilBd, $profilSonar);
            // MODIF 2026-05-26 : else terminal requis par S126 (branche intentionnellement vide).
        } else {
            $this->logger->debug('[Accueil] 🔍 Référentiel PROFIL : aucune condition vérifiée, aucune action requise.');
        }

        /** 4 - Visibility   */
        $public = $listeProjetRepo->countListeProjetVisibility('public')['request'][0]['visibility'] ?? 0;
        $private = $listeProjetRepo->countListeProjetVisibility('private')['request'][0]['visibility'] ?? 0;

        /** 5 - Tags  */
        /** Renvoi le nombre de projet et le nombre de tags */
        $tag = $listeProjetRepo->countListeProjetTags();

        /** 6 - VERSION  */
        /** On récupère le numero de version en base et de l'application*/
        $versionBd = $this->getGetVersion();
        $versionApp = $this->getParameter('version');

        /** si la dernière version en base est inférieure, on renvoie une alerte ; */
        if ($versionApp !== $versionBd) {
            $this->addFlash(
                'notice',
                [
                    'type' => 'warning',
                    'message' => "⚠️ La base de données est en version $versionBd. Vous devez passer le script de migration $versionApp."
                ]
            );
        }

        /** On va chercher les projets favoris ou les versions des projets */
        $favoriProjet = json_decode($this->getListeFavoriProjet()->getContent());
        $favoriVersion = json_decode($this->getListeFavoriVersion()->getContent());

        /** On a choisi la liste des projets favori
         *  sinon la liste des versions
         *  sinon rien
         */
        if ($favoriProjet->statut && $favoriProjet->code !== 500) {
            $favori = $favoriProjet->liste_favori;
            $nombreProjet = $favoriProjet->nombre_projet;
            $composant = 'projet';
            $this->flashDesyncFavori((array) ($favoriProjet->desync ?? []));
        } elseif ($favoriVersion->statut && $favoriVersion->code !== 500) {
            $favori = $favoriVersion->liste_version;
            $nombreProjet = $favoriVersion->nombre_projet;
            $composant = 'version';
            $this->flashDesyncFavori((array) ($favoriVersion->desync ?? []));
        } else {
            $nombreProjet = 0;
            $favori = [];
            $composant = 'vide';
        }

        /** On récupère la version du serveur SonarQube */
        $sonar_version = $this->getSonarQubeVersion();

        /** On récupère le rôle de l'utilisateur  */
        $refreshBD = $this->isGranted('ROLE_GESTIONNAIRE');

        /** On met à jour les données avant d'afficher la page */
        $render['refresh_bd'] = $refreshBD;
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
        $render['version_serveur_sonar'] = ($sonar_version != -1) ? $sonar_version : 'Version inconnue.';

        $this->logger->info("[Accueil] ℹ️ Rendu final de la page d'accueil.");
        return $this->render(self::$page, $render);
    }
}
