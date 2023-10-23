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

namespace App\Controller\Projet;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\ORM\EntityManagerInterface;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description ApiNoteController]
 */
class ApiNoteController extends AbstractController
{

  /** Définition des constantes */
  public static $sonarUrl = "sonar.url";
  public static $dateFormat = "Y-m-d H:i:s";
  public static $europeParis = "Europe/Paris";
  public static $regex = "/\s+/u";
  public static $erreurMavenKey="La clé maven est vide!";
  public static $reference="<strong>[PROJET-002]</strong>";
  public static $message="Vous devez avoir le rôle COLLECTE pour réaliser cette action.";
  
  /**
   * [Description for __construct]
   *
   * Created at: 15/12/2022, 21:25:23 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function __construct(
    private EntityManagerInterface $em)
  {
    $this->em = $em;
  }


  /**
   * [Description for historiqueNoteAjout]
   * Récupère les notes pour la fiabilité, la sécurité et les mauvaises pratiques.
   * http://{url}https://{url}/api/measures/search_history?component={key}}&metrics={type}&ps=1000
   * On récupère que la première page soit 1000 résultat max.
   * Les valeurs possibles pour {type} sont : reliability_rating,security_rating,sqale_rating
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:36:58 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/historique/note', name: 'projet_historique_note', methods: ['GET'])]
  public function historiqueNoteAjout(Client $client, Request $request): response
  {

    /** On créé un objet response */
    $response = new JsonResponse();

    /** On bind les variables. */
    $tempoUrl = $this->getParameter(static::$sonarUrl);
    $mavenKey = $request->get('mavenKey');
    $type = $request->get('type');
    $mode = $request->get('mode');

    /** On teste si la clé est valide */
    if ($mavenKey==="null" && $mode==="TEST") {
      return $response->setData([
        "mode"=>$mode, "mavenKey"=>$mavenKey,
        "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
    }

    /** On vérifie si l'utilisateur à un rôle Collecte ? */
    if (!$this->isGranted('ROLE_COLLECTE')){
      return $response->setData([
        "mode"=>$mode ,
        "type"=>'alert',
        "reference" => static::$reference,
        "message"=> static::$message,
        Response::HTTP_OK]);
    }

    $url = "$tempoUrl/api/measures/search_history?component=$mavenKey&metrics=${type}_rating&ps=1000";

    /** On appel le client http. */
    $result = $client->http(trim(preg_replace(static::$regex, " ", $url)));

    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));
    $tempoDate = $date->format(static::$dateFormat);
    $nombre = $result["paging"]["total"];
    $mesures = $result["measures"][0]["history"];

    /** Enregistrement des nouvelles valeurs. */
    foreach ($mesures as $mesure) {
      $tempoMesureDate = $mesure["date"];
      $tempoMesureValue = $mesure["value"];
      $sql = "INSERT OR IGNORE INTO notes (maven_key, type, date, value, date_enregistrement)
              VALUES ('$mavenKey', '$type', '$tempoMesureDate', '$tempoMesureValue', '$tempoDate')";
      $this->em->getConnection()->prepare($sql)->executeQuery();
    }

    if ($request->get('type') == "reliability") {
      $type = "Fiabilité";
    }
    if ($request->get('type') == "security") {
      $type = "Sécurité";
    }
    if ($request->get('type') == "sqale") {
      $type = "Mauvaises Pratiques";
    }

    return $response->setData(["mode"=>$mode, "nombre" => $nombre, "type" => $type, Response::HTTP_OK]);
  }
}
