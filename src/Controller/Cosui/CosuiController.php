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

namespace App\Controller\Cosui;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** Accès aux tables */
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Historique;
use App\Entity\Repartition;

/** Import des services */
use App\Service\ExtractName;

class CosuiController extends AbstractController
{

    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

    /**
     * [Description for __construct]
     *
     * Created at: 13/02/2023, 08:57:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ManagerRegistry $mr,
        private ExtractName $serviceExtractName,
        private ParameterBagInterface $params)
    {
        $this->em = $em;
        $this->mr = $mr;
        $this->serviceExtractName = $serviceExtractName;
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
        /** On se connecte à la base pour connaître la version du dernier setup pour le projet. */
        $response = $this->mr->getRepository(Repartition::class)
                    ->findBy(['mavenKey' => $mavenKey], ['setup' => 'DESC'], 1);

        $setup = "NaN";
        if (!empty($response)) {
            $setup = $response[0]->getSetup();
        }

        return $setup;
    }

    /**
     * [Description for notes]
     * On récupère les indicateurs du bloc information pour le projet
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 15/12/2022, 22:16:32 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function notes($mavenKey): array
    {
        /** On instancie l'entityRepository */
        $historique = $this->em->getRepository(Historique::class);

        /** On récupère les informations du projet de la table historique */
        $map=['maven_key'=>$mavenKey];
        $request=$historique->selectHistoriqueProjetLast($map);
        if ($request['code']!=200) {
            return [
                    'maven_key' => $mavenKey,'code'=>$request['code'],
                    'erreur' => $request['erreur']
                ];
        }

        if (!$request['infos']) {
            return ['result' => false];
        }

        /**
         * On sépare la version du type de version
         * On considère que la version est version-type
         * Par exemple : 1.0.0-Release
         */
        $tempo = explode("-", $request['infos'][0]['version']);

        return ['result' => true,
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
                'nombre_hotspot' => $request['infos'][0]['nombre_hotspot'],
                'coverage' => $request['infos'][0]['coverage'],
                'sqale_debt_ratio' => $request['infos'][0]['sqale_debt_ratio']
            ];
    }

    /**
     * [Description for reference]
     * On récupère les informations du projet de référence.
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 15/12/2022, 22:16:56 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function reference($mavenKey): array
    {
        /** On instancie l'entityRepository */
        $historique = $this->em->getRepository(Historique::class);

        /** On récupère les informations du projet de référence */
        $map=['maven_key'=>$mavenKey];
        $request=$historique->selectHistoriqueProjetReference($map);
        if ($request['code']!=200) {
            return [
                    'maven_key' => $mavenKey,'code'=>$request['code'],
                    'erreur' => $request['erreur']
                    ];
        }
        if (!$request['reference']) {
            return ['result' => false];
        }

        $tempo = explode("-", $request['reference'][0]['version']);
        return ['result' => true,
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
                'initial_nombre_hotspot' => $request['reference'][0]['nombre_hotspot'],
                'initial_coverage' => $request['reference'][0]['coverage'],
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

        $app=$this->serviceExtractName->extractNameFromMavenKey($mavenKey);

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
        if ($setup==='NaN'){ return []; }

        /**
         * On récupère la liste
         * Type : BUG, VULNERABILITY, CODE_SMELL
         * Severity : BLOCKER, CRITICAL, MAJOR,..
         */
        $liste = $this->mr->getRepository(Repartition::class)
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
     * [Description for variationDirection]
     *
     * @param integer $ancienneValeur
     * @param integer $nouvelleValeur
     *
     * @return string
     * Created at: 10/05/2024 16:24:07 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function variation($ancienneValeur, $nouvelleValeur)
    {
        $result = 0;
        if ($ancienneValeur === 0) {
            return "equal";
        }
        $result = (($nouvelleValeur - $ancienneValeur) / $ancienneValeur) * 100;
        if ($result > 0) {
            $response = 'down';
        }
        if ($result < 0) {
            $response = 'up';
        }
        return $response;
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
        /** On charge le template du render */
        $render=static::genericRender();

        /** On bind les variables */
        $mavenKey = $request->get('mavenKey');

        /** On récupère les notes */
        $n = static::notes($mavenKey);

        if ($n['result'] === false) {
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
            $hotspot = $coverage = $sqaleDebtRatio = 0;

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
            $hotspot = $n['nombre_hotspot'];
            $coverage = $n['coverage'];
            $sqaleDebtRatio = $n['sqale_debt_ratio'];
        }

        $render['monApplication'] = $nameApplication;
        $render['version_application'] = $versionApplication;
        $render['type_application'] = $typeApplication;
        $render['date_application'] = $dateApplication;

        $render['bug_blocker'] = $bugBlocker;
        $render['bug_critical'] = $bugCritical;
        $render['bug_major'] = $bugMajor;

        $render['vulnerability_blocker'] = $vulnerabilityBlocker;
        $render['vulnerability_critical'] = $vulnerabilityCritical;
        $render['vulnerability_major'] = $vulnerabilityMajor;

        $render['code_smell_blocker'] = $codeSmellBlocker;
        $render['code_smell_critical'] = $codeSmellCritical;
        $render['code_smell_major'] = $codeSmellMajor;

        $render['hotspot'] = $hotspot;

        $render['note_code_smell'] = $noteCodeSmell;
        $render['note_reliability'] = $noteReliability;
        $render['note_security'] = $noteSecurity;
        $render['note_hotspot'] = $noteHotspot;

        /** On récupères les indicateurs de la version de référence */
        $nn = self::reference($mavenKey);
        if ($nn['result'] === false) {
            $initialVersionApplication = 'NaN';
            $initialDateApplication = '01/01/1980';
            $initialNoteCodeSmell = $initialNoteReliability = 'F';
            $initialNoteSecurity = $initialNoteHotspot = 'F';
            $initialBugBlocker = $initialBugCritical = $initialBugMajor = 0;
            $initialVulnerabilityBlocker = $initialVulnerabilityCritical = $initialVulnerabilityMajor = 0;
            $initialCodeSmellBlocker = $initialCodeSmellCritical = $initialCodeSmellMajor = 0;
            $initialCoverage = $initialHotspot = 0;
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
            $initialHotspot = $nn['initial_nombre_hotspot'];
            $initialCoverage = $nn['initial_coverage'];
            $initialSqaleDebtRatio = $nn['initial_sqale_debt_ratio'];
        }

        /** on prépare les données pour la vue */
        $render['initial_version_application'] = $initialVersionApplication;
        $render['initial_date_application'] = $initialDateApplication;
        $render['initial_note_code_smell'] = $initialNoteCodeSmell;
        $render['initial_note_reliability'] = $initialNoteReliability;
        $render['initial_note_security'] = $initialNoteSecurity;
        $render['initial_note_hotspot'] = $initialNoteHotspot;
        $render['initial_bug_blocker'] = $initialBugBlocker;
        $render['initial_bug_critical'] = $initialBugCritical;
        $render['initial_bug_major'] = $initialBugMajor;
        $render['initial_vulnerability_blocker'] = $initialVulnerabilityBlocker;
        $render['initial_vulnerability_critical'] = $initialVulnerabilityCritical;
        $render['initial_vulnerability_major'] = $initialVulnerabilityMajor;
        $render['initial_code_smell_blocker'] = $initialCodeSmellBlocker;
        $render['initial_code_smell_critical'] = $initialCodeSmellCritical;
        $render['initial_code_smell_major'] = $initialCodeSmellMajor;

        $render['modal_initial_bug_blocker'] = $initialBugBlocker;
        $render['modal_initial_bug_critical'] = $initialBugCritical;
        $render['modal_initial_bug_major'] = $initialBugMajor;
        $render['modal_initial_vulnerability_blocker'] = $initialVulnerabilityBlocker;
        $render['modal_initial_vulnerability_critical'] = $initialVulnerabilityCritical;
        $render['modal_initial_vulnerability_major'] = $initialVulnerabilityMajor;
        $render['modal_initial_code_smell_blocker'] = $initialCodeSmellBlocker;
        $render['modal_initial_code_smell_critical'] = $initialCodeSmellCritical;
        $render['modal_initial_code_smell_major'] = $initialCodeSmellMajor;
        $render['modal_initial_hotspot'] = $initialHotspot;

        /** on récupère le dernier setup pour le projet, revoie NaN si il n'y en a pas. */
        $setup = self::setup($mavenKey);
        $render['setup'] = $setup;

        /** On récupère la répartition pour l'application backend */
        /** Fiabilité Blocker */
        $viability01 = self::traitement($mavenKey, $setup, 'BUG', 'BLOCKER');
        $nombrePresentationReliabilityBlocker = $viability01['frontend'] ?? '--';
        $nombreMetierReliabilityBlocker = $viability01['backend'] ?? '--';
        $render['nombre_presentation_reliability_blocker'] = $nombrePresentationReliabilityBlocker;
        $render['nombre_metier_reliability_blocker'] = $nombreMetierReliabilityBlocker;

        /** Fiabilité Critical */
        $viability02 = self::traitement($mavenKey, $setup, 'BUG', 'CRITICAL');
        $nombrePresentationReliabilityCritical = $viability02['frontend'] ?? '--';
        $nombreMetierReliabilityCritical = $viability02['backend'] ?? '--';
        $render['nombre_presentation_reliability_critical'] = $nombrePresentationReliabilityCritical;
        $render['nombre_metier_reliability_critical'] = $nombreMetierReliabilityCritical;

        /** Fiabilité Major */
        $viability03 = self::traitement($mavenKey, $setup, 'BUG', 'MAJOR');
        $nombrePresentationReliabilityMajor = $viability03['frontend'] ?? '--';
        $nombreMetierReliabilityMajor = $viability03['backend'] ?? '--';
        $render['nombre_presentation_reliability_major'] = $nombrePresentationReliabilityMajor;
        $render['nombre_metier_reliability_major'] = $nombreMetierReliabilityMajor;

        /** Vulnérabilité Blocker */
        $vulnerability01 = self::traitement($mavenKey, $setup, 'VULNERABILITY', 'BLOCKER');
        $nombrePresentationVulnerabilityBlocker = $vulnerability01['frontend'] ?? '--';
        $nombreMetierVulnerabilityBlocker = $vulnerability01['backend'] ?? '--';
        $render['nombre_presentation_vulnerability_blocker'] = $nombrePresentationVulnerabilityBlocker;
        $render['nombre_metier_vulnerability_blocker'] = $nombreMetierVulnerabilityBlocker;

        /** Vulnérabilité Critical */
        $vulnerability02 = self::traitement($mavenKey, $setup, 'VULNERABILITY', 'CRITICAL');
        $nombrePresentationVulnerabilityCritical = $vulnerability02['frontend'] ?? '--';
        $nombreMetierVulnerabilityCritical = $vulnerability02['backend'] ?? '--';
        $render['nombre_presentation_vulnerability_critical'] = $nombrePresentationVulnerabilityCritical;
        $render['nombre_metier_vulnerability_critical'] = $nombreMetierVulnerabilityCritical;

        /** Vulnérabilité Major */
        $vulnerability03 = self::traitement($mavenKey, $setup, 'VULNERABILITY', 'MAJOR');
        $nombrePresentationVulnerabilityMajor = $vulnerability03['frontend'] ?? '--';
        $nombreMetierVulnerabilityMajor = $vulnerability03['backend'] ?? '--';
        $render['nombre_presentation_vulnerability_major'] = $nombrePresentationVulnerabilityMajor;
        $render['nombre_metier_vulnerability_major'] = $nombreMetierVulnerabilityMajor;

        /** Maintenabilité Bloquant*/
        $codeSmell01 = self::traitement($mavenKey, $setup, 'CODE_SMELL', 'BLOCKER');
        $nombrePresentationCodeSmellBlocker = $codeSmell01['frontend'] ?? '--';
        $nombreMetierCodeSmellBlocker = $codeSmell01['backend'] ?? '--';
        $render['nombre_presentation_code_smell_blocker'] = $nombrePresentationCodeSmellBlocker;
        $render['nombre_metier_code_smell_blocker'] = $nombreMetierCodeSmellBlocker;

        /** Maintenabilité Critical */
        $codeSmell02 = self::traitement($mavenKey, $setup, 'CODE_SMELL', 'CRITICAL');
        $nombrePresentationCodeSmellCritical = $codeSmell02['frontend'] ?? '--';
        $nombreMetierCodeSmellCritical = $codeSmell02['backend'] ?? '--';
        $render['nombre_presentation_code_smell_critical'] = $nombrePresentationCodeSmellCritical;
        $render['nombre_metier_code_smell_critical'] = $nombreMetierCodeSmellCritical;

        /** Maintenabilité Major */
        $codeSmell03 = self::traitement($mavenKey, $setup, 'CODE_SMELL', 'MAJOR');
        $nombrePresentationCodeSmellMajor = $codeSmell03['frontend'] ?? '--';
        $nombreMetierCodeSmellMajor = $codeSmell03['frontend'] ?? '--';
        $render['nombre_presentation_code_smell_major'] = $nombrePresentationCodeSmellMajor;
        $render['nombre_metier_code_smell_major'] = $nombreMetierCodeSmellMajor;

        /** On calcul l'évolution pour chaque indicateur par rapport
         *  aux notes de référence.
         */

        /** Calcul de la variation des hotspots */
        $evolutionHotspot = self::variation($initialHotspot, $hotspot);
        $render['evolution_hotspot'] = $evolutionHotspot;

        /** Calcul de la variation des mauvaises pratiques */
        $evolutionCodeSmellBlocker = self::variation($initialCodeSmellBlocker, $codeSmellBlocker);
        $evolutionCodeSmellCritical = self::variation($initialCodeSmellCritical, $codeSmellCritical);
        $evolutionCodeSmellMajor = self::variation($initialCodeSmellMajor, $codeSmellMajor);
        $render['evolution_code_smell_blocker'] = $evolutionCodeSmellBlocker;
        $render['evolution_code_smell_critical'] = $evolutionCodeSmellCritical;
        $render['evolution_code_smell_major'] = $evolutionCodeSmellMajor;

        /** Calcul de la variation des vulnérabilités */
        $evolutionVulnerabilityBlocker = self::variation($initialVulnerabilityBlocker, $vulnerabilityBlocker);
        $evolutionVulnerabilityCritical = self::variation($initialVulnerabilityCritical, $vulnerabilityCritical);
        $evolutionVulnerabilityMajor = self::variation($initialVulnerabilityMajor, $vulnerabilityMajor);
        $render['evolution_vulnerability_blocker'] = $evolutionVulnerabilityBlocker;
        $render['evolution_vulnerability_critical'] = $evolutionVulnerabilityCritical;
        $render['evolution_vulnerability_major'] = $evolutionVulnerabilityMajor;

        /** Calcul de la variation des vulnérabilités */
        $evolutionBugBlocker = self::variation($initialBugBlocker, $bugBlocker);
        $evolutionBugCritical = self::variation($initialBugCritical, $bugCritical);
        $evolutionBugMajor = self::variation($initialBugMajor, $bugMajor);
        $render['evolution_bug_blocker'] = $evolutionBugBlocker;
        $render['evolution_bug_critical'] = $evolutionBugCritical;
        $render['evolution_bug_major'] = $evolutionBugMajor;

        /** série pour la version de référence et la version courante */
        // ['Fiabilité','Vulnérabilité','Hotspot','Maintenabilité','Couverture','Dette']
        // >100, 70, 50 30 10

        /** On calcul la valeur des notes pour le Radar */
        $i_data1=$i_data2=$i_data3=$i_data4=$i_data5=$i_data6=0;
        $i_data1=static::note2point($initialNoteReliability);
        $i_data2=static::note2point($initialNoteSecurity);
        $i_data3=static::note2point($initialNoteHotspot);
        $i_data4=static::note2point($initialNoteCodeSmell);
        $i_data5=$initialCoverage;

        /** On inverse la courbe, plus le résultat est proche de 100 et plus la dette est petite */
        $i_data6=100-$initialSqaleDebtRatio;

        /* si la dette technique est > à 100M alors le ration est de 100% */
        if ($initialSqaleDebtRatio>100) {
            $i_data6=100;
        }
        /** Si on a pad de données pour l'indicateur on fixe le niveau à 50 */
        if ($initialSqaleDebtRatio<0) {
            $i_data6=50;
        }

        $data1=$data2=$data3=$data4=$data5=$data6=0;
        $data1=static::note2point($noteReliability);
        $data2=static::note2point($noteSecurity);
        $data3=static::note2point($noteHotspot);
        $data4=static::note2point($noteCodeSmell);
        $data5=$coverage;

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
        $dataSet1="$i_data1, $i_data2, $i_data3, $i_data4, $i_data5, $i_data6";
        $dataSet2="$data1, $data2, $data3, $data4, $data5, $data6";

        $render['dataset1'] = $dataSet1;
        $render['dataset2'] = $dataSet2;
        $render['label1'] = $initialVersionApplication;
        $render['label2'] = $versionApplication;

        return $this->render('projet/cosui.html.twig', $render);
    }
}
