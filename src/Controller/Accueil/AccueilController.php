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

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/** Sécurité */
use Symfony\Bundle\SecurityBundle\Security;

/** Accès aux tables */
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ListeProjet;
use App\Entity\Profiles;
use App\Entity\Properties;
use App\Entity\Historique;
use App\Entity\MaMoulinette;

/** API */
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description AccueilController]
 */
class AccueilController extends AbstractController
{
    public static $strContentType = 'application/json';
    public static $sonarUrl = "sonar.url";
    public static $oups = "OUPS !!!";
    public static $europeParis = "Europe/Paris";
    public static $reference = "<strong>[Accueil]</strong>";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";

    private $client;
    private $em;

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
        EntityManagerInterface $em,
        Client $client,
        private ParameterBagInterface $params,
    ) {
        $this->em = $em;
        $this->client = $client;
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
        /** On instancie l'entityRepository */
        $listeProjetRepository = $this->em->getRepository(ListeProjet::class);

        /* On récupère le nombre de projet depuis la table liste_projet */
        $nombre = $listeProjetRepository->countListeProjet();

        $projet = 0;
        if ($nombre['code'] === 200){
            $projet = $nombre['request'][0]['total'];
        }
        return $projet;
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
        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);

        /** Appelle le client HTTP */
        $queryParams = ['qualifiers'=>'TRK', 'p'=>1, 'ps'=>500 ];
        $result = $this->client->httpSonarQube("$tempoUrl/api/components/search?".http_build_query($queryParams));
        /* On affiche un message flash pour un timeout ou si le serveur n'est pas démarré. */
        if (in_array($result['code'] ?? -1, [503, 504])) {
                $titre=static::$oups;
                $message = $result['erreur'];
                $this->addFlash('notice', ['type'=>'alert', 'titre'=>$titre, 'message'=>$message]);
                return 0;
            }

        /**
         * On compte le nombre de projet si la table n'est pas vide.
         */
        $nombre = 0;
        if (!array_key_exists('code', $result)){
            foreach ($result['components'] as $component) {
                /**
                 * On exclue les projets archivés avec le suffixe "-SVN".
                 *  "project": "fr.domaine:mon-application-SVN"
                 */
                $mystring = $component["project"];
                $findme   = '-SVN';
                if (!strpos($mystring, $findme)) {
                    $nombre = $nombre + 1;
                }
            }
        }
        return $nombre;
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
        /** On instancie l'entityRepository */
        $profilesRepository = $this->em->getRepository(Profiles::class);

        /** On récupère le nombre de profil depuis la table profils */
        $nombre = $profilesRepository->countProfiles();
        $profil = 0;
        if ($nombre['request']) {
            $profil = $nombre['request'][0]['total'];
        }
        return $profil;
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
        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);

        /** Appelle le client HTTP */
        $queryParams = ['defaults'=>'true', 'p'=>1, 'ps'=>500 ];
        $result = $this->client->httpSonarQube("$tempoUrl/api/qualityprofiles/search?".http_build_query($queryParams));
        if (in_array($result['code'] ?? -1, [503, 504])) {
            $titre=static::$oups;
            $message = $result['erreur'];
            $this->addFlash('notice', ['type'=>'alert', 'titre'=>$titre, 'message'=>$message]);
            return 0;
        }

        /** Si les profils custom n'existent pas on envoi 0 */
        $count=0;
        if (key_exists('profiles', $result)) {
            $count=count($result['profiles']);
        }
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
     * @return [type]
     *
     * Created at: 15/12/2022, 22:08:18 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function majProperties(string $type, int $bd, int $sonar)
    {
        /** On instancie l'entityRepository */
        $propertiesRepository = $this->em->getRepository(Properties::class);

        /** On met à jour la date de modification */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        $map=[  'projet_bd'=>$bd, 'projet_sonar'=>$sonar,
                'profil_bd'=>$bd, 'profil_sonar'=>$sonar,
                'date_modification_projet'=>$date,
                'date_modification_profil'=>$date];

        if ($type === 'projet') {
            $propertiesRepository->updatePropertiesProjet($map);
        } else {
            $propertiesRepository->updatePropertiesProfiles($map);
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
        $propertiesRepository = $this->em->getRepository(Properties::class);

        /** On récupère le nombre de projet et de profil */
        $getProperties = $propertiesRepository->getProperties('properties');

        /** La table est vide. On initialise les valeurs */
        if (!$getProperties['request']) {
            $projetBd = $projetSonar = $profilBd = $profilSonar = 0;

            $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));
            $dateCreationFormat = $date;
            $dateModificationProjet = $date;
            $dateModificationProfil = $date;
            $map=[
                'projet_bd'=>$projetBd, 'projet_sonar'=>$projetSonar,
                'profil_bd'=>$profilBd, 'profil_sonar'=>$profilSonar,
                'date_creation'=>$dateCreationFormat,
                'date_modification_projet'=>$dateModificationProjet,
                'date_modification_profil'=>$dateModificationProfil];

            $propertiesRepository->insertProperties($map);
        } else {
            $projetBd = $getProperties['request'][0]['projet_bd'];
            $projetSonar = $getProperties['request'][0]['projet_sonar'];
            $dateModificationProjet = $getProperties['request'][0]['date_modification_projet'];
            $profilBd = $getProperties['request'][0]['profil_bd'];
            $profilSonar = $getProperties['request'][0]["profil_sonar"];
            $dateModificationProfil = $getProperties['request'][0]['date_modification_profil'];
        }
        return ['projet_bd' => $projetBd,
                'projet_sonar' => $projetSonar,
                'date_modification_projet' => $dateModificationProjet,
                'profil_bd' => $profilBd,
                'profil_sonar' => $profilSonar,
                'date_modification_profil' => $dateModificationProfil
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
      /** On instancie l'entityRepository */
        $maMoulinetteEntity = $this->em->getRepository(MaMoulinette::class);

      /** On récupère le numéro de la dernière version en base */
        $getMaMoulinetteVersion = $maMoulinetteEntity->getMaMoulinetteVersion();
        return $getMaMoulinetteVersion['request'][0]['version'];
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
        $historiqueRepository = $this->em->getRepository(Historique::class);

        /** On récupère le nombre de favori que l'on souhaite afficher au max (10) */
        $nombreProjetFavori = $this->getParameter('nombre.favori');

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $security->getUser()->getPreference();
        $statutFavoriProjet = $preference['statut']['favori_projet'];
        $listeFavoriProjet = $preference['favori_projet'];
        $liste = '';

        /** Si la liste de favoris pour les projets est activée et que le nombre de projet > 0  */
        if ($statutFavoriProjet === true && count($listeFavoriProjet) > 0) {
            foreach ($listeFavoriProjet as $value) {
                $liste = $liste."'".$value."', ";
            }
            /* on prépare les données pour la requête */
            $map=['liste_projet'=>rtrim($liste, " , "), 'nombre_projet_favori'=>$nombreProjetFavori];
            $liste = $historiqueRepository->selectHistoriqueProjetFavori($map);
        }

        $data = [  'code' => $liste['code'] ?? 200, 'statut' => $statutFavoriProjet,
                    'liste_favori' => $liste, 'nombre_projet' => count($listeFavoriProjet)];
        return new JsonResponse($data, Response::HTTP_OK);
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
        $historiqueRepository = $this->em->getRepository(Historique::class);

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
                $favori = $historiqueRepository->getProjetFavori($where);
                array_push($liste, $favori);
            }
        }

        $data = [ 'code' => $liste['code'] ?? 200, 'statut' => $statutFavoriVersion,
                'liste_version' => $liste, 'nombre_projet' => count($listeFavoriVersion)];
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
        /** On instancie l'entityRepository */
        $listeProjetRepository = $this->em->getRepository(ListeProjet::class);

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

        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        /** On récupère les properties des projets et profils */
        $properties = static::getProperties();
        $dateVerificationProjet = 'false';
        $dateVerificationProfil = 'false';

        /** ***************** 1 - Date   ****************************  */
        /** On convertit la date en datetime. */
        /** On applique la la fréquence de mise à jour pour les projets et les profils. */
        $majProjet = '-'.$this->getParameter('maj.projet').' day';
        $majProfil = '-'.$this->getParameter('maj.profil').' day';

        $dateModificationProjet = new \DateTimeImmutable($properties['date_modification_projet']);
        $dateModificationProjet->modify($majProjet);

        $dateModificationProfil = new \DateTimeImmutable($properties['date_modification_profil']);
        $dateModificationProfil->modify($majProfil);

        /** ***** Date - Projet ***** */
        /** Si la base n'a pas été mise à jour, on récupère le nombre de projet */
        if (date_diff($dateModificationProjet, $date)->format('%a') >=
        $this->getParameter('maj.projet')) {
            /** On récupère le nombre de projet depuis le serveur sonar */
            $projetSonar = static::countProjetSonar();
            /** On récupère le nombre de projet en base */
            $projetBd = static::countProjetBD();

            $dateVerificationProjet = "true";
        } else {
            /** Sinon, on récupère les valeurs de la table de properties */
            $projetBd = $properties['projet_bd'];
            $projetSonar = $properties['projet_sonar'];
        }

        /** ***** Date - Profil ***** */
        /**
         * Si la base n'a pas été mise à jour, on récupère le nombre de projet
         */
        if (date_diff($dateModificationProfil, $date)->format('%a') >=
        $this->getParameter('maj.profil')) {
            /** On récupère le nombre de profil en base. */
            $profilBd = static::countProfilBD();
            /** On récupère le nombre de projet depuis le serveur sonar */
            $profilSonar = static::countProfilSonar();
            $dateVerificationProfil = "true";
        } else {
            /** Sinon, on récupère les valeurs de la table de properties */
            $profilBd = $properties['profil_bd'];
            $profilSonar = $properties['profil_sonar'];
        }

        /** ***************** 2 - Projet *****************************  */
        if ($dateVerificationProjet == 'true') {
            /** Si le référentiel local est différent de celui sur le serveur. */
            if ($projetSonar !== $projetBd) {
                /**
                 * Si le nombre de projetSonar est différent de projetBD
                 * alors on envoi un message à l'utilisateur pour qu'il mette à jour.
                 */
                $titre = '[ACCUEIL-001]';
                $message = ' Vous devez mettre à jour le référentiel local pour les ';
                $this->addFlash('info', ['type'=>'primary', 'titre'=>$titre, 'message'=>$message, 'ref'=>'PROJETS']);
            }

            /**
             * Si le référentiel sonar est égale de celui sur le serveur et que la table
             * de properties n'est pas à jour, on met à jour la table.
             */
            if ($projetSonar == $projetBd  && $projetSonar !== $properties['projet_sonar']) {
                $this->majProperties('projet', $projetBd, $projetSonar);
            }
        }

        /** ***************** 3 - PROFIL *****************************  */
        /** Si les properties ne sont pas à jour. */
        if ($dateVerificationProfil == 'true') {
            if ($profilSonar !== $profilBd) {
                /**
                 * Si le nombre de projetSonar est différent de projetBD
                 * alors on envoi un message à l'utilisateur pour qu'il mette à jour.
                 */
                $titre = '[ACCUEIL-002]';
                $message = ' Vous devez mettre à jour le référentiel local pour les ';
                $this->addFlash('info', ['type'=>'primary', 'titre'=>$titre, 'message'=>$message, 'ref'=>'PROFILS']);
            }
            /**
             * Si le référentiel sonar est égale de celui sur le serveur et que la table
             * de properties n'est pas à jour, on met à jour la table.
             */
            if ($profilSonar == $profilBd  && $profilSonar !== $properties['profil_sonar']) {
                $this->majProperties('profil', $profilBd, $profilSonar);
            }
        }

        /** ***************** 4 - Visibility *****************************  */
        $t1 = $listeProjetRepository->countListeProjetVisibility('public');
        $t2 = $listeProjetRepository->countListeProjetVisibility('private');

        $public = $t1['request'][0]['visibility'];
        $private = $t2['request'][0]['visibility'];

        /** ***************** 5 - Tags *****************************  */
        /** Renvoi le nombre de projet et le nombre de tags */
        $tag = $listeProjetRepository->countListeProjetTags();

        /** ***************** VERSION *** ************************* */
        /** On récupère le numero de version en base */
        $versionBd = static::getVersion();
        /** On récupère la version de l'application */
        $versionApp = $this->getParameter('version');
        /** si la dernière version en base est inférieure, on renvoie une alerte ; */
        if ($versionApp !== $versionBd) {
            $titre="OUPS !!!";
            $m1= "La base de données est en version ".$versionBd.". ";
            $m2 = "Vous devez passer le script de migration ".$versionApp.".";
            $message = $m1.$m2;
            $this->addFlash('notice', ['type'=>'warning', 'titre'=>$titre, 'message'=>$message]);
        }

        /** On va chercher les projets favoris ou les versions des projets */
        $t3 = static::getListeFavoriProjet($security, $request);
        $favoriProjet = json_decode($t3->getContent());
        $t4 = static::getListeFavoriVersion($security, $request);
        $favoriVersion = json_decode($t4->getContent());

        /** On a choisi la liste des projets favori
         *  sinon la liste des versions
         *  sinon rien
        */
        if ($favoriProjet->statut && $favoriProjet->code!==500) {
            $favori = $favoriProjet->liste_favori;
            $nombreProjet = $favoriProjet->nombre_projet;
            $composant = 'projet';
        } elseif ($favoriVersion->statut && $favoriVersion->code!==500) {
            $favori = $favoriVersion->liste_version;
            $nombreProjet = $favoriVersion->nombre_projet;
            $composant = 'version';
        } else {
            $nombreProjet = 0;
            $favori = [];
            $composant = 'vide';
        }

        /** On récupère le rôle de l'utilisateur  */
        $refreshBd=false;
        if ($this->isGranted('ROLE_COLLECTE')) { $refreshBd=true; }

        $render=static::genericRender();
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
        return $this->render('accueil/index.html.twig', $render);
    }
}
