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

use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;

/** Accès aux tables SLQLitec*/
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/** Logger */
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    public static $strContentType = 'application/json';
    public static $sonarUrl = "sonar.url";
    public static $dateFormat = "Y-m-d H:m:s";
    public static $regex = "/\s+/u";

    /** On ajoute un constructeur pour éviter à chaque fois d'injecter la même class */
    public function __construct (
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private Connection $connection,
        private HttpClientInterface $client
        )
        {
            $this->logger = $logger;
            $this->em = $em;
            $this->connection = $connection;
            $this->client = $client;
        }
    /**
     * httpClient
     *
     * @param  mixed $url
     * @return reponse
     */
    protected function httpClient($url): array
    {
        /**
         * On peut se connecter avec un user/password ou un token.
         * Nous on préfère le token.
         */
        if (empty($this->getParameter('sonar.token'))) {
        $user = $this->getParameter('sonar.user');
        $password = $this->getParameter('sonar.password');
        } else {
        $user = $this->getParameter('sonar.token');
        $password = '';
        }

        $response = $this->client->request('GET', $url,
        [
            'ciphers' => `AES128-SHA AES256-SHA DH-DSS-AES128-SHA DH-DSS-AES256-SHA DH-RSA-AES128-SHA
            DH-RSA-AES256-SHA DHE-DSS-AES128-SHA DHE-DSS-AES256-SHA DHE-RSA-AES128-SHA
            DHE-RSA-AES256-SHA ADH-AES128-SHA ADH-AES256-SHA`,
            'auth_basic' => [$user, $password], 'timeout' => 45,
            'headers' => ['Accept' => static::$strContentType,
            'Content-Type' => static::$strContentType]
        ]
        );

        /** Si la réponse est différente de HTTP: 200 alors... */
        if (200 !== $response->getStatusCode()) {
        // Le token ou le password n'est pas correct.
        if ($response->getStatusCode() == 401) {
            throw new \UnexpectedValueException('Erreur d\'Authentification. La clé n\'est pas correcte.');
        } else {
            throw new \UnexpectedValueException('Retour de la réponse différent de ce qui est prévu. Erreur ' .
            $response->getStatusCode());
        }
        }

        $contentType = $response->getHeaders()['content-type'][0];
        $this->logger->INFO('** ContentType *** '.isset($contentType));
        $responseJson = $response->getContent();
        return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Récupère le nombre de projet enregistré en base
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
     * Récupère le nombre de projet disponible sur le serveur sonarqube
     */
    private function countProjetSonar(): Int
    {
        /** On récupère le nombre de projet et on filtre */
        $url = $this->getParameter(static::$sonarUrl) . "/api/components/search?qualifiers=TRK&ps=500&p=1";

        /** On appel le client http */
        $result = $this->httpClient($url);

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
     * Récupère le nombre de profil enregistré en base
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
     * Récupère le nombre de profil disponible sur sonarqube
     */
    private function countProfilSonar(): Int
    {
        $url = $this->getParameter(static::$sonarUrl)
        . "/api/qualityprofiles/search?qualityProfile="
        . $this->getParameter('sonar.profiles');

        /** On appel le client http */
        $result = $this->httpClient($url);

        // Si les profils custom n'existent pas on envoi un message
        return count($result['profiles']);
    }

    /**
     * On met à jour la table de référence
     *
     * @return array
     */
    private function majProperties($type, $bd, $sonar)
    {
    /** On met à jour la date de modification */
    $date = new \DateTime();
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
     * Récupère les properties
     */
    private function getProperties(): array
    {
        /** On récupère le nombre de projet et de profil */
        $sql = "SELECT * FROM properties WHERE type='properties'";
        $r = $this->connection->fetchAllAssociative($sql);

        /** La table est vide. On initialise les valeurs */
        if (!$r){
            $projetBD=0;
            $projetSonar=0;
            $profilBD=0;
            $profilSonar=0;

            $date = new \DateTime();
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
     * On récupère le numéro de version en base
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
     * index
     *
     * @return Response
     */
    #[Route('/home', name: 'home')]
    public function index(): Response
    {
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

        $date = new \DateTime();

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
            $projetSonar=self::countProjetSonar();
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
            $profilSonar=self::countProfilSonar();
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

            /** Si le referentiel sonar est égale de celui sur le serveur et que la table
             *  de properties n'est pas à jour, on met à jour la table.
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
            /** Si le referentiel sonar est égale de celui sur le serveur et que la table
             *  de properties n'est pas à jour, on met à jour la table.
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

        /** On récupère les projets en favori. Pour le moment on limite le nombre de projet à 10.
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

        return $this->render('home/index.html.twig',
        [
            'projetBD' => $projetBD, 'projetSonar' => $projetSonar,
            'profilBD' => $profilBD, 'profilSonar' => $profilSonar,
            'nombreFavori' => $nombre, 'favori' => $favori,
            'version' => $versionAPP, 'dateCopyright' => \date('Y')
        ]);
    }
}
