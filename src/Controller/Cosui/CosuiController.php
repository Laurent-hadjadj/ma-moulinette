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

namespace App\Controller\Cosui;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/** Accès aux tables SLQLite */
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Secondary\Repartition;
use App\Entity\Main\Historique;

class CosuiController extends AbstractController
{
    /**
     * [Description for __construct]
     *  EntityManagerInterface = em
     *
     * Created at: 13/02/2023, 08:57:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(private EntityManagerInterface $em, private ManagerRegistry $mr)
    {
        $this->em = $em;
        $this->mr = $mr;
    }

    /**
     * [Description for extractNameFromMavenKey]
     * Extrait le nom du projet de la clé
     *
     * @param mixed $mavenKey
     *
     * @return string
     *
      * Created at: 13/03/2024 21:47:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function extractNameFromMavenKey($mavenKey): string
    {
        /**
         * On récupère le nom de l'application depuis la clé mavenKey
         * [fr.ma-petite-entreprise] : [ma-moulinette]
         */
        $app = explode(":", $mavenKey);
        if (count($app)===1) {
            /** La clé maven n'est pas conforme, on ne peut pas déduire le nom de l'application */
            $name=$mavenKey;
        } else {
            $name=$app[1];
        }
        return $name;
    }


    /**
     * [Description for note2point]
     * Renvoie la valeur de la note en point sur 100 ;
     * A=100, B=70, C=50, D=25, E=5, F=0;
     *
     * @param mixed $note
     *
     * @return int
     *
     * Created at: 06/03/2024 19:45:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function note2point($note): int
    {
        switch ($note) {
            case 'A':
                $p=100;
                break;
            case 'B':
                $p=80;
                break;
            case 'C':
                $p=60;
                break;
            case 'D':
                $p=30;
                break;
            case 'E':
                $p=10;
                break;
            default:
                $p=0;
        }
        return $p;
    }

    /**
     * [Description for setup]
     * On récupère le dernier setup du projet
     *
     * @param string $mavenKey
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
        $reponse = $this->mr->getRepository(Repartition::class, 'secondary')
                    ->findBy(['mavenKey' => $mavenKey], ['setup' => 'DESC'], 1);

        $setup = "NaN";
        if (!empty($reponse)) {
            $setup = $reponse[0]->getSetup();
        }

        return $setup;
    }

    /**
     * [Description for notes]
     * On récupère les indicateurs du bloc information pour le projet
     *
     * @param string $mavenKey
     * @param string $mode
     *
     * @return array
     *
     * Created at: 15/12/2022, 22:16:32 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function notes($mavenKey, $mode): array
    {
        /** On instancie l'entityRepository */
        $historique = $this->em->getRepository(Historique::class);

        /** On récupère les informations du projet de la table historique */
        $map=['maven_key'=>$mavenKey];
        $request=$historique->selectHistoriqueProjetLast($mode, $map);
        if ($request['code']!=200) {
            return [
                    'mode' => $mode, 'maven_key' => $mavenKey,
                    'code'=>$request['code'], 'erreur' => $request['erreur']
                ];
        }

        if (!$request['infos']) {
            return ['resultat' => false];
        }

        /**
         * On sépare la version du type de version
         * On considére que la version est version-type
         * Par exemple : 1.0.0-Release
         */
        $tempo = explode("-", $request['infos'][0]['version']);

        return ['resultat' => true,
                'name' => $request['infos'][0]['name'],
                'version' => $tempo[0],
                'type' => $tempo[1],
                'date_version' => $request['infos'][0]['date_version'],
                'note_reliability' => $request['infos'][0]['note_reliability'],
                'note_security' => $request['infos'][0]['note_security'],
                'note_hotspot' => $request['infos'][0]['note_hotspot'],
                'note_code_smell' => $request['infos'][0]['note_sqale'],
                'bug_blocker' => $request['infos'][0]['bug_blocker'],
                'bug_critical' => $request['infos'][0]['bug_critical'],
                'bug_major' => $request['infos'][0]['bug_major'],
                'vulnerability_blocker' => $request['infos'][0]['vulnerability_blocker'],
                'vulnerability_critical' => $request['infos'][0]['vulnerability_critical'],
                'vulnerability_major' => $request['infos'][0]['vulnerability_major'],
                'code_smell_blocker' => $request['infos'][0]['code_smell_blocker'],
                'code_smell_critical' => $request['infos'][0]['code_smell_critical'],
                'code_smell_major' => $request['infos'][0]['code_smell_major'],
                'hotspot_total' => $request['infos'][0]['hotspot_total'],
                'couverture' => $request['infos'][0]['couverture'],
                'sqale_debt_ratio' => $request['infos'][0]['sqale_debt_ratio']
            ];
    }

    /**
     * [Description for reference]
     * On récupère les informations du projet de référence.
     *
     * @param string $mavenKey
     * @param string $mode
     *
     * @return array
     *
     * Created at: 15/12/2022, 22:16:56 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function reference($mavenKey, $mode): array
    {
        /** On instancie l'entityRepository */
        $historique = $this->em->getRepository(Historique::class);

        /** On récupère les informations du projet de référence */
        $map=['maven_key'=>$mavenKey];
        $request=$historique->selectHistoriqueProjetReference($mode, $map);
        if ($request['code']!=200) {
            return [
                    'mode' => $mode, 'maven_key' => $mavenKey,
                    'code'=>$request['code'], 'erreur' => $request['erreur']
                    ];
        }
        if (!$request['reference']) {
            return ['resultat' => false];
        }

        $tempo = explode("-", $request['reference'][0]['version']);
        return ['resultat' => true,
                'initial_version_application' => $tempo[0],
                'initial_date_version' => $request['reference'][0]['date_version'],
                'initial_note_reliability' => $request['reference'][0]['note_reliability'],
                'initial_note_security' => $request['reference'][0]['note_security'],
                'initial_note_hotspot' => $request['reference'][0]['note_hotspot'],
                'initial_note_code_smell' => $request['reference'][0]['note_sqale'],
                'initial_bug_blocker' => $request['reference'][0]['bug_blocker'],
                'initial_bug_critical' => $request['reference'][0]['bug_critical'],
                'initial_bug_major' => $request['reference'][0]['bug_major'],
                'initial_vulnerability_blocker' => $request['reference'][0]['vulnerability_blocker'],
                'initial_vulnerability_critical' => $request['reference'][0]['vulnerability_critical'],
                'initial_vulnerability_major' => $request['reference'][0]['vulnerability_major'],
                'initial_code_smell_blocker' => $request['reference'][0]['code_smell_blocker'],
                'initial_code_smell_critical' => $request['reference'][0]['code_smell_critical'],
                'initial_code_smell_major' => $request['reference'][0]['code_smell_major'],
                'initial_hotspot_total' => $request['reference'][0]['hotspot_total'],
                'initial_couverture' => $request['reference'][0]['couverture'],
                'initial_sqale_debt_ratio' => $request['reference'][0]['sqale_debt_ratio']

            ];
    }

    /**
     * [Description for repartition]
     * On calcule le nombre de défaut par module
     *
     * @param string $mavenKey
     * @param array $contents
     *
     * @return array
     *
     * Created at: 15/12/2022, 22:17:19 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function repartition($mavenKey, $contents): array
    {
        $frontend = $backend = $autre = 0;

        /**
         * fr.ma-petite-entreprise:ma-moulinette : valeur par défaut pour les Tests
         */
        if (is_null($mavenKey)) {
            $mavenKey = "fr.ma-petite-entreprise:ma-moulinette";
        }

        $app=static::extractNameFromMavenKey($mavenKey);

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
            if ($module[0] === "du-presentation" ||
                $module[0] === "rs-presentation") {
                $frontend = $frontend + 1;
            }
            if ($module[0] === $app . "-presentation" ||
                $module[0] === $app . "-presentation-commun" ||
                $module[0] === $app . "-presentation-ear" ||
                $module[0] === $app . "-webapp") {
                $frontend = $frontend + 1;
            }
            if ($module[0] === "rs-metier") {
                $backend = $backend + 1;
            }
            if ($module[0] === $app . "-metier" ||
                $module[0] === $app . "-common" ||
                $module[0] === $app . "-api" ||
                $module[0] === $app . "-dao") {
                $backend = $backend + 1;
            }
            if ($module[0] === $app . "-metier-ear" ||
                $module[0] === $app . "-service" ||
                $module[0] === $app . "-serviceweb" ||
                $module[0] === $app . "-middleoffice") {
                $backend = $backend + 1;
            }
            if ($module[0] === $app . "-metier-rest" ||
                $module[0] === $app . "-entite" ||
                $module[0] === $app . "-serviceweb-client") {
                $backend = $backend + 1;
            }
            if ($module[0] === $app . "-batch" ||
                $module[0] === $app . "-batchs" ||
                $module[0] === $app . "-batch-envoi-dem-aval" ||
                $module[0] === $app . "-batch-import-billets") {
                $autre = $autre + 1;
            }
            if ($module[0] === $app . "-rdd") {
                $autre = $autre + 1;
            }
        }
        return ['frontend' => $frontend, 'backend' => $backend, 'autre' => $autre];
    }

    /**
     * [Description for traitement]
     *
     * @param string $mavenKey
     * @param string $setup
     * @param string $type
     * @param string $severity
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
        $liste = $this->mr->getRepository(Repartition::class, 'secondary')
            ->findBy(
                [
                    'type' => $type,
                    'severity' => $severity,
                    'setup' => $setup
                ]
        );

        return self::repartition($mavenKey, $liste);
    }


    /**
     * [Description for variation]
     * Calcul de la variation entre deux notes
     *
     * @param integer $a
     * @param integer $b
     *
     * @return string
     *
     * Created at: 15/12/2022, 22:17:57 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function variation($a, $b): string
    {
        /** Pas dévolution ou la valeur */
        if ($a == -1 && $b == 0) {
            return"equal";
        }

        if ($a == 0) {
            $c = 0;
            return "equal";
        } else {

            $c = (($b - $a) / $a) * 100;
        }

        switch ($c) {
            case $c > 0:
                $evolution = "down";
                break;
            case $c < 0:
                $evolution = "up";
                break;
            default:
                echo "Le taux de variation ne peut pas être calculé.";
        }
        return $evolution;
    }

    /**
     * [Description for projetCosui]
     * On ouvre la page COSUI
     *
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
        $mode = 'null';
        if (!is_null($request->get('mode'))) {
            $mode = $request->get('mode');
        }

        /** On bind les variables */
        $mavenKey = $request->get('mavenKey');

        /** On récupère les notes */
        $n = static::notes($mavenKey, $mode);

        if ($n['resultat'] === false) {
            $nameApplication = $versionApplication = $typeApplication = 'NaN';
            $dateApplication = '01/01/1980';
            $noteCodeSmell = $noteReliability = $noteSecurity = $noteHotspot = 'F';
            $nombreMetierCodeSmellBlocker = $nombreMetierCodeSmellCritical = $nombreMetierCodeSmellMajor = 0;
            $nombrePresentationCodeSmellBlocker = $nombrePresentationCodeSmellCritical = 0;
            $nombrePresentationCodeSmellMajor = 0;
            $nombreMetierReliabilityBlocker = $nombreMetierReliabilityCritical = $nombreMetierReliabilityMajor = 0;
            $bugBlocker = $bugCritical = $bugMajor = 0;
            $vulnerabilityBlocker = $vulnerabilityCritical = $vulnerabilityMajor = 0;
            $codeSmellBlocker = $codeSmellCritical = $codeSmellMajor = 0;
            $hotspot = $couverture = $sqaleDebtRatio = 0;

            $nombrePresentationReliabilityBlocker = $nombrePresentationReliabilityCritical = 0;
            $nombrePresentationReliabilityMajor = 0;

            $nombrePresentationVulnerabilityBlocker = $nombrePresentationVulnerabilityCritical = 0;
            $nombrePresentationVulnerabilityMajor = 0;
            $nombreMetierVulnerabilityBlocker = $nombreMetierVulnerabilityCritical = 0;
            $nombreMetierVulnerabilityMajor = 0;

            $message = "[COSUI-001] Il n'y a pas de données dans la babase !";
            $this->addFlash('alert', $message);
        } else {
            $nameApplication = $n['name'];
            $versionApplication = $n['version'];
            $typeApplication = $n['type'];
            $dateApplication = $n['date_version'];
            $noteCodeSmell = $n['note_code_smell'];
            $noteReliability = $n['note_reliability'];
            $noteSecurity = $n['note_security'];
            $noteHotspot = $n['note_hotspot'];
            $bugBlocker = $n['bug_blocker'];
            $bugCritical = $n['bug_critical'];
            $bugMajor = $n['bug_major'];
            $vulnerabilityBlocker = $n['vulnerability_blocker'];
            $vulnerabilityCritical = $n['vulnerability_critical'];
            $vulnerabilityMajor = $n['vulnerability_major'];
            $codeSmellBlocker = $n['code_smell_blocker'];
            $codeSmellCritical = $n['code_smell_critical'];
            $codeSmellMajor = $n['code_smell_major'];
            $hotspot = $n['hotspot_total'];
            $couverture = $n['couverture'];
            $sqaleDebtRatio = $n['sqale_debt_ratio'];
        }

        /** On récupères les indicateurs de la version de référence */
        $nn = self::reference($mavenKey, $mode);
        if ($nn['resultat'] === false) {
            $initialVersionApplication = 'NaN';
            $initialDateApplication = '01/01/1980';
            $initialNoteCodeSmell = $initialNoteReliability = 'F';
            $initialNoteSecurity = $initialNoteHotspot = 'F';
            $initialBugBlocker = $initialBugCritical = $initialBugMajor = 0;
            $initialVulnerabilityBlocker = $initialVulnerabilityCritical = $initialVulnerabilityMajor = 0;
            $initialCodeSmellBlocker = $initialCodeSmellCritical = $initialCodeSmellMajor = 0;
            $initialCouverture = $initialHotspot = 0;
            $initialSqaleDebtRatio = 100;
            $message = "[COSUI-002] Vous devez choisir un projet comme référence !";
            $this->addFlash('alert', $message);
        } else {
            $initialVersionApplication = $nn['initial_version_application'];
            $initialDateApplication = $nn['initial_date_version'];
            $initialNoteCodeSmell = $nn['initial_note_code_smell'];
            $initialNoteReliability = $nn['initial_note_reliability'];
            $initialNoteSecurity = $nn['initial_note_security'];
            $initialNoteHotspot = $nn['initial_note_hotspot'];
            $initialBugBlocker = $nn['initial_bug_blocker'];
            $initialBugCritical = $nn['initial_bug_critical'];
            $initialBugMajor = $nn['initial_bug_major'];
            $initialVulnerabilityBlocker = $nn['initial_vulnerability_blocker'];
            $initialVulnerabilityCritical = $nn['initial_vulnerability_critical'];
            $initialVulnerabilityMajor = $nn['initial_vulnerability_major'];
            $initialCodeSmellBlocker = $nn['initial_code_smell_blocker'];
            $initialCodeSmellCritical = $nn['initial_code_smell_critical'];
            $initialCodeSmellMajor = $nn['initial_code_smell_major'];
            $initialHotspot = $nn['initial_hotspot_total'];
            $initialCouverture = $nn['initial_couverture'];
            $initialSqaleDebtRatio = $nn['initial_sqale_debt_ratio'];
        }
        /** on récupère le dernier setup pour le projet */
        $setup = self::setup($mavenKey);

        /** On récupère la répartition pour l'application backend */
        /** Fiabilité Blocker */
        $fiabilite01 = self::traitement($mavenKey, $setup, 'BUG', 'BLOCKER');
        $nombrePresentationReliabilityBlocker = $fiabilite01['frontend'];
        $nombreMetierReliabilityBlocker = $fiabilite01['backend'];

        /** Fiabilité Critical */
        $fiabilite02 = self::traitement($mavenKey, $setup, 'BUG', 'CRITICAL');
        $nombrePresentationReliabilityCritical = $fiabilite02['frontend'];
        $nombreMetierReliabilityCritical = $fiabilite02['backend'];

        /** Fiabilité Major */
        $fiabilite03 = self::traitement($mavenKey, $setup, 'BUG', 'MAJOR');
        $nombrePresentationReliabilityMajor = $fiabilite03['frontend'];
        $nombreMetierReliabilityMajor = $fiabilite03['backend'];

        /** Vulnérabilité Blocker */
        $vulnerabilite01 = self::traitement($mavenKey, $setup, 'VULNERABILITY', 'BLOCKER');
        $nombrePresentationVulnerabilityBlocker = $vulnerabilite01['frontend'];
        $nombreMetierVulnerabilityBlocker = $vulnerabilite01['backend'];

        /** Vulnérabilité Critical */
        $vulnerabilite02 = self::traitement($mavenKey, $setup, 'VULNERABILITY', 'CRITICAL');
        $nombrePresentationVulnerabilityCritical = $vulnerabilite02['frontend'];
        $nombreMetierVulnerabilityCritical = $vulnerabilite02['backend'];

        /** Vulnérabilité Major */
        $vulnerabilite03 = self::traitement($mavenKey, $setup, 'VULNERABILITY', 'MAJOR');
        $nombrePresentationVulnerabilityMajor = $vulnerabilite03['frontend'];
        $nombreMetierVulnerabilityMajor = $vulnerabilite03['backend'];

        /** Maintenabilité Bloqant*/
        $codeSmell01 = self::traitement($mavenKey, $setup, 'CODE_SMELL', 'BLOCKER');
        $nombrePresentationCodeSmellBlocker = $codeSmell01['frontend'];
        $nombreMetierCodeSmellBlocker = $codeSmell01['backend'];

        /** Maintenabilité Critical */
        $codeSmell02 = self::traitement($mavenKey, $setup, 'CODE_SMELL', 'CRITICAL');
        $nombrePresentationCodeSmellCritical = $codeSmell02['frontend'];
        $nombreMetierCodeSmellCritical = $codeSmell02['backend'];

        /** Maintenabilité Major */
        $codeSmell03 = self::traitement($mavenKey, $setup, 'CODE_SMELL', 'MAJOR');
        $nombrePresentationCodeSmellMajor = $codeSmell03['frontend'];
        $nombreMetierCodeSmellMajor = $codeSmell03['frontend'];

        /** On calcul l'évolution pour chaque indicateur par rapport
         *  aux notes de référence.
         */

        /** Calcul de la variation des hotspots */
        $evolutionHotspot = self::variation($initialHotspot, $hotspot);

        /** Calcul de la variation des mauvaises pratiques */
        $evolutionCodeSmellBlocker = self::variation($initialCodeSmellBlocker, $codeSmellBlocker);
        $evolutionCodeSmellCritical = self::variation($initialCodeSmellCritical, $codeSmellCritical);
        $evolutionCodeSmellMajor = self::variation($initialCodeSmellMajor, $codeSmellMajor);

        /** Calcul de la variation des vulnérabilités */
        $evolutionVulnerabilityBlocker = self::variation($initialVulnerabilityBlocker, $vulnerabilityBlocker);
        $evolutionVulnerabilityCritical = self::variation($initialVulnerabilityCritical, $vulnerabilityCritical);
        $evolutionVulnerabilityMajor = self::variation($initialVulnerabilityMajor, $vulnerabilityMajor);

        /** Calcul de la variation des vulnérabilités */
        $evolutionBugBlocker = self::variation($initialBugBlocker, $bugBlocker);
        $evolutionBugCritical = self::variation($initialBugCritical, $bugCritical);
        $evolutionBugMajor = self::variation($initialBugMajor, $bugMajor);

        /** serie pour la version de référence et la version courrante */
        // ['Fiabilité','Vulnérabilité','Hotspot','Maintenabilité','Couverture','Dette']
        // >100, 70, 50 30 10

        /** On calcul la valeur des notes pour le Radar */
        $idata1=$idata2=$idata3=$idata4=$idata5=$idata6=0;
        $idata1=static::note2point($initialNoteReliability);
        $idata2=static::note2point($initialNoteSecurity);
        $idata3=static::note2point($initialNoteHotspot);
        $idata4=static::note2point($initialNoteCodeSmell);
        $idata5=$initialCouverture;

        /** On inverse la courbe, plus le résultat est proche de 100 et plus la dette est petite */
        $idata6=100-$initialSqaleDebtRatio;

        /* si la dette technique est > à 100M alors le ration est de 100% */
        if ($initialSqaleDebtRatio>100) {
            $idata6=100;
        }
        /** Si on a pad de données pour l'indicateur on fixe le niveau à 50 */
        if ($initialSqaleDebtRatio<0) {
            $idata6=50;
        }

        $data1=$data2=$data3=$data4=$data5=$data6=0;
        $data1=static::note2point($noteReliability);
        $data2=static::note2point($noteSecurity);
        $data3=static::note2point($noteHotspot);
        $data4=static::note2point($noteCodeSmell);
        $data5=$couverture;

        /** On inverse la courbe, plus le résultat est proche de 100 et plus la dette est petite */
        $data6=100-$sqaleDebtRatio;

        /* si la dette technique est > à 100M alors le ration est de 100% */
        if ($sqaleDebtRatio>100) {
            $data6=100;
        }
        /** Si on a pad de données pour l'indicateur on fixe le niveau à 50 */
        if ($sqaleDebtRatio<0) {
            $data6=50;
        }

        /** On constitue les dataSet */
        $dataSet1="$idata1, $idata2, $idata3, $idata4, $idata5, $idata6";
        $dataSet2="$data1, $data2, $data3, $data4, $data5, $data6";

        $render = [
            'dataset1'=>$dataSet1, 'dataset2'=>$dataSet2,
            'label1'=>$initialVersionApplication, 'label2'=>$versionApplication,
            'setup' => $setup, 'monApplication' => $nameApplication, 'version_application' => $versionApplication,
            'type_application' => $typeApplication, 'date_application' => $dateApplication,
            'note_code_smell' => $noteCodeSmell, 'note_reliability' => $noteReliability,
            'note_security' => $noteSecurity, 'note_hotspot' => $noteHotspot, 'bug_blocker' => $bugBlocker,
            'bug_critical' => $bugCritical, 'bug_major' => $bugMajor, 'vulnerability_blocker' => $vulnerabilityBlocker,
            'vulnerability_critical' => $vulnerabilityCritical, 'vulnerability_major' => $vulnerabilityMajor,
            'code_smell_blocker' => $codeSmellBlocker, 'code_smell_critical' => $codeSmellCritical,
            'code_smell_major' => $codeSmellMajor, 'hotspot' => $hotspot,
            'initial_version_application' => $initialVersionApplication,
            'initial_date_application' => $initialDateApplication,
            'initial_note_code_smell' => $initialNoteCodeSmell, 'initial_note_reliability' => $initialNoteReliability,
            'initial_note_security' => $initialNoteSecurity, 'initial_note_hotspot' => $initialNoteHotspot,
            'initial_bug_blocker' => $initialBugBlocker, 'initial_bug_critical' => $initialBugCritical,
            'initial_bug_major' => $initialBugMajor, 'initial_vulnerability_blocker' => $initialVulnerabilityBlocker,
            'initial_vulnerability_critical' => $initialVulnerabilityCritical,
            'initial_vulnerability_major' => $initialVulnerabilityMajor,
            'initial_code_smell_blocker' => $initialCodeSmellBlocker,
            'initial_code_smell_critical' => $initialCodeSmellCritical,
            'initial_code_smell_major' => $initialCodeSmellMajor, 'evolution_bug_blocker' => $evolutionBugBlocker,
            'evolution_bug_critical' => $evolutionBugCritical, 'evolution_bug_major' => $evolutionBugMajor,
            'evolution_vulnerability_blocker' => $evolutionVulnerabilityBlocker,
            'evolution_vulnerability_critical' => $evolutionVulnerabilityCritical,
            'evolution_vulnerability_major' => $evolutionVulnerabilityMajor,
            'evolution_code_smell_blocker' => $evolutionCodeSmellBlocker,
            'evolution_code_smell_critical' => $evolutionCodeSmellCritical,
            'evolution_code_smell_major' => $evolutionCodeSmellMajor, 'evolution_hotspot' => $evolutionHotspot,
            'modal_initial_bug_blocker' => $initialBugBlocker, 'modal_initial_bug_critical' => $initialBugCritical,
            'modal_initial_bug_major' => $initialBugMajor,
            'modal_initial_vulnerability_blocker' => $initialVulnerabilityBlocker,
            'modal_initial_vulnerability_critical' => $initialVulnerabilityCritical,
            'modal_initial_vulnerability_major' => $initialVulnerabilityMajor,
            'modal_initial_code_smell_blocker' => $initialCodeSmellBlocker,
            'modal_initial_code_smell_critical' => $initialCodeSmellCritical,
            'modal_initial_code_smell_major' => $initialCodeSmellMajor, 'modal_initial_hotspot' => $initialHotspot,
            'nombre_metier_code_smell_blocker' => $nombreMetierCodeSmellBlocker,
            'nombre_metier_code_smell_critical' => $nombreMetierCodeSmellCritical,
            'nombre_metier_code_smell_major' => $nombreMetierCodeSmellMajor,
            'nombre_presentation_code_smell_blocker' => $nombrePresentationCodeSmellBlocker,
            'nombre_presentation_code_smell_critical' => $nombrePresentationCodeSmellCritical,
            'nombre_presentation_code_smell_major' => $nombrePresentationCodeSmellMajor,
            'nombre_metier_reliability_blocker' => $nombreMetierReliabilityBlocker,
            'nombre_metier_reliability_critical' => $nombreMetierReliabilityCritical,
            'nombre_metier_reliability_major' => $nombreMetierReliabilityMajor,
            'nombre_presentation_reliability_blocker' => $nombrePresentationReliabilityBlocker,
            'nombre_presentation_reliability_critical' => $nombrePresentationReliabilityCritical,
            'nombre_presentation_reliability_major' => $nombrePresentationReliabilityMajor,
            'nombre_metier_vulnerability_blocker' => $nombreMetierVulnerabilityBlocker,
            'nombre_metier_vulnerability_critical' => $nombreMetierVulnerabilityCritical,
            'nombre_metier_vulnerability_major' => $nombreMetierVulnerabilityMajor,
            'nombre_presentation_vulnerability_blocker' => $nombrePresentationVulnerabilityBlocker,
            'nombre_presentation_vulnerability_critical' => $nombrePresentationVulnerabilityCritical,
            'nombre_presentation_vulnerability_major' => $nombrePresentationVulnerabilityMajor,
            'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y'),
            'mode' => $mode,  Response::HTTP_OK
        ];

        if ($mode === 'TEST') {
            array_push($render, ['notes' => $n]);
            return $response->setData($render);
        } else {
            return $this->render('projet/cosui.html.twig', $render);
        }
    }
}
