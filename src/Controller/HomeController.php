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

namespace App\Controller;

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

/** Logger */
use Psr\Log\LoggerInterface;

/** Accès aux tables SLQLitec*/
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;

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
  public static $regex = "/\s+/u";

  /**
   * [Description for __construct]
   *
   * @param  private
   * @param  private
   * @param  private
   *
   * Created at: 15/12/2022, 22:06:26 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function __construct (
      private LoggerInterface $logger,
      private EntityManagerInterface $em,
      private Connection $connection,
    )
    {
      $this->logger = $logger;
      $this->em = $em;
      $this->connection = $connection;
    }

  /**
   * [Description for countProjetBD]
   * Récupère le nombre de projet enregistré en base
   *
   * @return Int
   *
   * Created at: 15/12/2022, 22:06:59 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  private function countProjetBD(): Int
  {
    /**
     * On récupère le nombre de projet depuis la table liste_projet
     */
    $sql = "SELECT COUNT(*) as total from liste_projet";
    $r = $this->connection->fetchAllAssociative($sql);
    if (!$r) {
        $projet=0;
    } else {
        $projet=$r[0]['total'];
    }
    return $projet;
  }

  /**
   * [Description for countProjetSonar]
   * Récupère le nombre de projet disponible sur le serveur sonarqube
   *
   * @return Int
   *
   * Created at: 15/12/2022, 22:07:31 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  private function countProjetSonar(Client $client): Int
  {
    /** On récupère le nombre de projet et on filtre */
    $url = $this->getParameter(static::$sonarUrl) . "/api/components/search?qualifiers=TRK&ps=500&p=1";

    /** On appel le client http */
    $result = $client->http($url);

    /**
     * On compte le nombre de projet.
     */
    $nombre = 0;
    foreach ($result["components"] as $component) {
        /**
         * On exclue les projets archivés avec le suffixe "-SVN".
         *  "project": "fr.domaine:mon-application-SVN"
         */
        $mystring = $component["project"];
        $findme   = "-SVN";
        if (!strpos($mystring, $findme)) {
            $nombre = $nombre + 1;
        }
    }
    return $nombre;
  }

  /**
   * [Description for countProfilBD]
   * Récupère le nombre de profil enregistré en base
   *
   * @return Int
   *
   * Created at: 15/12/2022, 22:07:46 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  private function countProfilBD(): Int
  {
    /** On récupère le nombre de profil depuis la table profils */
    $sql = "SELECT COUNT(*) as total from profiles";
    $r = $this->connection->fetchAllAssociative($sql);
    if (!$r) {
        $profil=0;
    } else {
        $profil=$r[0]['total'];
    }
    return $profil;
  }

  /**
   * [Description for countProfilSonar]
   * Récupère le nombre de profil disponible sur sonarqube
   *
   * @return Int
   *
   * Created at: 15/12/2022, 22:07:58 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  private function countProfilSonar(Client $client): Int
  {
      $url = $this->getParameter(static::$sonarUrl)
      . "/api/qualityprofiles/search?qualityProfile="
      . $this->getParameter('sonar.profiles');

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
    /** On met à jour la date de modification */
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));

    $dateModificationProjet = $date->format(static::$dateFormat);
    $dateModificationProfil = $date->format(static::$dateFormat);

    if ($type==="projet") {
        $sql="UPDATE properties
        SET projet_bd= ${bd},
            projet_sonar=${sonar},
            date_modification_projet = '${dateModificationProjet}'
        WHERE type = 'properties'";
        $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql)))->executeQuery();

    } else {
        $sql="UPDATE properties
        SET profil_bd= ${bd},
            profil_sonar=${sonar},
            date_modification_profil = '${dateModificationProfil}'
        WHERE type = 'properties'";
        $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql)))->executeQuery();
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
    /** On récupère le nombre de projet et de profil */
    $sql = "SELECT * FROM properties WHERE type='properties'";
    $r = $this->connection->fetchAllAssociative($sql);

    /** La table est vide. On initialise les valeurs */
    if (!$r){
        $projetBD=$projetSonar=$profilBD=$profilSonar=0;

        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        $dateCreationFormat = $date->format(static::$dateFormat);
        $projetModificationDate = $date->format(static::$dateFormat);
        $profilModificationDate = $date->format(static::$dateFormat);

        $sql2="INSERT INTO properties
                (type, projet_bd, projet_sonar, profil_bd, profil_sonar,
                date_modification_projet, date_modification_profil, date_creation)
                VALUES
                ('properties', ${projetBD}, ${projetSonar}, ${profilBD}, ${profilSonar},
                '${projetModificationDate}', '${profilModificationDate}', '${dateCreationFormat}')";
        $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql2)))->executeQuery();
    } else {
        $projetBD=$r[0]["projet_bd"];
        $projetSonar=$r[0]["projet_sonar"];
        $projetModificationDate=$r[0]["date_modification_projet"];
        $profilBD=$r[0]["profil_bd"];
        $profilSonar=$r[0]["profil_sonar"];
        $profilModificationDate=$r[0]["date_modification_profil"];
    }

    return ['projetBD'=>$projetBD,
            'projetSonar'=>$projetSonar,
            'projetDateModification'=>$projetModificationDate,
            'profilBD'=>$profilBD,
            'profilSonar'=>$profilSonar,
            'profilDateModification'=>$profilModificationDate
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
    /** On récupère le numéro de la dernère version en base */
    $sql = "SELECT version
            FROM ma_moulinette
            ORDER BY date_version DESC LIMIT 1";
    $select = $this->em->getConnection()->prepare($sql)->executeQuery();
    $getVersion=$select->fetchAllAssociative();
    return $getVersion[0]['version'];
  }


  /**
   * [Description for index]
   *
   * @return Response
   *
   * Created at: 15/12/2022, 22:09:19 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/home', name: 'home', methods:'GET')]
  public function index(Client $client, Request $request): Response
  {
    $mode=$request->get('mode');
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
    $properties=self::getProperties();
    $dateVerificationProjet="false";
    $dateVerificationProfil="false";

    /** ***************** 1 - Date   ****************************  */
    /** On convertit la date en datetime. */
    /** On applique la la fréquence de mise à jour pour les projets et les profils. */
    $majProjet="-".$this->getParameter('maj.projet')." day";
    $majProfil="-".$this->getParameter('maj.profil')." day";

    $dateModificationProjet=new \DateTime($properties['projetDateModification']);
    $dateModificationProjet->modify($majProjet);

    $dateModificationProfil=new \DateTime($properties['profilDateModification']);
    $dateModificationProfil->modify($majProfil);

    /** ***** Date - Projet ***** */
    /** Si la base n'a pas été mise à jour, on récupère le nombre de projet */
    if (date_diff($dateModificationProjet,$date)->format('%a') >=
    $this->getParameter('maj.projet')) {
        /** On récupère le nombre de projet depuis le serveur sonar */
        $projetSonar=self::countProjetSonar($client);
        /** On récupère le nombre de projet en base */
        $projetBD=self::countProjetBD();
        $dateVerificationProjet="true";
    } else {
        /** Sinon, on récupère les valeurs de la table de properties */
        $projetBD=$properties['projetBD'];
        $projetSonar=$properties['projetSonar'];
    }

    /** ***** Date - Profil ***** */
    /**
     * Si la base n'a pas été mise à jour, on récupère le nombre de projet
     */
    if (date_diff($dateModificationProfil,$date)->format('%a') >=
    $this->getParameter('maj.profil')){
        /** On récupère le nombre de profil en base. */
        $profilBD=self::countProfilBD();
        /** On récupère le nombre de projet depuis le serveur sonar */
        $profilSonar=self::countProfilSonar($client);
        $dateVerificationProfil="true";
    } else {
        /** Sinon, on récupère les valeurs de la table de properties */
        $profilBD=$properties['profilBD'];
        $profilSonar=$properties['profilSonar'];
    }

    /** ***************** 2 - Projet *****************************  */
    if ($dateVerificationProjet=="true"){
        /** Si le referentiel local est différent de celui sur le serveur. */
        if ($projetSonar !== $projetBD){
            /**
             * Si le nombre de projetSonar est différent de projetBD
             * alors on envoit un message à l'utilisateur pour qu'il mette à jour.
             */
            $message="[PROJET] Vous devez mettre à jour le référentiel local !";
            $this->addFlash('info', $message);
        }

        /**
         * Si le referentiel sonar est égale de celui sur le serveur et que la table
         * de properties n'est pas à jour, on met à jour la table.
         */
        if ($projetSonar == $projetBD  && $projetSonar !== $properties['projetSonar'] ) {
        $this->majProperties("projet", $projetBD, $projetSonar);
        }
    }

    /** ***************** 3 - PROFIL *****************************  */
    /** Si les properties ne sont pas à jour. */
    if ($dateVerificationProfil=="true"){
        if ($profilSonar !== $profilBD){
        /**
         * Si le nombre de projetSonar est différent de projetBD
         * alors on envoit un message à l'utilisateur pour qu'il mette à jour.
         */
        $message="[PROFIL] Vous devez mettre à jour le référentiel local !";
        $this->addFlash('info', $message);
        }
        /**
         * Si le referentiel sonar est égale de celui sur le serveur et que la table
         * de properties n'est pas à jour, on met à jour la table.
         */
        if ($profilSonar == $profilBD  && $profilSonar !== $properties['profilSonar'] ) {
            $this->majProperties("profil", $profilBD, $profilSonar);
            }
    }

    /** ***************** VERSION *** ************************* */
    /** On récupère le numero de version en base */
    $versionBD=self::getVersion();
    /** On récupère la version de l'application */
    $versionAPP=$this->getParameter('version');
    /** si la dernière version en base est inférieure, on renvoie une alerte ; */
    if ($versionAPP !== $versionBD ) {
        $m1 = "Oooups !!! La base de données est en version ".$versionBD." ";
        $m2= "Vous devez passer le script de migration ".$versionAPP.".";
        $message=$m1.$m2;
        $this->addFlash('alert', $message);
    }

    $nombreFavori=$this->getParameter('nombre.favori');

    /**
     * On récupère les projets en favori. Pour le moment on limite le nombre de projet à 10.
     * SQLite : 0 (false) and 1 (true).
     */
    $sql = "SELECT DISTINCT
                    maven_key as mavenkey,
                    nom_projet as nom,
                    version, date_version as date,
                    note_reliability as fiabilite,
                    note_security as securite,
                    note_hotspot as hotspot,
                    note_sqale as sqale,
                    nombre_bug as bug,
                    nombre_vulnerability as vulnerability,
                    nombre_code_smell as code_smell,
                    hotspot_total as hotspots
            FROM historique
            WHERE favori=1
            ORDER BY date_version LIMIT $nombreFavori";

    $select = $this->em->getConnection()
                    ->prepare(trim(preg_replace(static::$regex, " ", $sql)))->executeQuery();
    $favoris = $select->fetchAllAssociative();
    if (empty($favoris)) {
        $nombre = 0;
        $favori = 'FALSE';
    } else {
        $nombre = 1;
        $favori = $favoris;
    }

    $response = new JsonResponse();
    $render=[
      'projetBD' => $projetBD, 'projetSonar' => $projetSonar,
      'profilBD' => $profilBD, 'profilSonar' => $profilSonar,
      'nombreFavori' => $nombre, 'favori' => $favori,
      'version' => $versionAPP, 'dateCopyright' => \date('Y'),
      'mode'=>$mode, Response::HTTP_OK];

    if ($mode==="TEST"){
      return $response->setData($render);
    } else {
      return $this->render('home/index.html.twig', $render);
    }
  }
}
