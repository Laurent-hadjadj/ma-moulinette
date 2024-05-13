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

namespace App\Controller\Home;

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/** Securité */
use Symfony\Bundle\SecurityBundle\Security;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

/** Logger */
use Psr\Log\LoggerInterface;

/** Accès aux tables SLQLite*/
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

class HomeController extends AbstractController
{
    public static $strContentType = 'application/json';
    public static $sonarUrl = "sonar.url";
    public static $dateFormat = "Y-m-d H:i:s";
    public static $europeParis = "Europe/Paris";
    public static $reference = "<strong>[HOME]</strong>";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 22:06:26 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
    ) {
        $this->logger = $logger;
        $this->em = $em;
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
        $listeProjetEntity = $this->em->getRepository(ListeProjet::class);

        /* On récupère le nombre de projet depuis la table liste_projet */
        $nombre = $listeProjetEntity->countListeProjet();

        $projet = 0;
        if ($nombre['request']){
            $projet = $nombre['request'][0]['total'];
        }
        return $projet;
    }

    /**
     * [Description for countProjetSonar]
     * Récupère le nombre de projet disponible sur le serveur sonarqube
     *
     * @return int
     *
     * Created at: 15/12/2022, 22:07:31 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function countProjetSonar(Client $client): int
    {
        /** On récupère le nombre de projet et on filtre */
        $url = $this->getParameter(static::$sonarUrl) . "/api/components/search?qualifiers=TRK&ps=500&p=1";

        /** On appel le client http */
        $result = $client->http($url);

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
        $profilesEntity = $this->em->getRepository(Profiles::class);

        /** On récupère le nombre de profil depuis la table profils */
        $nombre = $profilesEntity->countProfiles();
        $profil = 0;
        if ($nombre['request']) {
            $profil = $nombre['request'][0]['total'];
        }
        return $profil;
    }

    /**
     * [Description for countProfilSonar]
     * Récupère le nombre de profil disponible sur sonarqube
     *
     * @return int
     *
     * Created at: 15/12/2022, 22:07:58 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function countProfilSonar(Client $client): int
    {
        $url = $this->getParameter(static::$sonarUrl)
        . "/api/qualityprofiles/search?defaults=true";

        /** On appel le client http */
        $result = $client->http($url);

        /** Si les profils custom n'existent pas on envoi un message */
        return count($result['profiles']);
    }

    /**
     * [Description for majProperties]
     * On met à jour la table de référence
     *
     * @param mixed $type
     * @param mixed $bd
     * @param mixed $sonar
     *
     * @return [type]
     *
     * Created at: 15/12/2022, 22:08:18 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function majProperties($type, $bd, $sonar)
    {
        /** On instancie l'entityRepository */
        $propertiesEntity = $this->em->getRepository(Properties::class);

        /** On met à jour la date de modification */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        $map=[  'projet_bd'=>$bd, 'projet_sonar'=>$sonar,
                'profil_bd'=>$bd, 'profil_sonar'=>$sonar,
                'date_modification_projet'=>$date->format(static::$dateFormat),
                'date_modification_profil'=>$date->format(static::$dateFormat)];

        if ($type === 'projet') {
            $propertiesEntity->updatePropertiesProjet($map);
        } else {
            $propertiesEntity->updatePropertiesProfiles($map);
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
        $propertiesEntity = $this->em->getRepository(Properties::class);

        /** On récupère le nombre de projet et de profil */
        $getProperties = $propertiesEntity->getProperties('properties');

        /** La table est vide. On initialise les valeurs */
        if (!$getProperties['request']) {
            $projetBd = $projetSonar = $profilBd = $profilSonar = 0;

            $date = new DateTime();
            $date->setTimezone(new DateTimeZone(static::$europeParis));

            $dateCreationFormat = $date->format(static::$dateFormat);
            $dateModificationProjet = $date->format(static::$dateFormat);
            $dateModificationProfil = $date->format(static::$dateFormat);
            $map=[
                'projet_bd'=>$projetBd, 'projet_sonar'=>$projetSonar,
                'profil_bd'=>$profilBd, 'profil_sonar'=>$profilSonar,
                'date_creation'=>$dateCreationFormat,
                'date_modification_projet'=>$dateModificationProjet,
                'date_modification_profil'=>$dateModificationProfil];

            $propertiesEntity->insertProperties($map);
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

      /** On récupère le numéro de la dernère version en base */
        $getmaMoulinetteVersion = $maMoulinetteEntity->getMaMoulinetteVersion();
        return $getmaMoulinetteVersion['request'][0]['version'];
    }

    /**
     * [Description for getListefavoris]
     * Récupère la liste des projets favoris.
     *
     * @param Request $request
     * @param Client $client
     *
     * @return response
     *
     * Created at: 14/06/2023, 06:35:37 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/home/liste/favori', name: 'home_liste_favori')]
    private function getListeFavori(Security $security): response
    {
        /** On instancie l'entityRepository */
        $historiqueEntity = $this->em->getRepository(Historique::class);

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On récupère le nombre de favori que l'on souhaite afficher au max (10) */
        $nombreProjetFavori = $this->getParameter('nombre.favori');

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $security->getUser()->getPreference();
        $statutFavori = $preference['statut']['favori'];
        $statutVersion = $preference['statut']['version'];
        $listeFavori = $preference['favori'];

        /** On regarde si l'utilisateur a activé l'affichage des favoris. */
        if ($statutFavori === false || count($listeFavori) === 0 || $statutVersion === true) {
            $liste = false;
        } else {
            $condition = '';
            foreach ($listeFavori as $value) {
                $condition = $condition.' maven_key="'.$value.'" OR ';
            }

            /** On supprime le dernier OR */
            $clauseWhere = rtrim($condition, " OR ");
            $map=['clause_where'=>$clauseWhere, 'nombre_projet_favori'=>$nombreProjetFavori];
            $liste = $historiqueEntity->selectHistoriqueProjetfavori($map);
        }

        $data = [
                    'statut' => $statutFavori, 'listeFavori' => $liste,
                    'nombreProjet' => count($listeFavori), Response::HTTP_OK];
        return $response->setData($data);
    }

    /**
     * [Description for contruitMaRequete]
     *
     * @param mixed $liste
     * @param mixed $mavenkey
     * @param mixed $index
     *
     * @return [type]
     *
     * Created at: 14/06/2023, 16:06:05 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function contruitMaRequete($liste, $mavenkey, $index)
    {
        $m = $mavenkey[0];
        $l = "";
        $mavenKey = "maven_key='".$m."'";
        $version = "";

        $versions = array_values($liste[$index]);
        for ($v = 0; $v < count($versions[0]); $v++) {
            $version = $version." version='".$versions[0][$v]."' OR ";
        }
        $l = $l.' '.$mavenKey.' AND ('.$version;

        /** On supprime le dernier OR */
        $rtrimOr = rtrim($l, " OR ");
        return $rtrimOr.')';
    }


    /**
     * [Description for getListeVersion]
     * Récupération de la liste des projets par version (limité à 4).
     *
     * @param Security $security
     *
     * @return response
     *
     * Created at: 14/06/2023, 07:09:32 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/home/liste/version', name: 'home_liste_version')]
    private function getListeVersion(Security $security): response
    {
        /** On instancie l'entityRepository */
        $historiqueEntity = $this->em->getRepository(Historique::class);

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $security->getUser()->getPreference();
        $statutVersion = $preference['statut']['version'];
        $listeVersion = $preference['version'];

        /** On regarde si l'utilisateur a activé l'affichage des favoris. */
        if ($statutVersion === false || count($listeVersion) === 0) {
            $liste = false;
        } else {
            $keys = array_values($listeVersion);
            $liste = [];
            for ($i = 0; $i < count($keys); $i++) {
                $where = static::contruitMaRequete($keys, array_keys($keys[$i]), $i);
                $favori = $historiqueEntity->getProjetFavori($where);
                array_push($liste, $favori);
            }
        }

        $data = [
            'statut' => $statutVersion, 'listeVersion' => $liste,
            'nombreProjet' => count($listeVersion), Response::HTTP_OK];
        return $response->setData($data);
    }

    /**
     * [Description for index]
     * Affiche ma page d'accueil
     *
     * @param Client $client
     * @param Request $request
     * @param Security $security
     *
     * Created at: 15/12/2022, 22:09:19 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/home', name: 'home', methods:'GET')]
    public function index(Client $client, Security $security, Request $request): Response
    {
        /** On instancie l'entityRepository */
        $listeProjet = $this->em->getRepository(ListeProjet::class);

        /**
         * Description du processus :
         * 1 - On regarde si la base locale a déjà été mise à jour, i.e.
         *     si la base locale a été mise à jour dans la journée alors les propriétées
         *     sont à jour. Alors on renvoit les valeurs de la tables properties, sinon
         * 2 - On récupère le nombre de projet et le nombre de profil diponible sur le serveur
         *     sonarqube. Si les valeurs sont identiques, on renvoit les valeurs récupérés et on
         *     met à jour la date de la table de propriétées. Sinon,
         * 3 - On met à jour la table liste_projet et/ou profiles et on met à jour la table
         *     properties.
         */

        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        /** On récupère les properties des projets et profils */
        $properties = static::getProperties();
        $dateVerificationProjet = 'false';
        $dateVerificationProfil = 'false';

        /** ***************** 1 - Date   ****************************  */
        /** On convertit la date en datetime. */
        /** On applique la la fréquence de mise à jour pour les projets et les profils. */
        $majProjet = '-'.$this->getParameter('maj.projet').' day';
        $majProfil = '-'.$this->getParameter('maj.profil').' day';

        $dateModificationProjet = new DateTime($properties['date_modification_projet']);
        $dateModificationProjet->modify($majProjet);

        $dateModificationProfil = new DateTime($properties['date_modification_profil']);
        $dateModificationProfil->modify($majProfil);

        /** ***** Date - Projet ***** */
        /** Si la base n'a pas été mise à jour, on récupère le nombre de projet */
        if (date_diff($dateModificationProjet, $date)->format('%a') >=
        $this->getParameter('maj.projet')) {
            /** On récupère le nombre de projet depuis le serveur sonar */
            $projetSonar = static::countProjetSonar($client);
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
            $profilSonar = static::countProfilSonar($client);
            $dateVerificationProfil = "true";
        } else {
            /** Sinon, on récupère les valeurs de la table de properties */
            $profilBd = $properties['profil_bd'];
            $profilSonar = $properties['profil_sonar'];
        }

        /** ***************** 2 - Projet *****************************  */
        if ($dateVerificationProjet == "true") {
            /** Si le referentiel local est différent de celui sur le serveur. */
            if ($projetSonar !== $projetBd) {
                /**
                 * Si le nombre de projetSonar est différent de projetBD
                 * alors on envoit un message à l'utilisateur pour qu'il mette à jour.
                 */
                $message = "[PROJET] Vous devez mettre à jour le référentiel local !";
                $this->addFlash('info', $message);
            }

            /**
             * Si le referentiel sonar est égale de celui sur le serveur et que la table
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
                 * alors on envoit un message à l'utilisateur pour qu'il mette à jour.
                 */
                $message = "[PROFIL] Vous devez mettre à jour le référentiel local !";
                $this->addFlash('info', $message);
            }
            /**
             * Si le referentiel sonar est égale de celui sur le serveur et que la table
             * de properties n'est pas à jour, on met à jour la table.
             */
            if ($profilSonar == $profilBd  && $profilSonar !== $properties['profil_sonar']) {
                $this->majProperties('profil', $profilBd, $profilSonar);
            }
        }

        /** ***************** 4 - Visibility *****************************  */
        $t1 = $listeProjet->countListeProjetVisibility('public');
        $t2 = $listeProjet->countListeProjetVisibility('private');

        $public = $t1['request'][0]['visibility'];
        $private = $t2['request'][0]['visibility'];

        /** ***************** VERSION *** ************************* */
        /** On récupère le numero de version en base */
        $versionBd = static::getVersion();
        /** On récupère la version de l'application */
        $versionApp = $this->getParameter('version');
        /** si la dernière version en base est inférieure, on renvoie une alerte ; */
        if ($versionApp !== $versionBd) {
            $m1 = "Oooups !!! La base de données est en version ".$versionBd." ";
            $m2 = "Vous devez passer le script de migration ".$versionApp.".";
            $message = $m1.$m2;
            $this->addFlash('alert', $message);
        }

        /** On va chercher les projets favoris ou les versions des projets */
        $t3 = static::getListeFavori($security, $request);
        $data1 = json_decode($t3->getContent());
        $t4 = static::getListeVersion($security, $request);
        $data2 = json_decode($t4->getContent());

        /** On a choisi la liste des projets favori
         *  sinon la liste des versions
         *  sinon rien
        */
        if ($data1->statut) {
            $favori = $data1->listeFavori;
            $nombreProjet = $data1->nombreProjet;
            $composant = 'projet';
        } elseif ($data2->statut) {
            $favori = $data2->listeVersion;
            $nombreProjet = $data2->nombreProjet;
            $composant = 'version';
        } else {
            $nombreProjet = 0;
            $favori = false;
            $composant = 'vide';
        }

        /** On récupère le rôle de l'utilisateur  */
        $refreshBd=false;
        if ($this->isGranted('ROLE_GESTIONNAIRE')) { $refreshBd=true; }

        $render = [
            'refreshBD'=>$refreshBd,
            'projetBD' => $projetBd, 'projetSonar' => $projetSonar,
            'profilBD' => $profilBd, 'profilSonar' => $profilSonar,
            'composant' => $composant, 'nombreProjet' => $nombreProjet, 'favori' => $favori,
            'public' => $public, 'private' => $private,
            'version' => $versionApp, 'dateCopyright' => \date('Y'),
            Response::HTTP_OK];

            return $this->render('home/index.html.twig', $render);
    }
}
