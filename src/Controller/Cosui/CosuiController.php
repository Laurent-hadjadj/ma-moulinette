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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

use App\Entity\RepartitionTemp;
use App\Entity\Historique;
use App\Entity\Repartition;
use App\Service\ExtractName;

/**
 * [Description CosuiController]
 */
class CosuiController extends AbstractController
{

    private static $titre = '[COSUI]';
    private static $erreur400 = 'La requête est incorrecte (Erreur 400).';
    private static $erreur403 = 'Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).';
    private static $page = 'projet/cosui.html.twig';

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
        $this->params = $params;
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
     * [Description for addFlashAndRender]
     *
     * @param string $type
     * @param string $message
     * @param string $debug
     * @param array $render
     *
     * @return Response
     *
     * Created at: 06/03/2025 12:17:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function addFlashAndRender(string $type, string $message, string $debug, array $render): Response
    {
        $this->addFlash('notice', [
            'type' => $type,
            'titre' => static::$titre,
            'message' => $message,
            'debug' => $debug]);
        return $this->render(static::$page, $render);
    }

    /**
     * [Description for decodeToken]
     *
     * @param string $token
     *
     * @return string|null
     *
     * Created at: 06/03/2025 12:13:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function decodeToken(string $token): ?string
    {
        //token=BGR2ZQL5ZQLjA3kzpv5gLF1go3IfnJ5yqUEyBzkyYJAbLKD=
        //1 - b64=OTE2MDY5MDYwN3xmci5tYS1tb3VsaW5ldHRlOmxlLWNoYXQ=
        //2 - rot13=BGR2ZQL5ZQLjA3kzpv5gLF1go3IfnJ5yqUEyBzkyYJAbLKD=
        $string = str_rot13($token);
        $decoded = base64_decode($string);
        $parts = preg_split("/[|]+/", $decoded);
        return (count($parts) === 2) ? strtolower($parts[1]) : null;
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
                $p = 100;
                break;
            case 'B':
                $p = 80;
                break;
            case 'C':
                $p = 60;
                break;
            case 'D':
                $p = 30;
                break;
            case 'E':
                $p = 10;
                break;
            default:
                $p = 0;
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
        $repo = $this->em->getRepository(Repartition::class);
        $response = $repo->findBy(['mavenKey' => $mavenKey], ['setup' => 'DESC'], 1);
        return (!empty($response)) ? $response[0]->getSetup() : 'NaN';
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
        $historiqueRepository = $this->em->getRepository(Historique::class);

        /** On récupère les informations du projet de la table historique */
        $map = ['maven_key' => $mavenKey];
        $request = $historiqueRepository->selectHistoriqueProjetLast($map);
        if ($request['code'] != 200) {
            return [
                    'maven_key' => $mavenKey, 'code' => $request['code'],
                    'erreur' => $request['erreur']
                ];
        }

        if (!$request['infos']) {
            return ['code' => 200, 'result' => false ];
        }

        /**
         * On sépare la version du type de version
         * On considère que la version est version-type
         * Par exemple : 1.0.0-Release
         */
        $tempo = explode("-", $request['infos'][0]['version']);

