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

use Doctrine\DBAL\Connection;
use App\Entity\Secondary\Repartition;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/** Accès aux tables SLQLite */
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProjetController extends AbstractController
{
  public static $dateFormat = "Y-m-d H:i:s";
  public static $regex = "/\s+/u";

  /**
   * [Description for __construct]
   *
   * @param  private
   * @param  private
   * @param mixed
   *
   * Created at: 15/12/2022, 22:15:51 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function __construct (
    private Connection $connection,
    private ManagerRegistry $manager,
    )
    {
      $this->manager = $manager;
      $this->connection = $connection;
    }

  /**
   * [Description for index]
   * Affcihe la page projet
   * @return Response
   *
   * Created at: 15/12/2022, 22:16:04 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/projet', name: 'projet', methods: 'GET')]
  public function index(Request $request): Response
  {
    /** On crée un objet de reponse JSON */
    $response = new JsonResponse();

    $mode='';
    /** On on vérifie si on a activé le mode test */
    $mode = $request->get('mode');

    $render=[
      'mode'=>$mode,
      'version' => $this->getParameter('version'),
      'dateCopyright' => \date('Y'),
      Response::HTTP_OK
    ];

    if ($mode==='TEST') {
      return $response->setData($render);
    } else {
      return $this->render('projet/index.html.twig', $render);
    }
  }

    /**
     * [Description for setup]
     * On récupère le dernier setup du projet
     * @param mixed $mavenKey
     *
     * @return string
     *
     * Created at: 15/12/2022, 22:16:17 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function setup($mavenKey): string
    {
      /** On se connecte à la base pour connaitre la version du dernier setup pour le projet. */
      $reponse = $this->manager->getManager('secondary')
      ->getRepository(Repartition::class)
      ->findBy(['mavenKey' => $mavenKey],['setup' => 'DESC'],1);

      if (empty($reponse)) {
          $setup="NaN";
        } else { $setup=$reponse[0]->getSetup(); }

      return $setup;
    }

    /**
     * [Description for notes]
     * On récupère les indicateur du bloques infomation pour le projet
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 15/12/2022, 22:16:32 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function notes($mavenKey): array
    {
        /** On récupère les informations du projet de la table historique */
        $sql = "SELECT version, nom_projet AS name, date_version,
                  note_reliability, note_security, note_hotspot,note_sqale,
                  bug_blocker, bug_critical, bug_major,
                  vulnerability_blocker, vulnerability_critical, vulnerability_major,
                  code_smell_blocker, code_smell_critical, code_smell_major,
                  hotspot_total
                FROM historique
                WHERE maven_key='${mavenKey}'
                ORDER BY date_version DESC LIMIT 1";
        $r = $this->connection->fetchAllAssociative($sql);

        $resultat=true;
        if (!$r) {
          $resultat=false;
          return ['resultat'=>$resultat];
        }
        /**
         * On sépare la version du type de version
         * On considére que la version est version-type
         * Par exemple : 1.0.0-Release
         */
        $tempo=explode("-", $r[0]["version"]);

        return ['resultat'=>$resultat,
                'name'=>$r[0]["name"],
                'version'=>$tempo[0],
                'type'=>$tempo[1],
                'date_version'=>$r[0]["date_version"],
                'note_reliability'=>$r[0]["note_reliability"],
                'note_security'=>$r[0]["note_security"],
                'note_hotspot'=>$r[0]["note_hotspot"],
                'note_code_smell'=>$r[0]["note_sqale"],
                'bug_blocker'=>$r[0]["bug_blocker"],
                'bug_critical'=>$r[0]["bug_critical"],
                'bug_major'=>$r[0]["bug_major"],
                'vulnerability_blocker'=>$r[0]["vulnerability_blocker"],
                'vulnerability_critical'=>$r[0]["vulnerability_critical"],
                'vulnerability_major'=>$r[0]["vulnerability_major"],
                'code_smell_blocker'=>$r[0]["code_smell_blocker"],
                'code_smell_critical'=>$r[0]["code_smell_critical"],
                'code_smell_major'=>$r[0]["code_smell_major"],
                'hotspot_total'=>$r[0]["hotspot_total"],
              ];
    }

    /**
     * [Description for reference]
     * On récupère les informations du projet de référence.
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 15/12/2022, 22:16:56 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function reference($mavenKey): array
    {
        /** On récupère les informations du projet de référence */
        $sql = "SELECT  version, date_version,
                  note_reliability, note_security, note_hotspot, note_sqale,
                  bug_blocker, bug_critical, bug_major,
                  vulnerability_blocker, vulnerability_critical, vulnerability_major,
                  code_smell_blocker, code_smell_critical, code_smell_major, hotspot_total
                FROM historique
                WHERE maven_key='${mavenKey}' AND initial=1";
        $r = $this->connection->fetchAllAssociative($sql);
        $resultat=true;
        if (!$r) {
          $resultat=false;
          return ['resultat'=>$resultat];
        }
        $tempo=explode("-", $r[0]["version"]);
        return ['resultat'=>$resultat,
                'initial_version_application'=>$tempo[0],
                'initial_date_version'=>$r[0]["date_version"],
                'initial_note_reliability'=>$r[0]["note_reliability"],
                'initial_note_security'=>$r[0]["note_security"],
                'initial_note_hotspot'=>$r[0]["note_hotspot"],
                'initial_note_code_smell'=>$r[0]["note_sqale"],
                'initial_bug_blocker'=>$r[0]["bug_blocker"],
                'initial_bug_critical'=>$r[0]["bug_critical"],
                'initial_bug_major'=>$r[0]["bug_major"],
                'initial_vulnerability_blocker'=>$r[0]["vulnerability_blocker"],
                'initial_vulnerability_critical'=>$r[0]["vulnerability_critical"],
                'initial_vulnerability_major'=>$r[0]["vulnerability_major"],
                'initial_code_smell_blocker'=>$r[0]["code_smell_blocker"],
                'initial_code_smell_critical'=>$r[0]["code_smell_critical"],
                'initial_code_smell_major'=>$r[0]["code_smell_major"],
                'initial_hotspot_total'=>$r[0]["hotspot_total"],
              ];
    }

    /**
     * [Description for repartition]
     * On calcule le nombre de défaut par module
     *
     * @param mixed $mavenKey
     * @param mixed $contents
     *
     * @return array
     *
     * Created at: 15/12/2022, 22:17:19 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function repartition($mavenKey, $contents): array
    {
      $erreur=$frontend=$backend=$autre=0;

      /**
       * fr.ma-petite-entreprise:ma-moulinette : valeur par défaut pour les Tests
       */
      if (is_null($mavenKey)) {
        $mavenKey="fr.ma-petite-entreprise:ma-moulinette";
      }

      $app = explode(":", $mavenKey);
      foreach ($contents as $el) {
      /**
       * On supprime le début de la ligne
       * monapplication-metier/monapplication-metier-service/src/
       */
        $file = str_replace($mavenKey . ":", "", $el->getComponent());
      /**
       * On découpe le chemin.
       * monapplication-metier, monapplication-metier-service, src,...
       */
        $module = explode("/", $file);
        /** On prend la première entrée */
        switch ($module[0]) {
          case "du-presentation" || "rs-presentation" : $frontend = $frontend + 1;
                break;
          case  $app[1] . "-presentation" || $app[1] . "-presentation-commun" ||
                $app[1] . "-presentation-ear" || $app[1] . "-webapp": $frontend = $frontend + 1;
                break;
          case "rs-metier" : $backend = $backend + 1;
                break;
          case  $app[1] . "-metier" || $app[1] . "-common" ||
                $app[1] . "-api" || $app[1] . "-dao": $backend = $backend + 1;
                break;
          case  $app[1] . "-metier-ear" || $app[1] . "-service" ||
                $app[1] . "-serviceweb" || $app[1] . "-middleoffice": $backend = $backend + 1;
                break;
          case  $app[1] . "-metier-rest" || $app[1] . "-entite" ||
                $app[1] . "-serviceweb-client": $backend = $backend + 1;
                break;
          case  $app[1] . "-batch" || $app[1] . "-batchs" ||
                $app[1] . $app[1] . "-batch-envoi-dem-aval" ||
                $app[1] . "-batch-import-billets": $autre = $autre + 1;
                break;
          case  $app[1] . $app[1] . "-rdd" : $autre = $autre + 1;
                break;
          default:
              $erreur=$erreur+1;
        }
      }
      return ['erreur'=>$erreur, 'frontend'=>$frontend, 'backend'=>$backend];
    }

    /**
     * [Description for traitement]
     *
     * @param mixed $mavenKey
     * @param mixed $setup
     * @param mixed $type
     * @param mixed $severity
     *
     * @return array
     *
     * Created at: 15/12/2022, 22:17:46 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function traitement($mavenKey, $setup, $type, $severity): array
    {
      /**
       * On récupère la liste
       * Type : BUG, VULNERABILITY, CODE_SMELL
       * Severity : BLOCKER, CRITICAL, MAJOR,..
       */

      $liste = $this->manager->getManager('secondary')
                ->getRepository(Repartition::class)
                ->findBy(
              [
                'type' => $type,
                'severity' => $severity,
                'setup' => $setup
              ]);

      return self::repartition($mavenKey,$liste);
    }


  /**
   * [Description for variation]
   * Calcul de la variation entre deux notes
   *
   * @param mixed $a
   * @param mixed $b
   *
   * @return string
   *
   * Created at: 15/12/2022, 22:17:57 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  private function variation ($a, $b): string
  {
    /** Pas dévolution ou la valeur */
    if ($a==-1 && $b==0) {
      return"equal";
    }

    if ($a==0) {
      $c=0;
      return "equal";
    } else {

      $c=(($b-$a)/$a)*100;
    }

    switch ($c) {
      case $c>0:
          $evolution="down";
          break;
      case $c<0:
          $evolution="up";
          break;
      default:
        echo "Le taux de variation ne peut pas être calculé.";
    }
  return $evolution;
  }

  /**
   * [Description for projetCosui]
   * On ouvre la page COSUI
   * @param Request $request
   *
   * @return Response
   *
   * Created at: 15/12/2022, 22:18:08 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/projet/cosui', name: 'projet_cosui', methods: 'GET')]
  public function projetCosui(Request $request): Response
  {
    /** On crée un objet de reponse JSON */
    $response = new JsonResponse();

    /** On on vérifie si on a activé le mode test */
    if (is_null($request->get('mode'))) {
      $mode="null";
    } else {
      $mode = $request->get('mode');
    }

    /** On bind les variables */
    $mavenKey = $request->get('mavenKey');

    /** On récupère les notes */
    $n=self::notes($mavenKey);

    if ($n["resultat"]===false) {
      $nameApplication=$versionApplication=$typeApplication="NaN";
      $dateApplication="01/01/1980";
      $noteCodeSmell=$noteReliability=$noteSecurity=$noteHotspot="F";
      $nombreMetierCodeSmellBlocker=$nombreMetierCodeSmellCritical=$nombreMetierCodeSmellMajor=0;
      $nombrePresentationCodeSmellBlocker=$nombrePresentationCodeSmellCritical=$nombrePresentationCodeSmellMajor=0;
      $nombreMetierReliabilityBlocker=$nombreMetierReliabilityCritical=$nombreMetierReliabilityMajor=0;
      $bugBlocker=$bugCritical=$bugMajor=0;
      $vulnerabilityBlocker=$vulnerabilityCritical=$vulnerabilityMajor=0;
      $codeSmellBlocker=$codeSmellCritical=$codeSmellMajor=0;
      $hotspot=0;

      $nombrePresentationReliabilityBlocker=$nombrePresentationReliabilityCritical=0;
      $nombrePresentationReliabilityMajor=0;

      $nombrePresentationVulnerabilityBlocker=$nombrePresentationVulnerabilityCritical=0;
      $nombrePresentationVulnerabilityMajor=0;
      $nombreMetierVulnerabilityBlocker=$nombreMetierVulnerabilityCritical=$nombreMetierVulnerabilityMajor=0;

      $message="[COSUI-001] Il n'y a pas de données dans la babase !";
      $this->addFlash('alert', $message);
    }
    else {
      $nameApplication=$n["name"];
      $versionApplication=$n["version"];
      $typeApplication=$n["type"];
      $dateApplication=$n["date_version"];
      $noteCodeSmell=$n["note_code_smell"];
      $noteReliability=$n["note_reliability"];
      $noteSecurity=$n["note_security"];
      $noteHotspot=$n["note_hotspot"];
      $bugBlocker=$n["bug_blocker"];
      $bugCritical=$n["bug_critical"];
      $bugMajor=$n["bug_major"];
      $vulnerabilityBlocker=$n["vulnerability_blocker"];
      $vulnerabilityCritical=$n["vulnerability_critical"];
      $vulnerabilityMajor=$n["vulnerability_major"];
      $codeSmellBlocker=$n["code_smell_blocker"];
      $codeSmellCritical=$n["code_smell_critical"];
      $codeSmellMajor=$n["code_smell_major"];
      $hotspot=$n["hotspot_total"];
    }

    /** On récupères les indicateurs de la version de référence */
    $nn=self::reference($mavenKey);
    if ($nn["resultat"]===false) {
      $initialVersionApplication="NaN";
      $initialDateApplication="01/01/1980";
      $initialNoteCodeSmell=$initialNoteReliability=$initialNoteReliability="F";
      $initialNoteSecurity=$initialNoteHotspot="F";
      $initialBugBlocker=$initialBugCritical=$initialBugMajor=0;
      $initialVulnerabilityBlocker=$initialVulnerabilityCritical=$initialVulnerabilityMajor=0;
      $initialCodeSmellBlocker=$initialCodeSmellCritical=$initialCodeSmellMajor=0;
      $initialHotspot=0;
      $message="[COSUI-002] Vous devez choisir un projet comme référence !";
      $this->addFlash('alert', $message);
    }
    else {
        $initialVersionApplication=$nn["initial_version_application"];
        $initialDateApplication=$nn["initial_date_version"];
        $initialNoteCodeSmell=$nn["initial_note_code_smell"];
        $initialNoteReliability=$nn["initial_note_reliability"];
        $initialNoteSecurity=$nn["initial_note_security"];
        $initialNoteHotspot=$nn["initial_note_hotspot"];
        $initialBugBlocker=$nn["initial_bug_blocker"];
        $initialBugCritical=$nn["initial_bug_critical"];
        $initialBugMajor=$nn["initial_bug_major"];
        $initialVulnerabilityBlocker=$nn["initial_vulnerability_blocker"];
        $initialVulnerabilityCritical=$nn["initial_vulnerability_critical"];
        $initialVulnerabilityMajor=$nn["initial_vulnerability_major"];
        $initialCodeSmellBlocker=$nn["initial_code_smell_blocker"];
        $initialCodeSmellCritical=$nn["initial_code_smell_critical"];
        $initialCodeSmellMajor=$nn["initial_code_smell_major"];
        $initialHotspot=$nn["initial_hotspot_total"];
      }
    /** on récupère le dernier setup pour le projet */
    $setup=self::setup($mavenKey);

    /** On récupère la répartition pour l'application backend */
    /** Fiabilité Blocker */
    $fiabilite01=self::traitement($mavenKey, $setup, 'BUG', 'BLOCKER');
    $nombrePresentationReliabilityBlocker=$fiabilite01["frontend"];
    $nombreMetierReliabilityBlocker=$fiabilite01["backend"];

    /** Fiabilité Critical */
    $fiabilite02=self::traitement($mavenKey, $setup, 'BUG', 'CRITICAL');
    $nombrePresentationReliabilityCritical=$fiabilite02["frontend"];
    $nombreMetierReliabilityCritical=$fiabilite02["backend"];

    /** Fiabilité Major */
    $fiabilite03=self::traitement($mavenKey, $setup, 'BUG', 'MAJOR');
    $nombrePresentationReliabilityMajor=$fiabilite03["frontend"];
    $nombreMetierReliabilityMajor=$fiabilite03["backend"];

    /** Vulnérabilité Blocker */
    $vulnerabilite01=self::traitement($mavenKey, $setup, 'VULNERABILITY', 'BLOCKER');
    $nombrePresentationVulnerabilityBlocker=$vulnerabilite01["frontend"];
    $nombreMetierVulnerabilityBlocker=$vulnerabilite01["backend"];

    /** Vulnérabilité Critical */
    $vulnerabilite02=self::traitement($mavenKey, $setup, 'VULNERABILITY', 'CRITICAL');
    $nombrePresentationVulnerabilityCritical=$vulnerabilite02["frontend"];
    $nombreMetierVulnerabilityCritical=$vulnerabilite02["backend"];

    /** Vulnérabilité Major */
    $vulnerabilite03=self::traitement($mavenKey, $setup, 'VULNERABILITY', 'MAJOR');
    $nombrePresentationVulnerabilityMajor=$vulnerabilite03["frontend"];
    $nombreMetierVulnerabilityMajor=$vulnerabilite03["backend"];

    /** Maintenabilité Bloqant*/
    $codeSmell01=self::traitement($mavenKey, $setup, 'CODE_SMELL', 'BLOCKER');
    $nombrePresentationCodeSmellBlocker=$codeSmell01["frontend"];
    $nombreMetierCodeSmellBlocker=$codeSmell01["backend"];

    /** Maintenabilité Critical */
    $codeSmell02=self::traitement($mavenKey, $setup, 'CODE_SMELL', 'CRITICAL');
    $nombrePresentationCodeSmellCritical=$codeSmell02["frontend"];
    $nombreMetierCodeSmellCritical=$codeSmell02["backend"];

    /** Maintenabilité Major */
    $codeSmell03=self::traitement($mavenKey, $setup, 'CODE_SMELL', 'MAJOR');
    $nombrePresentationCodeSmellMajor=$codeSmell03["frontend"];
    $nombreMetierCodeSmellMajor=$codeSmell03["frontend"];

    /** On calcul l'évolution pour chaque indicateur par rapport
     *  aux notes de référence.
     */

    /** Calcul de la variation des hotspots */
    $evolutionHotspot=self::variation($initialHotspot, $hotspot);

    /** Calcul de la variation des mauvaises pratiques */
    $evolutionCodeSmellBlocker=self::variation($initialCodeSmellBlocker, $codeSmellBlocker);
    $evolutionCodeSmellCritical=self::variation($initialCodeSmellCritical, $codeSmellCritical);
    $evolutionCodeSmellMajor=self::variation($initialCodeSmellMajor, $codeSmellMajor);

    /** Calcul de la variation des vulnérabilités */
    $evolutionVulnerabilityBlocker=self::variation($initialVulnerabilityBlocker, $vulnerabilityBlocker);
    $evolutionVulnerabilityCritical=self::variation($initialVulnerabilityCritical, $vulnerabilityCritical);
    $evolutionVulnerabilityMajor=self::variation($initialVulnerabilityMajor, $vulnerabilityMajor);

    /** Calcul de la variation des vulnérabilités */
    $evolutionBugBlocker=self::variation($initialBugBlocker, $bugBlocker);
    $evolutionBugCritical=self::variation($initialBugCritical, $bugCritical);
    $evolutionBugMajor=self::variation($initialBugMajor, $bugMajor);


    $render=[
    'setup'=>$setup, 'monApplication'=>$nameApplication, 'version_application'=>$versionApplication,
    'type_application'=>$typeApplication, 'date_application'=>$dateApplication,
    'note_code_smell'=>$noteCodeSmell, 'note_reliability'=>$noteReliability,
    'note_security'=>$noteSecurity, 'note_hotspot'=>$noteHotspot, 'bug_blocker'=>$bugBlocker,
    'bug_critical'=>$bugCritical, 'bug_major'=>$bugMajor, 'vulnerability_blocker'=>$vulnerabilityBlocker,
    'vulnerability_critical'=>$vulnerabilityCritical, 'vulnerability_major'=>$vulnerabilityMajor,
    'code_smell_blocker'=>$codeSmellBlocker, 'code_smell_critical'=>$codeSmellCritical,
    'code_smell_major'=>$codeSmellMajor, 'hotspot'=>$hotspot,
    'initial_version_application'=>$initialVersionApplication, 'initial_date_application'=>$initialDateApplication,
    'initial_note_code_smell'=>$initialNoteCodeSmell, 'initial_note_reliability'=>$initialNoteReliability,
    'initial_note_security'=>$initialNoteSecurity, 'initial_note_hotspot'=>$initialNoteHotspot,
    'initial_bug_blocker'=>$initialBugBlocker, 'initial_bug_critical'=>$initialBugCritical,
    'initial_bug_major'=>$initialBugMajor, 'initial_vulnerability_blocker'=>$initialVulnerabilityBlocker,
    'initial_vulnerability_critical'=>$initialVulnerabilityCritical,
    'initial_vulnerability_major'=>$initialVulnerabilityMajor,
    'initial_code_smell_blocker'=>$initialCodeSmellBlocker,
    'initial_code_smell_critical'=>$initialCodeSmellCritical, 'initial_code_smell_major'=>$initialCodeSmellMajor,
    'evolution_bug_blocker'=>$evolutionBugBlocker, 'evolution_bug_critical'=>$evolutionBugCritical,
    'evolution_bug_major'=>$evolutionBugMajor, 'evolution_vulnerability_blocker'=>$evolutionVulnerabilityBlocker,
    'evolution_vulnerability_critical'=>$evolutionVulnerabilityCritical,
    'evolution_vulnerability_major'=>$evolutionVulnerabilityMajor,
    'evolution_code_smell_blocker'=>$evolutionCodeSmellBlocker,
    'evolution_code_smell_critical'=>$evolutionCodeSmellCritical,
    'evolution_code_smell_major'=>$evolutionCodeSmellMajor, 'evolution_hotspot'=>$evolutionHotspot,
    'modal_initial_bug_blocker'=>$initialBugBlocker, 'modal_initial_bug_critical'=>$initialBugCritical,
    'modal_initial_bug_major'=>$initialBugMajor,'modal_initial_vulnerability_blocker'=>$initialVulnerabilityBlocker,
    'modal_initial_vulnerability_critical'=>$initialVulnerabilityCritical,
    'modal_initial_vulnerability_major'=>$initialVulnerabilityMajor,
    'modal_initial_code_smell_blocker'=>$initialCodeSmellBlocker,
    'modal_initial_code_smell_critical'=>$initialCodeSmellCritical,
    'modal_initial_code_smell_major'=>$initialCodeSmellMajor, 'modal_initial_hotspot'=>$initialHotspot,
    'nombre_metier_code_smell_blocker'=>$nombreMetierCodeSmellBlocker,
    'nombre_metier_code_smell_critical'=>$nombreMetierCodeSmellCritical,
    'nombre_metier_code_smell_major'=>$nombreMetierCodeSmellMajor,
    'nombre_presentation_code_smell_blocker'=>$nombrePresentationCodeSmellBlocker,
    'nombre_presentation_code_smell_critical'=>$nombrePresentationCodeSmellCritical,
    'nombre_presentation_code_smell_major'=>$nombrePresentationCodeSmellMajor,
    'nombre_metier_reliability_blocker'=>$nombreMetierReliabilityBlocker,
    'nombre_metier_reliability_critical'=>$nombreMetierReliabilityCritical,
    'nombre_metier_reliability_major'=>$nombreMetierReliabilityMajor,
    'nombre_presentation_reliability_blocker'=>$nombrePresentationReliabilityBlocker,
    'nombre_presentation_reliability_critical'=>$nombrePresentationReliabilityCritical,
    'nombre_presentation_reliability_major'=>$nombrePresentationReliabilityMajor,
    'nombre_metier_vulnerability_blocker'=>$nombreMetierVulnerabilityBlocker,
    'nombre_metier_vulnerability_critical'=>$nombreMetierVulnerabilityCritical,
    'nombre_metier_vulnerability_major'=>$nombreMetierVulnerabilityMajor,
    'nombre_presentation_vulnerability_blocker'=>$nombrePresentationVulnerabilityBlocker,
    'nombre_presentation_vulnerability_critical'=>$nombrePresentationVulnerabilityCritical,
    'nombre_presentation_vulnerability_major'=>$nombrePresentationVulnerabilityMajor,
    'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y'),
    'mode'=>$mode,
    Response::HTTP_OK
    ];
    if ($mode==='TEST') {
      array_push($render,['notes'=>$n]);
      return $response->setData($render);
    } else {
      return $this->render('projet/cosui.html.twig',$render);
    }
  }

}