        return [
                'code' => 200,
                'result' => true,
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
        $historiqueRepository = $this->em->getRepository(Historique::class);

        /** On récupère les informations du projet de référence */
        $map = ['maven_key'=> $mavenKey];
        $request = $historiqueRepository->selectHistoriqueProjetReference($map);
        if ($request['code'] != 200) {
            return [
                    'maven_key' => $mavenKey, 'code' => $request['code'],
                    'erreur' => $request['erreur']
                    ];
        }
        if (!$request['reference']) {
            return ['code' => 200, 'result' => false];
        }
        $tempo = explode("-", $request['reference'][0]['version']);
        return [
                'code' => 200,
                'result' => true,
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
    private function traitement($mavenKey, $setup): array
    {
        if ($setup === 'NaN'){ return []; }

        /**
         * On récupère la répartition frontend, backend, autre et inconnu
         *  des BUG, VULNERABILITY, CODE_SMELL
         *  pour la severity : BLOCKER, CRITICAL, MAJOR,..
         */
        $qb = $this->mr->createQueryBuilder('r')
            ->select()
            ->where('r.mavenKey = :mavenKey')
            ->andWhere('r.setup = :setup')
            ->setParameter('mavenKey', $mavenKey)
            ->setParameter('setup', $setup);

        return $qb->getQuery()->getArrayResult();
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
     * [Description for initializeNotesRender]
     *
     * @return array
     *
     * Created at: 06/03/2025 13:27:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function initializeNotesRender(): array
    {
        $render['monApplication'] = 'NaN';
        $render['version_application'] = 'NaN';
        $render['type_application'] = 'NaN';
        $render['date_application'] = '01/01/1980';
        $render['setup'] = 'NaN';

        $render['bug_blocker'] = 0;
        $render['bug_critical'] = 0;
        $render['bug_major'] = 0;
        $render['vulnerability_blocker'] = 0;
        $render['vulnerability_critical'] = 0;
        $render['vulnerability_major'] = 0;
        $render['code_smell_blocker'] = 0;
        $render['code_smell_critical'] = 0;
        $render['code_smell_major'] = 0;

        $render['hotspot'] = 0;

        $render['note_code_smell'] = 'F';
        $render['note_reliability'] = 'F';
        $render['note_security'] = 'F';
        $render['note_hotspot'] = 'F';

        $render['coverage'] = 0;
        $render['sqale_debt_ratio'] = 0;

        $render['nombre_presentation_reliability_blocker'] = '--';
        $render['nombre_metier_reliability_blocker'] = '--';
        $render['nombre_presentation_reliability_critical'] = '--';
        $render['nombre_metier_reliability_critical'] = '--';
        $render['nombre_presentation_reliability_major'] = '--';
        $render['nombre_metier_reliability_major'] = '--';
        $render['nombre_presentation_vulnerability_blocker'] = '--';
        $render['nombre_metier_vulnerability_blocker'] = '--';
        $render['nombre_presentation_vulnerability_critical'] = '--';
        $render['nombre_metier_vulnerability_critical'] = '--';
        $render['nombre_presentation_vulnerability_major'] = '--';
        $render['nombre_metier_vulnerability_major'] = '--';
        $render['nombre_presentation_code_smell_blocker'] = '--';
        $render['nombre_metier_code_smell_blocker'] = '--';
        $render['nombre_presentation_code_smell_critical'] = '--';
        $render['nombre_metier_code_smell_critical'] = '--';
        $render['nombre_presentation_code_smell_major'] = '--';
        $render['nombre_metier_code_smell_major'] = '--';
        return $render;
    }

    /**
     * [Description for initializeReferenceRender]
     *
     * @return array
     *
     * Created at: 06/03/2025 13:54:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function initializeReferenceRender(): array
    {
        $render['initial_version_application'] = 'NaN';
        $render['initial_date_application'] = '01/01/1980';
        $render['initial_note_code_smell'] = 'F';
        $render['initial_note_reliability'] = 'F';
        $render['initial_note_security'] = 'F';
        $render['initial_note_hotspot'] = 'F';
        $render['initial_bug_blocker'] = 0;
        $render['initial_bug_critical'] = 0;
        $render['initial_bug_major'] = 0;
        $render['initial_vulnerability_blocker'] = 0;
        $render['initial_vulnerability_critical'] = 0;
        $render['initial_vulnerability_major'] = 0;
        $render['initial_code_smell_blocker'] = 0;
        $render['initial_code_smell_critical'] = 0;
        $render['initial_code_smell_major'] = 0;

        $render['initial_nombre_hotspot'] = 0;

        $render['modal_initial_bug_blocker'] = 0;
        $render['modal_initial_bug_critical'] = 0;
        $render['modal_initial_bug_major'] = 0;
        $render['modal_initial_vulnerability_blocker'] = 0;
        $render['modal_initial_vulnerability_critical'] = 0;
        $render['modal_initial_vulnerability_major'] = 0;
        $render['modal_initial_code_smell_blocker'] = 0;
        $render['modal_initial_code_smell_critical'] = 0;
        $render['modal_initial_code_smell_major'] = 0;
        $render['modal_initial_hotspot'] = 0;

        $render['initial_coverage'] = 0;
        $render['initial_sqale_debt_ratio'] = 100;

        return $render;
    }

    private function initializeEvolutionRender(): array
    {
        /** Calcul de la variation des hotspots */
        $render['evolution_hotspot'] = 0;

        /** Calcul de la variation des mauvaises pratiques */
        $render['evolution_code_smell_blocker'] = 0;
        $render['evolution_code_smell_critical'] = 0;
        $render['evolution_code_smell_major'] = 0;

        /** Calcul de la variation des vulnérabilités */
        $render['evolution_vulnerability_blocker'] = 0;
        $render['evolution_vulnerability_critical'] = 0;
        $render['evolution_vulnerability_major'] = 0;

        /** Calcul de la variation des vulnérabilités */
        $render['evolution_bug_blocker'] = 0;
        $render['evolution_bug_critical'] = 0;
        $render['evolution_bug_major'] = 0;

        return $render;
    }

    /**
     * [Description for initializeChartRender]
     *
     * @return array
     *
     * Created at: 06/03/2025 14:43:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function initializeChartRender(): array
    {
        $render['dataset1'] = "0,0,0,0,0,0";
        $render['dataset2'] = "0,0,0,0,0,0";
        $render['label1'] = 'NaN';
        $render['label2'] = 'NaN';

        return $render;

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
        $render = array_merge(static::genericRender(), static::initializeNotesRender(), static::initializeReferenceRender(), static::initializeEvolutionRender(), static::initializeChartRender());

        /** On récupère le token */
        $token = $request->get('token');
        $debug = '';

        /** On teste si la clé est valide */
        if (empty($token)) {
            return $this->addFlashAndRender('alert', static::$erreur400, $debug, $render);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return $this->addFlashAndRender('warning', static::$erreur403, $debug, $render);
        }

        /** On récupère la maven_key du token */
        $mavenKey = $this->decodeToken($token);
        if (null === $mavenKey) {
            return $this->addFlashAndRender('alert', static::$erreur400, $debug, $render);
        }

        /** On récupère les notes */
        $n = static::notes($mavenKey);
        if ($n['code'] != 200) {
            $message = "Une erreur inattendue s'est produite lors de la récupération des informations pour la clé ". $n['maven_key']. ".";
            return $this->addFlashAndRender('alert', $message, $n['erreur'], $render);
        }

        if ($n['result'] === false) {
            $message = "[COSUI-001] Il n'y a pas de données dans la babase !";
            return $this->addFlashAndRender('alert', $message, $debug, $render);
        } else {
            $render['monApplication'] = $n['name'];
            $render['version_application'] = $n['version'];
            $render['type_application'] = $n['type'];
            $render['date_application'] = $n['date_version'];
            $render['bug_blocker'] = $n['bug_blocker'];
            $render['bug_critical'] = $n['bug_critical'];
            $render['bug_major'] = $n['bug_major'];
            $render['vulnerability_blocker'] = $n['vulnerability_blocker'];
            $render['vulnerability_critical'] = $n['vulnerability_critical'];
            $render['vulnerability_major'] = $n['vulnerability_major'];
            $render['code_smell_blocker'] = $n['code_smell_blocker'];
            $render['code_smell_critical'] = $n['code_smell_critical'];
            $render['code_smell_major'] = $n['code_smell_major'];
            $render['hotspot'] = $n['nombre_hotspot'];
            $render['note_code_smell'] =  $n['note_code_smell'];
            $render['note_reliability'] = $n['note_reliability'];
            $render['note_security'] = $n['note_security'];
            $render['note_hotspot'] = $n['note_hotspot'];
            $render['coverage'] = $n['coverage'];
            $render['sqale_debt_ratio'] = $n['sqale_debt_ratio'];
        }

        /** On récupères les indicateurs de la version de référence */
        $nn = self::reference($mavenKey);
        if ($nn['code'] != 200) {
            $message = " Une erreur inattendue s'est produite lors de la récupération des informations pour la clé ". $nn['maven_key']. ".";
            return $this->addFlashAndRender('alert', $message, $nn['erreur'], $render);
        }
        if ($nn['result'] === false) {
            $message = "[COSUI-002] Vous devez choisir un projet comme référence !";
            return $this->addFlashAndRender('alert', $message, $debug, $render);
        } else {
            $render['initial_version_application'] = $nn['initial_version_application'];
            $render['initial_date_version'] = $nn['initial_date_version'];
            $render['initial_note_code_smell'] = $nn['initial_note_code_smell'];
            $render['initial_note_reliability'] = $nn['initial_note_reliability'];
            $render['initial_note_security'] = $nn['initial_note_security'];
            $render['initial_note_hotspot'] = $nn['initial_note_hotspot'];
            $render['initial_bug_blocker'] = $nn['initial_bug_blocker'];
            $render['initial_bug_critical'] = $nn['initial_bug_critical'];
            $render['initial_bug_major'] = $nn['initial_bug_major'];
            $render['initial_vulnerability_blocker'] = $nn['initial_vulnerability_blocker'];
            $render['initial_vulnerability_critical'] = $nn['initial_vulnerability_critical'];
            $render['initial_vulnerability_major'] = $nn['initial_vulnerability_major'];
            $render['initial_code_smell_blocker'] = $nn['initial_code_smell_blocker'];
            $render['initial_code_smell_critical'] = $nn['initial_code_smell_critical'];
            $render['initial_code_smell_major'] = $nn['initial_code_smell_major'];
            $render['initial_nombre_hotspot'] = $nn['initial_nombre_hotspot'];
            $render['initial_nombre_coverage'] = $nn['initial_coverage'];
            $render['initial_sqale_debt_ratio'] = $nn['initial_sqale_debt_ratio'];

            $render['modal_initial_bug_blocker'] = $nn['initial_bug_blocker'];
            $render['modal_initial_bug_critical'] = $nn['initial_bug_critical'];
            $render['modal_initial_bug_major'] = $nn['initial_bug_major'];
            $render['modal_initial_vulnerability_blocker'] = $nn['initial_vulnerability_blocker'];
            $render['modal_initial_vulnerability_critical'] = $nn['initial_vulnerability_critical'];
            $render['modal_initial_vulnerability_major'] = $nn['initial_vulnerability_major'];
            $render['modal_initial_code_smell_blocker'] = $nn['initial_code_smell_blocker'];
            $render['modal_initial_code_smell_critical'] = $nn['initial_code_smell_critical'];
            $render['modal_initial_code_smell_major'] = $nn['initial_code_smell_major'];
            $render['modal_initial_hotspot'] = $nn['initial_nombre_hotspot'];
        }

        /** On récupère le dernier setup pour le projet, revoie NaN si il n'y en a pas. */
        $setup = self::setup($mavenKey);
        $render['setup'] = $setup;

        /** On récupère la répartition pour l'application backend */
        $liste = self::traitement($mavenKey, $setup);
     
        // Fiabilité Blocker
        $render['nombre_presentation_reliability_blocker'] = $viability01['frontend'] ?? '--';
        $render['nombre_metier_reliability_blocker'] = $viability01['backend'] ?? '--';
        $render['nombre_autre_reliability_blocker'] = $viability01['autre'] ?? '--';
        $render['nombre_inconnu_reliability_blocker'] = $viability01['inconnu'] ?? '--';

        // Fiabilité Critical
        $viability02 = self::traitement($mavenKey, $setup, 'BUG', 'CRITICAL');
        $render['nombre_presentation_reliability_critical'] = $viability02['frontend'] ?? '--';
        $render['nombre_metier_reliability_critical'] = $viability02['backend'] ?? '--';
        $render['nombre_autre_reliability_critical'] = $viability02['autre'] ?? '--';
        $render['nombre_inconnu_reliability_critical'] = $viability02['inconnu'] ?? '--';

        // Fiabilité Major
        $viability03 = self::traitement($mavenKey, $setup, 'BUG', 'MAJOR');
        $render['nombre_presentation_reliability_major'] = $viability03['frontend'] ?? '--';
        $render['nombre_metier_reliability_major'] = $viability03['backend'] ?? '--';
        $render['nombre_autre_reliability_major'] = $viability03['autre'] ?? '--';
        $render['nombre_inconnu_reliability_major'] = $viability03['inconnu'] ?? '--';

        // Vulnérabilité Blocker
        $vulnerability01 = self::traitement($mavenKey, $setup, 'VULNERABILITY', 'BLOCKER');
        $render['nombre_presentation_vulnerability_blocker'] = $vulnerability01['frontend'] ?? '--';
        $render['nombre_metier_vulnerability_blocker'] = $vulnerability01['backend'] ?? '--';
        $render['nombre_autre_vulnerability_blocker'] = $vulnerability01['autre'] ?? '--';
        $render['nombre_inconnu_vulnerability_blocker'] = $vulnerability01['inconnu'] ?? '--';

        // Vulnérabilité Critical
        $vulnerability02 = self::traitement($mavenKey, $setup, 'VULNERABILITY', 'CRITICAL');
        $render['nombre_presentation_vulnerability_critical'] = $vulnerability02['frontend'] ?? '--';
        $render['nombre_metier_vulnerability_critical'] = $vulnerability02['backend'] ?? '--';
        $render['nombre_autre_vulnerability_critical'] = $vulnerability02['autre'] ?? '--';
        $render['nombre_inconnu_vulnerability_critical'] = $vulnerability02['inconnu'] ?? '--';

        // Vulnérabilité Major
        $vulnerability03 = self::traitement($mavenKey, $setup, 'VULNERABILITY', 'MAJOR');
        $render['nombre_presentation_vulnerability_major'] = $vulnerability03['frontend'] ?? '--';
        $render['nombre_metier_vulnerability_major'] = $vulnerability03['backend'] ?? '--';
        $render['nombre_autre_vulnerability_major'] = $vulnerability03['autre'] ?? '--';
        $render['nombre_inconnu_vulnerability_major'] = $vulnerability03['inconnu'] ?? '--';

        // Maintenabilité Blocker
        $codeSmell01 = self::traitement($mavenKey, $setup, 'CODE_SMELL', 'BLOCKER');
        $render['nombre_presentation_code_smell_blocker'] = $codeSmell01['frontend'] ?? '--';
        $render['nombre_metier_code_smell_blocker'] = $codeSmell01['backend'] ?? '--';
        $render['nombre_autre_code_smell_blocker'] = $codeSmell01['autre'] ?? '--';
        $render['nombre_inconnu_code_smell_blocker'] = $codeSmell01['inconnu'] ?? '--';

        // Maintenabilité Critical
        $codeSmell02 = self::traitement($mavenKey, $setup, 'CODE_SMELL', 'CRITICAL');
        $render['nombre_presentation_code_smell_critical'] = $codeSmell02['frontend'] ?? '--';
        $render['nombre_metier_code_smell_critical'] = $codeSmell02['backend'] ?? '--';
        $render['nombre_autre_code_smell_critical'] = $codeSmell02['autre'] ?? '--';
        $render['nombre_inconnu_code_smell_critical'] = $codeSmell02['inconnu'] ?? '--';

        // Maintenabilité Major
        $codeSmell03 = self::traitement($mavenKey, $setup, 'CODE_SMELL', 'MAJOR');
        $render['nombre_presentation_code_smell_major'] = $codeSmell03['frontend'] ?? '--';
        $render['nombre_metier_code_smell_major'] = $codeSmell03['backend'] ?? '--';
        $render['nombre_autre_code_smell_major'] = $codeSmell03['autre'] ?? '--';
        $render['nombre_inconnu_code_smell_major'] = $codeSmell03['inconnu'] ?? '--';

        /** On calcul l'évolution pour chaque indicateur par rapport
         *  aux notes de référence.
         */

        /** Calcul de la variation des hotspots */
        $evolutionHotspot = self::variation($render['initial_nombre_hotspot'], $render['hotspot']);
        $render['evolution_hotspot'] = $evolutionHotspot;

        /** Calcul de la variation des mauvaises pratiques */
        $render['evolution_code_smell_blocker'] = self::variation($render['initial_code_smell_blocker'], $render['code_smell_blocker']);
        $render['evolution_code_smell_critical'] = self::variation($render['initial_code_smell_critical'], $render['initial_code_smell_blocker'], $render['code_smell_critical']);
        $render['evolution_code_smell_major'] = self::variation($render['initial_code_smell_major'], $render['code_smell_major']);

        /** Calcul de la variation des vulnérabilités */
        $render['evolution_vulnerability_blocker'] = self::variation($render['initial_vulnerability_blocker'], $render['vulnerability_blocker']);
        $render['evolution_vulnerability_critical'] = self::variation($render['initial_vulnerability_critical'], $render['vulnerability_critical']);
        $render['evolution_vulnerability_major'] = self::variation($render['initial_vulnerability_major'], $render['vulnerability_major']);

        /** Calcul de la variation des vulnérabilités */
        $render['evolution_bug_blocker'] = self::variation($render['initial_bug_blocker'], $render['bug_blocker']);
        $render['evolution_bug_critical'] = self::variation($render['initial_bug_critical'], $render['bug_critical']);
        $render['evolution_bug_major'] = self::variation($render['initial_bug_major'], $render['bug_major']);

        /** série pour la version de référence et la version courante */
        // ['Fiabilité','Vulnérabilité','Hotspot','Maintenabilité','Couverture','Dette']
        // >100, 70, 50 30 10

        /** On calcul la valeur des notes pour le Radar */
        $i_data1 = $i_data2 = $i_data3 = $i_data4 = $i_data5 = $i_data6 = 0;
        $i_data1 = static::note2point($render['initial_note_reliability']);
        $i_data2 = static::note2point($render['initial_note_security']);
        $i_data3 = static::note2point($render['initial_note_hotspot']);
        $i_data4 = static::note2point($render['initial_note_code_smell']);
        $i_data5 = $render['initial_coverage'];

        /** On inverse la courbe, plus le résultat est proche de 100 et plus la dette est petite */
        $i_data6 = 100 - $render['initial_sqale_debt_ratio'];

        /* si la dette technique est > à 100M alors le ration est de 100% */
        if ($render['initial_sqale_debt_ratio'] > 100) {
            $i_data6 = 100;
        }
        /** Si on a pad de données pour l'indicateur on fixe le niveau à 50 */
        if ($render['initial_sqale_debt_ratio'] < 0) {
            $i_data6 = 50;
        }

        $data1 = $data2 = $data3 = $data4 = $data5 = $data6 = 0;
        $data1 = static::note2point($render['note_reliability']);
        $data2 = static::note2point($render['note_security']);
        $data3 = static::note2point($render['note_hotspot']);
        $data4 = static::note2point($render['note_code_smell']);
        $data5 = $render['coverage'];

        /** On inverse la courbe, plus le résultat est proche de 100 et plus la dette est petite */
        $data6 = 100 - $render['sqale_debt_ratio'];

        /* si la dette technique est > à 100M alors le ration est de 100% */
        if ($render['sqale_debt_ratio'] > 100) {
            $data6 = 100;
        }
        /** Si on a pad de données pour l'indicateur on fixe le niveau à 50 */
        if ($render['sqale_debt_ratio'] < 0) {
            $data6 = 50;
        }

        /** On constitue les dataSet */
        $dataSet1 = "$i_data1, $i_data2, $i_data3, $i_data4, $i_data5, $i_data6";
        $dataSet2 = "$data1, $data2, $data3, $data4, $data5, $data6";

        $render['dataset1'] = $dataSet1;
        $render['dataset2'] = $dataSet2;
        $render['label1'] = $render['initial_version_application'];
        $render['label2'] = $render['version_application'];

        return $this->render(static::$page, $render);
    }
}
