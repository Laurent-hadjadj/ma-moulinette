<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2024.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Batch;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Historique;

/** Class API Batch */
use App\Controller\Batch\BatchCollecteInformationProjetController;
use App\Controller\Batch\BatchCollecteMesureController;
use App\Controller\Batch\BatchCollecteNoteController;
use App\Controller\Batch\BatchCollecteOwaspController;
use App\Controller\Batch\BatchCollecteHotspotController;
use App\Controller\Batch\BatchCollecteAnomalieController;
use App\Controller\Batch\BatchCollecteAnomalieDetailController;
use App\Controller\Batch\BatchCollecteHotspotOwaspController;
use App\Controller\Batch\BatchCollecteHotspotDetailController;
use App\Controller\Batch\BatchCollecteNoSonarController;
use App\Controller\Batch\BatchCollecteTodoController;
use App\Controller\Batch\BatchCollecteActuatorController;

/**
 * [Description CollecteController]
 */
class CollecteController extends AbstractController
{
    private static $dateFormat = 'Y-m-d H:i:s';
    private static $europeParis = 'Europe/Paris';
    private static $noMessage = 'Aucun message remonté.';
    private static $noError = 'Aucune erreur remontée.';
    private static $jeNeSaisPas = 'Je ne sais pas.';
    private static $erreurFinTraitement = '[Batch] ❌ Erreur lors du traitement des notes du projet.';

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private BatchCollecteInformationProjetController $batchCollecteInformation,
        private BatchCollecteMesureController $batchCollecteMesure,
        private BatchCollecteNoteController $batchCollecteNote,
        private BatchCollecteOwaspController $batchCollecteOwasp,
        private BatchCollecteHotspotController $batchCollecteHotspot,
        private BatchCollecteAnomalieController $batchCollecteAnomalie,
        private BatchCollecteAnomalieDetailController $batchCollecteAnomalieDetail,
        private BatchCollecteHotspotOwaspController $batchCollecteHotspotOwasp,
        private BatchCollecteHotspotDetailController $batchCollecteHotspotDetail,
        private BatchCollecteNoSonarController $batchCollecteNoSonar,
        private BatchCollecteTodoController $batchCollecteTodo,
        private BatchCollecteActuatorController $batchCollecteActuator,
        private BatchCollecteLoggerController $batchCollecteLogger
    ) {
        $this->batchCollecteInformation = $batchCollecteInformation;
        $this->batchCollecteMesure = $batchCollecteMesure;
        $this->batchCollecteNote = $batchCollecteNote;
        $this->batchCollecteOwasp = $batchCollecteOwasp;
        $this->batchCollecteHotspot = $batchCollecteHotspot;
        $this->batchCollecteAnomalie = $batchCollecteAnomalie;
        $this->batchCollecteAnomalieDetail = $batchCollecteAnomalieDetail;
        $this->batchCollecteHotspotOwasp = $batchCollecteHotspotOwasp;
        $this->batchCollecteHotspotDetail = $batchCollecteHotspotDetail;
        $this->batchCollecteNoSonar = $batchCollecteNoSonar;
        $this->batchCollecteTodo = $batchCollecteTodo;
        $this->batchCollecteActuator = $batchCollecteActuator;
        $this->batchCollecteLogger = $batchCollecteLogger;
    }

    /**
     * [Description for generateHtmlForItem]
     *
     * @param array $item
     *
     * @return string
     *
     * Created at: 07/09/2025 15:07:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function generateHtmlForItem(array $item): string
    {
        return '
            <table border="1">
                <tr>
                    <th>Referential OWASP</th>
                    <td>' . $item['referential_owasp'] . '</td>
                </tr>
                <tr>
                    <th>Maven Key</th>
                    <td>' . $item['maven_key'] . '</td>
                </tr>
                <tr>
                    <th>Version</th>
                    <td>' . $item['version'] . '</td>
                </tr>
                <tr>
                    <th>Date Version</th>
                    <td>' . $item['date_version']->format(static::$dateFormat) . '</td>
                </tr>
                <tr>
                    <th>Menace</th>
                    <td>' . $item['menace'] . '</td>
                </tr>
                <tr>
                    <th>Security Category</th>
                    <td>' . $item['security_category'] . '</td>
                </tr>
                <tr>
                    <th>Rule Key</th>
                    <td>' . $item['rule_key'] . '</td>
                </tr>
                <tr>
                    <th>Probability</th>
                    <td>' . $item['probability'] . '</td>
                </tr>
                <tr>
                    <th>Niveau</th>
                    <td>' . $item['niveau'] . '</td>
                </tr>
                <tr>
                    <th>Date Enregistrement</th>
                    <td>' . $item['date_enregistrement']->format(static::$dateFormat) . '</td>
                </tr>
            </table>';
    }

    /**
     * [Description for generateHtmlErrorContain]
     *
     * @param string $code
     * @param string $message
     * @param string $erreur
     *
     * @return string
     *
     * Created at: 08/09/2025 10:42:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function generateHtmlErrorContain(string $code, string $message, string $erreur): string
    {
        return '<div class="callout alert-callout-border alert callout-ombre"><p class="open-sans">
                        🔴 Le traitement de collecte pour ce projet est arrêté.</p>
                </div>
                <div>
                    <p class="open-sans"><strong>Traces :</strong></p>
                    <ul class="open-sans">
                        <li class="lead">Code : '. $code .'</li>
                        <li class="lead">Message : '. $message .'</li>
                        <li class="lead">Erreur : '. $erreur .'</li>
                    </ul>
                </div>';
    }

    /**
     * [Description for generateHtmlFromArray]
     *
     * @param array $items
     *
     * @return string
     *
     * Created at: 08/09/2025 10:49:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function generateHtmlFromArray(array $items): string | null
    {
        $html = '<ul class="data-list">';

        foreach ($items as $key => $value) {
            if (!is_string($key)) {
                $key = (string) $key;
            }

            if ($value === null) {
                $value = 'N/A';
            } elseif (!is_scalar($value)) {
                // pour les arrays, objets, booléens, etc.
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            } else {
                // scalaires (int, float, bool) → cast en string
                $value = (string) $value;
            }
            // Gérer les cas où la valeur est null ou vide
            $value = $value ?? 'N/A';
            $html .= '<li class="lead">';
            $html .= '<strong>' . htmlspecialchars($key) . '</strong> => ';
            $html .= htmlspecialchars($value);
            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * [Description for collecte]
     *
     * @param Request $request
     *
     * @return array
     *
     * Created at: 11/06/2024 12:58:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function collecte($portefeuille, $maven_key, $mode_collecte, $utilisateur_collecte): array
    {
        /** On instancie l'entityRepository */
        $historiqueRepos = $this->em->getRepository(Historique::class);

        /** On initialise la log */
        $collecte = [];

        /** On nettoie les variables du POST */
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $mode_collecte = htmlspecialchars($mode_collecte, ENT_QUOTES, 'UTF-8');

        /** On démarre la mesure du traitement */
        $debutTraitement = new \DateTime('now', new \DateTimeZone(static::$europeParis));
        $this->logger->info("[Batch] ℹ️ Démarrage du traitement différé en mode {$mode_collecte}.");

        $collecte[] =
            '<div>
                <h1 class="h3 open-sans"><span aria-hidden="true">🎬</span>Début du traitement</h1>
                <div class="fieldset">
                    <ul class="open-sans">
                        <li class="lead">Portefeuille : '. $portefeuille . '</li>
                        <li class="lead">Projet : ' . $maven_key . '</li>
                        <li class="lead">Mode : ' . $mode_collecte .'</li>
                        <li class="lead">Date : ' . $debutTraitement->format(static::$dateFormat) .'</li>
                    </ul>
                <div>
            </div>';

        /** On récupère les données du projet */
        $mergeHistorique = [];

        /** Informations du projet (nom, type de version) */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des informations du projet.');
        $collecte[] =
        '<h2 class="h4 open-sans"><span aria-hidden="true">🔜</span>01 - Collecte pour les informations du projet</h2>';

        $informationProjet = $this->batchCollecteInformation->batchCollecteInformation(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte
        );

        /** Tout vas bien, on peut lancer la collecte */
        if ($informationProjet['code'] === 200){
            $item = $informationProjet['historique'];
            $collecte[] =
            '<p class="open-sans">'. $informationProjet['message'] .'</p>
            <div>
                <ul class="open-sans">
                    <li class="lead">Clé d’analyse : ' . $item['analyse_key'] . '</li>
                    <li class="lead">Version du projet : ' . $item['version'] . '</li>
                    <li class="lead">date de la Version du projet : ' . $item['date_version'] . '</li>
                    <li class="lead">Nombre Release (ma-moulinette) : ' . $item['version_release'] .'</li>
                    <li class="lead">Nombre Snapshot (ma-moulinette) : ' . $item['version_snapshot'] .'</li>
                    <li class="lead">Nombre Autre (ma-moulinette) : ' . $item['version_autre'] .'</li>
                    <li class="lead">Nombre Release (sonar) : ' . $item['version_release_sonar'] .'</li>
                    <li class="lead">Nombre Snapshot (sonar) : ' . $item['version_snapshot_sonar'] .'</li>
                    <li class="lead">Nombre Autre (sonar) : ' . $item['version_autre_sonar'] .'</li>
                    <li class="lead">Nombre total (sonar) : ' . $item['version_sonar'] .'</li>
                    <li class="lead">Nombre Release (sonar) : ' . $item['version_release_sonar'] .'</li>
                </ul>
            </div>';

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $informationProjet['historique']);
        }

        /** Le projet existe déjà, il est à jour, on arrête la collecte ou pn met à jour les informations. */
        if ($informationProjet['code'] === 100){
            $this->logger->info('[Batch] ℹ️ Mise à jour pour le projet inutile.');
            /** Pas de mise à jour local = historique = SonarQube */
            $item = $informationProjet['historique'];
            if (isset($informationProjet['historique']) && isset($informationProjet['historique']['Locale'])){
            $collecte[] =
            '<p class="open-sans">'. $informationProjet['message'] .'</p>
            <div>
                <ul class="open-sans">
                    <li class="lead">Code : ' . $item['code'] . '</li>
                    <li class="lead">Mode : ' . $item['mode_collecte'] .'</li>
                </ul>
                <p class="open-sans">Version ma-moulinette.</p>
                <ul class="open-sans">
                    <li class="lead">Nom : ' . $item['Locale']['name'] . '</li>
                    <li class="lead">Clé d’analyse : ' . $item['Locale']['key-analyse'] .'</li>
                    <li class="lead">version : ' . $item['Locale']['version'] .'</li>
                    <li class="lead">Date analyse : ' . $item['Locale']['date-analyse'] .'</li>
                </ul>
                <p class="open-sans">Version SonarQube.</p>
                <ul class="open-sans">
                    <li class="lead">Clé d’analyse : ' . $item['SonarQube']['key-analyse'] .'</li>
                    <li class="lead">version : ' . $item['SonarQube']['version'] .'</li>
                </ul>
            </div>
            <h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>01 - Fin de la collecte des Informations du projet</h2>
            <div class="callout alert-callout-border warning callout-ombre"><p class="open-sans">
                ⚠️ Le traitement de collecte pour ce projet est arrêté.</p>
            </div>';
            }

            /** On continue le traitement */
            $compte_rendu = implode("\n", $collecte);
            return [
                    'code' => 202,
                    'compte_rendu' => $compte_rendu
                ];
        }

        /** Pour les autres messages d'erreur */
        if (!in_array($informationProjet['code'], ['200', '100'])) {
            $this->logger->info('[Batch] ❌ Erreur lors du traitement des informations du projet.', [
                'code' => $informationProjet['code'],
                'message' => $informationProjet['message'] ?? static::$noMessage,
                'erreur' => $informationProjet['erreur'] ?? static::$noError,

            ]);

            $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>Fin de la collecte des informations du projet</h2>';

            $erreurContain = $this->generateHtmlErrorContain(
                $informationProjet['code'],
                $informationProjet['message'] ?? static::$noMessage,
                $informationProjet['erreur'] ?? static::$noError );

            $collecte[] = $erreurTitre . $erreurContain;
            $compte_rendu = implode("\n", $collecte);

            return [
                    'code' => $informationProjet['code'],
                    'compte_rendu' => $compte_rendu
                ];
        }

        /** Mesures du projet (ligne de code, coverage, dette, ...) */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des mesures pour le projet.');
        $collecte[] =
        '<h2 class="h4 open-sans"><span aria-hidden="true">🔜</span>02 - Collecte des mesures pour le projet</h2>';

        $mesure = $this->batchCollecteMesure->batchCollecteMesure(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($mesure['code'] === 200){
            $item = $mesure['historique'];
            $data = $mesure['data'];
            $collecte[] =
            '<p class="open-sans">'. $mesure['message'] .'</p>
            <div>
                <ul class="open-sans">
                    <li class="lead">Clé maven : ' . $data['maven_key'] . '</li>
                    <li class="lead">Version du projet : ' . $item['nom_projet'] . '</li>
                    <li class="lead">Nombre de ligne : ' . $item['nombre_ligne'] . '</li>
                    <li class="lead">Nombre de ligne de code : ' . $item['nombre_ligne_code'] .'</li>
                    <li class="lead">Nombre de classes : ' . $item['nombre_classes'] .'</li>
                    <li class="lead">Nombre de fonctions/méthodes : ' . $item['nombre_functions'] .'</li>
                    <li class="lead">Nombre de fichiers : ' . $item['nombre_files'] .'</li>
                    <li class="lead">Distribution des langages : ' . json_encode($item['language_distribution']) .'</li>
                    <li class="lead">Ratio de dette technique : ' . $item['sqale_debt_ratio'] .'</li>
                    <li class="lead">Couverture du code : ' . $item['coverage'] .'</li>
                    <li class="lead">Codes dupliqué : ' . $item['duplicated_lines_density'] .'</li>
                    <li class="lead">Nombre de tests unitaires : ' . $item['tests'] .'</li>
                    <li class="lead">Nombre d’anomalie : ' . $item['issues'] .'</li>
                </ul>
            </div>';

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $mesure['historique']);
        } else {
            $this->logger->info('[Batch] ❌ Erreur lors du traitement des mesures du projet.', [
                'code' => $mesure['code'],
                'message' => $mesure['message'] ?? static::$noMessage,
                'erreur' => $mesure['erreur'] ?? static::$noError,
            ]);

            $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>02 - Fin de la collecte des mesures pour le projet</h2>';
            $erreurContain = $this->generateHtmlErrorContain(
                $mesure['code'],
                $mesure['message'] ?? static::$noMessage,
                $mesure['erreur'] ?? static::$noError );

            $collecte[] = $erreurTitre . $erreurContain;
            $compte_rendu = implode("\n", $collecte);

            return [
                    'code' => $mesure['code'],
                    'compte_rendu' => $compte_rendu
                ];
        }

        /** Notes du projet  (fiabilité, sécurité, mauvaise pratique) */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des notes pour le projet.');
        $collecte[] =
        '<h2 class="h4 open-sans"><span aria-hidden="true">🔜</span>03 - Collecte des notes pour le projet</h2>';

        $noteReliability = $this->batchCollecteNote->batchCollecteNote(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte,
            'reliability');

        if ($noteReliability['code'] === 200){
            $item = $noteReliability['historique'];
            $collecte[] =
            '<p class="open-sans">'. $noteReliability['message'] .'</p>
            <div>
                <ul class="open-sans">
                    <li class="lead">Note pour la catégorie RELIABILITY : ' . $item['note_reliability'] . '</li>
                </ul>
            </div>';

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $noteReliability['historique']);
        } else {
            $this->logger->error(static::$erreurFinTraitement, [
                'code' => $noteReliability['code'],
                'type' => 'reliability',
                'message' => $noteReliability['message'] ?? static::$noMessage,
                'erreur' => $noteReliability['erreur'] ?? static::$noError,
            ]);

            $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>03 - Fin de la collecte des notes pour le projet</h2>';
            $erreurSousTitre = '<h3> class="h5 opens-sans"><span aria-hidden="true">🔠</span>Type : RELIABILITY<h3>';

            $erreurContain = $this->generateHtmlErrorContain(
                    $noteReliability['code'],
                    $noteReliability['message'] ?? static::$noMessage,
                    $noteReliability['erreur'] ?? static::$noError );

            $collecte[] = $erreurTitre . $erreurSousTitre. $erreurContain;
            $compte_rendu = implode("\n", $collecte);

            return [
                    'code' => $noteReliability['code'],
                    'compte_rendu' => $compte_rendu
                ];
        }

        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des notes pour le projet.');
        $collecte[] =
        '<h2 class="h4 open-sans"><span aria-hidden="true">🔜</span>03 - Collecte des notes pour le projet</h2>';

        $noteSecurity = $this->batchCollecteNote->batchCollecteNote(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte,
            'security');

        if ($noteSecurity['code'] === 200){
            $item = $noteSecurity['historique'];
            $collecte[] =
            '<p class="open-sans">'. $noteSecurity['message'] .'</p>
            <div>
                <ul class="open-sans">
                    <li class="lead">Note pour la catégorie SECURITY : ' . $item['note_security'] . '</li>
                </ul>
            </div>';

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $noteSecurity['historique']);
        } else {
            $this->logger->error(static::$erreurFinTraitement, [
                'code' => $noteSecurity['code'],
                'type' => 'security',
                'message' => $noteSecurity['message'] ?? static::$noMessage,
                'erreur' => $noteSecurity['erreur'] ?? static::$noError,
            ]);

            $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>03 - Fin de la collecte des notes pour le projet</h2>';
            $erreurSousTitre = '<h3> class="h5 opens-sans"><span aria-hidden="true">🔠</span>Type : SECURITY<h3>';

            $erreurContain = $this->generateHtmlErrorContain(
                    $noteSecurity['code'],
                    $noteSecurity['message'] ?? static::$noMessage,
                    $noteSecurity['erreur'] ?? static::$noError );

            $collecte[] = $erreurTitre . $erreurSousTitre. $erreurContain;
            $compte_rendu = implode("\n", $collecte);

            return [
                    'code' => $noteSecurity['code'],
                    'compte_rendu' => $compte_rendu
                ];
        }

        $noteSqale = $this->batchCollecteNote->batchCollecteNote(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte,
            'sqale');

        if ($noteSqale['code'] === 200){
            $item = $noteSqale['historique'];
            $collecte[] =
            '<p class="open-sans">'. $noteSqale['message'] .'</p>
            <div>
                <ul class="open-sans">
                    <li class="lead">Note pour la catégorie SQALE : ' . $item['note_sqale'] . '</li>
                </ul>
            </div>';

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $noteSqale['historique']);
        } else {
            $this->logger->error(static::$erreurFinTraitement, [
                'code' => $noteSqale['code'],
                'type' => 'sqale',
                'message' => $noteSqale['message'] ?? static::$noMessage,
                'erreur' => $noteSqale['erreur'] ?? static::$noError,
            ]);

            $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>03 - Fin de la collecte des notes pour le projet</h2>';
            $erreurSousTitre = '<h3> class="h5 opens-sans"><span aria-hidden="true">🔠</span>Type : CODE SMELL<h3>';

            $erreurContain = $this->generateHtmlErrorContain(
                    $noteSecurity['code'],
                    $noteSecurity['message'] ?? static::$noMessage,
                    $noteSecurity['erreur'] ?? static::$noError );

            $collecte[] = $erreurTitre . $erreurSousTitre. $erreurContain;
            $compte_rendu = implode("\n", $collecte);

            return [
                    'code' => $noteSqale['code'],
                    'compte_rendu' => $compte_rendu
                ];
        }

        /** Signalement des Anomalies pour le projet  */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des anomalies pour le projet.');
        $collecte[] =
            '<h2 class="h4 open-sans"><span aria-hidden="true">🔜</span>04 - Collecte des anomalies pour le projet</h2>';

        $anomalie = $this->batchCollecteAnomalie->batchCollecteAnomalie(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($anomalie['code'] === 200){
            $item = $anomalie['data'];
            $collecte[] =
            '<p class="open-sans">'. $anomalie['message'] .'</p>
            <div>
                <ul class="open-sans">
                    <li class="lead">Nombre d’anomalie total : ' . $item['anomalie_total'] . '</li>
                    <li class="lead">Dette technique : ' . $item['dette'] . '</li>
                    <li class="lead">Dette en minute : ' . $item['dette_minute'] . '</li>
                    <li class="lead">Dette reliability : ' . $item['dette_reliability'] . '</li>
                    <li class="lead">Dette en minute : ' . $item['dette_reliability_minute'] . '</li>
                    <li class="lead">Dette vulnerability : ' . $item['dette_vulnerability'] . '</li>
                    <li class="lead">Dette en minute : ' . $item['dette_vulnerability_minute'] . '</li>
                    <li class="lead">Dette code_smell : ' . $item['dette_code_smell'] . '</li>
                    <li class="lead">Dette en minute : ' . $item['dette_code_smell_minute'] . '</li>
                    <li class="lead">Nombre d’anomalie de type BUG : ' . $item['bug'] . '</li>
                    <li class="lead">Nombre d’anomalie de type VULNERABILITY : ' . $item['vulnerability'] . '</li>
                    <li class="lead">Nombre d’anomalie de type CODE_SMELL : ' . $item['code_smell'] . '</li>
                    <li class="lead">Nombre d’anomalie frontend : ' . $item['frontend'] . '</li>
                    <li class="lead">Nombre d’anomalie backend : ' . $item['backend'] . '</li>
                    <li class="lead">Nombre d’anomalie autre : ' . $item['autre'] . '</li>
                    <li class="lead">Nombre d’anomalie inconnu : ' . $item['inconnu'] . '</li>
                    <li class="lead">Nombre d’anomalie bloquant : ' . $item['blocker'] . '</li>
                    <li class="lead">Nombre d’anomalie critique: ' . $item['critical'] . '</li>
                    <li class="lead">Nombre d’anomalie majeur: ' . $item['major'] . '</li>
                    <li class="lead">Nombre d’anomalie en info: ' . $item['info'] . '</li>
                    <li class="lead">Nombre d’anomalie mineur: ' . $item['minor'] . '</li>
                </ul>
            </div>';

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $anomalie['historique']);
        } else {
            $this->logger->error('[Batch] ❌ Erreur lors du traitement des anomalies du projet.', [
                    'code' => $anomalie['code'],
                    'type' => $anomalie['type'] ?? '',
                    'message' => $anomalie['message'] ?? static::$noMessage,
                    'erreur' => $anomalie['erreur'] ?? static::$noError,
            ]);

            $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>04 - Fin de la collecte des anomalies pour le projet</h2>';
            $erreurSousTitre = '<h3> class="h5 opens-sans"><span aria-hidden="true">🔠</span>Type : '. $anomalie['type'] ?? ' Information manquante' .'<h3>';

            $erreurContain = $this->generateHtmlErrorContain(
                    $anomalie['code'],
                    $anomalie['message'] ?? static::$noMessage,
                    $anomalie['erreur'] ?? static::$noError );

            $collecte[] = $erreurTitre . $erreurSousTitre. $erreurContain;
            $compte_rendu = implode("\n", $collecte);

            return [
                    'code' => $anomalie['code'],
                    'compte_rendu' => $compte_rendu
                ];
        }

        /** Signalement du détails des Anomalies pour le projet  */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte du détails des anomalies pour le projet.');
        $collecte[] =
            '<h2 class="h4 open-sans"><span aria-hidden="true">🔜</span>05 - Collecte du détails des anomalies pour le projet</h2>';

        $anomalieDetail = $this->batchCollecteAnomalieDetail->BatchCollecteAnomalieDetail(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($anomalieDetail['code'] === 200){
            $item = $anomalieDetail['historique'];
            if (empty($item)){
                $msg = htmlspecialchars($anomalieDetail['message']);
                $collecte[] = '<p class="open-sans">'. $msg .'</p>';
            } else  {
                $collecte[] =
                '<p class="open-sans">'. htmlspecialchars($anomalieDetail['message']) .'</p>
                <div>
                    <ul class="open-sans">
                        <li class="lead">Nombre de Bug Bloquant : ' . htmlspecialchars($item['bug_blocker']) . '</li>
                        <li class="lead">Nombre de Bug Critique : ' . htmlspecialchars($item['bug_critical']) . '</li>
                        <li class="lead">Nombre de Bug Majeur : ' . htmlspecialchars($item['bug_major']) . '</li>
                        <li class="lead">Nombre de Bug Mineur : ' . htmlspecialchars($item['bug_minor']) . '</li>
                        <li class="lead">Nombre de Bug Info : ' . htmlspecialchars($item['bug_info']) . '</li>
                        <li class="lead">Nombre de Vulnérabilité Bloquant : ' . htmlspecialchars($item['vulnerability_blocker']) . '</li>
                        <li class="lead">Nombre de Vulnérabilité Critique : ' . htmlspecialchars($item['vulnerability_critical']) . '</li>
                        <li class="lead">Nombre de Vulnérabilité Majeur : ' . htmlspecialchars($item['vulnerability_major']) . '</li>
                        <li class="lead">Nombre de Vulnérabilité Mineur : ' . htmlspecialchars($item['vulnerability_minor']) . '</li>
                        <li class="lead">Nombre de Vulnérabilité Info : ' . htmlspecialchars($item['vulnerability_info']) . '</li>
                        <li class="lead">Nombre de Mauvaise Pratique Bloquant : ' . htmlspecialchars($item['code_smell_blocker']) . '</li>
                        <li class="lead">Nombre de Mauvaise Pratique Critique : ' . htmlspecialchars($item['code_smell_critical']) . '</li>
                        <li class="lead">Nombre de Mauvaise Pratique Majeur : ' . htmlspecialchars($item['code_smell_major']) . '</li>
                        <li class="lead">Nombre de Mauvaise Pratique Mineur : ' . htmlspecialchars($item['code_smell_minor']) . '</li>
                        <li class="lead">Nombre de Mauvaise Pratique Info : ' . htmlspecialchars($item['code_smell_info']) . '</li>
                    </ul>
                </div>';
            }

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $anomalieDetail['historique']);
        } else {
            $this->logger->error('[Batch] ❌ Erreur lors du traitement du détail des anomalies du projet.', [
                    'code' => $anomalieDetail['code'],
                    'type' => $anomalieDetail['type'] ?? '',
                    'message' => $anomalieDetail['message'] ?? static::$noMessage,
                    'erreur' => $anomalieDetail['erreur'] ?? static::$noError,
            ]);

            $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>05 - Fin de la collecte du détails des anomalies pour le projet</h2>';
            $erreurSousTitre = '<h3> class="h5 opens-sans"><span aria-hidden="true">🔠</span>Type : '. $anomalieDetail['type'] ?? 'Information manquante' .'<h3>';

            $erreurContain = $this->generateHtmlErrorContain(
                    $anomalieDetail['code'],
                    $anomalieDetail['message'] ?? static::$noMessage,
                    $anomalieDetail['erreur'] ?? static::$noError );

            $collecte[] = $erreurTitre . $erreurSousTitre. $erreurContain;
            $compte_rendu = implode("\n", $collecte);

            return [
                'code' => $anomalieDetail['code'],
                'compte_rendu' => $compte_rendu
            ];
        }

        /** Signalement Hotspots pour le projet  */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des menaces potentielles pour le projet.');
        $collecte[] =
        '<h2 class="h4 open-sans"><span aria-hidden="true">🔜</span>06 - Collecte des menaces potentielles pour le projet</h2>';

        $hotspot = $this->batchCollecteHotspot->batchCollecteHotspot(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($hotspot['code'] === 200){
            $item = $hotspot['historique'];
            $collecte[] =
            '<p class="open-sans">'. $hotspot['message'] .'</p>
            <div>
                <ul class="open-sans">
                    <li class="lead">Nombre de Hotspot de niveau High : ' . $item['hotspot_high'] . '</li>
                    <li class="lead">Nombre de Hotspot de niveau Medium : ' . $item['hotspot_medium'] . '</li>
                    <li class="lead">Nombre de Hotspot de niveau Low : ' . $item['hotspot_low'] . '</li>
                    <li class="lead">Nombre de Hotspot : ' . $item['nombre_hotspot'] . '</li>
                </ul>
            </div>';

        } else {
            $this->logger->error('[Batch] ❌ Erreur lors du traitement des menaces du projet.', [
                    'code' => $hotspot['code'],
                    'message' => $hotspot['message'] ?? static::$noMessage,
                    'erreur' => $hotspot['erreur'] ?? static::$noError,
            ]);

            $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>06 - Fin de la collecte des menaces potentielles pour le projet</h2>';
            $erreurContain = $this->generateHtmlErrorContain(
                $hotspot['code'],
                $hotspot['message'] ?? static::$noMessage,
                $hotspot['erreur'] ?? static::$noError );

            $collecte[] = $erreurTitre . $erreurContain;
            $compte_rendu = implode("\n", $collecte);

            return [
                'code' => $hotspot['code'],
                'compte_rendu' => $compte_rendu
            ];
        }

        /** On calcule la note pour les hotspot */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte de la note des menaces potentielle pour le projet.');
        $collecte[] =
        '<h2 class="h4 open-sans"><span aria-hidden="true">🔜</span>07 - Collecte de la note Hotspot pour le projet</h2>';

        $noteHotspot = $this->batchCollecteNote->batchCollecteNoteHotspot(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

            if ($noteHotspot['code'] === 200){
                $item = $noteHotspot['historique'];
                $collecte[] =
                '<p class="open-sans">'. $noteHotspot['message'] .'</p>
                <div>
                    <ul class="open-sans">
                        <li class="lead">Note pour les menaces potentielles : ' . $item['note_hotspot'] . '</li>
                    </ul>
                </div>';

                /** On met à jour le tableau des données pour la table historique */
                $mergeHistorique = array_merge($mergeHistorique, $noteHotspot['historique']);
            } else {
                $this->logger->error('[Batch] ❌ Erreur lors du traitement de la note des menaces potentielles du projet.', [
                            'code' => $noteHotspot['code'],
                            'message' => $noteHotspot['message'] ?? static::$noMessage,
                            'erreur' => $noteHotspot['erreur'] ?? static::$noError,
                    ]);

                $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>07 - Fin de la collecte de la note des menaces potentielles pour le projet</h2>';

                $erreurContain = $this->generateHtmlErrorContain(
                    $noteHotspot['code'],
                    $noteHotspot['message'] ?? static::$noMessage,
                    $noteHotspot['erreur'] ?? static::$noError );

                $collecte[] = $erreurTitre . $erreurContain;
                $compte_rendu = implode("\n", $collecte);

                return [
                    'code' => $noteHotspot['code'],
                    'compte_rendu' => $compte_rendu
                ];
            }

        /** Signalement du détail des Hotspots pour le projet */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte du détail des hotspots pour le projet.');
        $collecte[] =
        '<h2 class="h4 open-sans"><span aria-hidden="true">🔜</span>08 - Collecte du détail des menaces potentielles pour le projet</h2>';

        $hotspotDetails = $this->batchCollecteHotspotDetail->batchCollecteHotspotDetail(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($hotspotDetails['code'] === 200){
            $item = $hotspotDetails['historique'];
            if (empty($item)){
                $msg = htmlspecialchars($hotspotDetails['message']);
                $collecte[] ='<p class="open-sans">'. $msg .'</p>';
            } else  {
                $collecte[] =
                '<p class="open-sans">'. htmlspecialchars($hotspotDetails['message']) .'</p>
                <div>
                    <ul class="open-sans">
                        <li class="lead">Nombre d’élément dans la liste  : ' .
                            htmlspecialchars($hotspotDetails['nombre']) ?? static::$jeNeSaisPas . '</li>
                        <li class="lead">Nombre de menaces total  : ' .
                            htmlspecialchars($item['menace_potentielle_totale']) ?? static::$jeNeSaisPas . '</li>
                        <li class="lead">Nombre de menaces de niveau HIGH au statut REVIEW : ' .
                            htmlspecialchars($item['menace_potentielle_review_high']) ?? static::$jeNeSaisPas . '</li>
                        <li class="lead">Menaces de niveau MEDIUM au statut REVIEW : ' .
                            htmlspecialchars($item['menace_potentielle_review_medium']) ?? static::$jeNeSaisPas . '</li>
                        <li class="lead">Menaces de niveau LOW au statut REVIEW : ' .
                            htmlspecialchars($item['menace_potentielle_review_low']) ?? static::$jeNeSaisPas . '</li>
                        <li class="lead">Menaces de niveau HIGH au statut REVIEWED : ' .
                            htmlspecialchars($item['menace_potentielle_reviewed_high']) ?? static::$jeNeSaisPas . '</li>
                        <li class="lead">Menaces de niveau MEDIUM au statut REVIEWED : ' .
                            htmlspecialchars($item['menace_potentielle_reviewed_medium']) ?? static::$jeNeSaisPas . '</li>
                        <li class="lead">Menaces de niveau LOW au statut REVIEWED : ' .
                            htmlspecialchars($item['menace_potentielle_reviewed_low']) ?? static::$jeNeSaisPas . '</li>
                    </ul>
                </div>';
                /** On met à jour le tableau des données pour la table historique */
                $mergeHistorique = array_merge($mergeHistorique, $hotspotDetails['historique']);
            }
        } else {
            $this->logger->error('[Batch] ❌ Erreur lors du traitement du détail des menaces potentielles du projet.', [
                        'code' => $hotspotDetails['code'],
                        'message' => $hotspotDetails['message'] ?? static::$noMessage,
                        'erreur' => $hotspotDetails['erreur'] ?? static::$noError,
                ]);

            $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>08 - Fin de la collecte du détail des menaces potentielles pour le projet</h2>';

            $erreurContain = $this->generateHtmlErrorContain(
                $hotspotDetails['code'],
                $hotspotDetails['message'] ?? static::$noMessage,
                $hotspotDetails['erreur'] ?? static::$noError );

            $collecte[] = $erreurTitre . $erreurContain;
            $compte_rendu = implode("\n", $collecte);

            return [
                'code' => $hotspotDetails['code'],
                'compte_rendu' => $compte_rendu
            ];
        }

        /** Signalement OWASP et nombre d'issue par type pour le projet  */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des menaces OWASP pour le projet.');
        $collecte[] =
        '<h2 class="h4 open-sans"><span aria-hidden="true">🔜</span>09 - Collecte des menaces OWASP pour le projet</h2>';

        $owasp = $this->batchCollecteOwasp->batchCollecteOwasp(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($owasp['code'] === 200){
        $collecte[] =
        '<p class="open-sans">'. $owasp['message'] .'</p>
        <div>
            <ul class="open-sans">
                <li class="lead">Nombre de menace 2017  : ' . $owasp['owasp2017'] . '</li>
                <li class="lead">Nombre de menace 2021  : ' . $owasp['owasp2021'] . '</li>
            </ul>
        </div>';
        /** Pas de données historisées */
        } else {
            $this->logger->error('[Batch] ❌ Erreur lors du traitement des menaces OWASP du projet.', [
                        'code' => $owasp['code'],
                        'message' => $owasp['message'] ?? static::$noMessage,
                        'erreur' => $owasp['erreur'] ?? static::$noError,
                ]);

            $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>09 - Fin de la collecte des menaces OWASP pour le projet</h2>';

            $erreurContain = $this->generateHtmlErrorContain(
                $owasp['code'],
                $owasp['message'] ?? static::$noMessage,
                $owasp['erreur'] ?? static::$noError );

            $collecte[] = $erreurTitre . $erreurContain;
            $compte_rendu = implode("\n", $collecte);

            return [
                'code'=> $owasp['code'],
                'compte_rendu' => $compte_rendu
            ];
        }

        $errorEncountered = false;

        /** Signalement HotspotOwasp pour le projet */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des menaces OWASP potentielles pour le projet.');
        $collecte[] =
            '<h2 class="h4 open-sans"><span aria-hidden="true">🔜</span>10 - Collecte des menaces OWASP potentielles pour le projet</h2>';

        $owaspKeys = ['a0', 'a1', 'a2', 'a3', 'a4', 'a5', 'a6', 'a7', 'a8', 'a9', 'a10'];
        foreach ($owaspKeys as $owaspKey) {
            $hotspotOwasp = $this->batchCollecteHotspotOwasp->batchCollecteHotspotOwasp(
                $maven_key,
                $mode_collecte,
                $utilisateur_collecte,
                $owaspKey);

            if ($hotspotOwasp['code'] === 200) {
                if (isset($hotspotOwasp['data']) && !empty($hotspotOwasp['data'])){
                    $html = $this->generateHtmlForItem($hotspotOwasp['data'][0]);
                    $collecte[] =
                    '<p class="open-sans">'. $hotspotOwasp['message'].'</p>
                    <div>
                        <ul class="open-sans">
                            <li class="lead">Mode  : ' . $hotspotOwasp['info'] . '</li>
                            <li class="lead">Menace  : ' . strtoupper($owaspKey) . '</li>
                            <li class="lead">Nombre de menace 2017  : ' . $hotspotOwasp['owasp_2017'] . '</li>
                            <li class="lead">Nombre de menace 2021  : ' . $hotspotOwasp['owasp_2021'] . '</li>
                        </ul>
                    </div>
                    <p class="open-sans">Détail</p>
                    <div class="callout">'. $html .'</div>';
                }
            } else {
                $this->logger->error('[Batch] ❌ Erreur lors du traitement des menaces OWASP potentielles du projet.', [
                        'code' => $hotspotOwasp['code'],
                        'type' => $hotspotOwasp['type'] ?? 'inconnu',
                        'message' => $hotspotOwasp['message'] ?? static::$noMessage,
                        'erreur' => $hotspotOwasp['erreur'] ?? static::$noError,
                ]);

                $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>10 - Fin de la collecte des menaces OWASP potentielles pour le projet</h2>';
                $erreurSousTitre = '<h3> class="h5 opens-sans"><span aria-hidden="true">🔠</span>Menace : '. $hotspotOwasp['type'] ?? 'Information manquante' .'<h3>';

                $erreurContain = $this->generateHtmlErrorContain(
                        $hotspotOwasp['code'],
                        $hotspotOwasp['message'] ?? static::$noMessage,
                        $hotspotOwasp['erreur'] ?? static::$noError );

                $collecte[] = $erreurTitre . $erreurSousTitre. $erreurContain;
                $compte_rendu = implode("\n", $collecte);

                $errorEncountered = true;
            }

            if ($errorEncountered) {
                return [
                    'code' => 500,
                    'compte_rendu' => $compte_rendu];
            }
        }

        /** Signalement NoSonar et suppressWarning pour les projets Java */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des annotations noSonar et suppressWarning pour le projet.');
        $collecte[] =
        '<h2 class="h4 open-sans"><span aria-hidden="true">🔜</span>11 - Collecte des annotations noSonar et suppressWarning pour le projet</h2>';

        $noSonar = $this->batchCollecteNoSonar->batchCollecteNoSonar(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($noSonar['code'] === 200){
            $item = $noSonar['historique'];
            $collecte[] =
            '<p class="open-sans">'. $noSonar['message'] .'</p>
            <div>
                <ul class="open-sans">
                    <li class="lead">Nombre de noSonar : ' . $item['no_sonar'] . '</li>
                    <li class="lead">Nombre de suppressWarning : ' . $item['suppress_warning'] . '</li>
                </ul>
            </div>';

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $noSonar['historique']);
        } else {
                $this->logger->error('[Batch] ❌ Erreur lors du traitement des annotations noSonar/suppressWarning du projet.', [
                        'code' => $noSonar['code'],
                        'message' => $noSonar['message'] ?? static::$noMessage,
                        'erreur' => $noSonar['erreur'] ?? static::$noError,
                ]);

            $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>11 Fin de la collecte des annotations noSonar et suppressWarning pour le projet</h2>';

            $erreurContain = $this->generateHtmlErrorContain(
                $noSonar['code'],
                $noSonar['message'] ?? static::$noMessage,
                $noSonar['erreur'] ?? static::$noError );

            $collecte[] = $erreurTitre . $erreurContain;
            $compte_rendu = implode("\n", $collecte);

            return [
                'code' => $noSonar['code'],
                'compte_rendu' => $compte_rendu
            ];
        }

        /** Signalement des to.do pour le projet */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des todo pour le projet.');
        $collecte[] =
        '<h2 class="h4 open-sans"><span aria-hidden="true">🔜</span>12 - Collecte des todo pour le projet</h2>';

        $todo = $this->batchCollecteTodo->batchCollecteTodo(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($todo['code'] === 200){
            $item = $todo['historique'];
            $collecte[] =
            '<p class="open-sans">'. $todo['message'] .'</p>
            <div>
                <ul class="open-sans">
                    <li class="lead">Nombre de todo : ' . $item['todo'] . '</li>
                </ul>
            </div>';

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $todo['historique']);
        } else {
            $this->logger->error('[Batch] ❌ Erreur lors du traitement des todo du projet.', [
                'code' => $noSonar['code'],
                'message' => $noSonar['message'] ?? static::$noMessage,
                'erreur' => $noSonar['erreur'] ?? static::$noError,
            ]);

            $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>12 Fin de la collecte des todo pour le projet</h2>';

            $erreurContain = $this->generateHtmlErrorContain(
                $todo['code'],
                $todo['message'] ?? static::$noMessage,
                $todo['erreur'] ?? static::$noError );

            $collecte[] = $erreurTitre . $erreurContain;
            $compte_rendu = implode("\n", $collecte);

            return [
                'code' => $todo['code'],
                'compte_rendu' => $compte_rendu
            ];
        }

        /** Actuator Info du projet (java spring-boot) */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des informations Actuator pour le projet.');
        $collecte[] =
        '<h2 class="h4 open-sans"><span aria-hidden="true">🔜</span>13 - Collecte des informations Actuator pour le projet</h2>';

        $actuatorInfo = $this->batchCollecteActuator->BatchCollecteActuatorInfo($maven_key);
        if ($actuatorInfo['code'] === 200 || $actuatorInfo['code'] === 404){
            $collecte[] =
            '<p class="open-sans">'. $actuatorInfo['message'] .'</p>';

            /** Information a joindre dans l'historique */
            $json = $actuatorInfo['dataJson']['json'] ?? '{}';
        } else {
                $this->logger->error('[Batch] ❌ Erreur lors du traitement des informations actuator du projet.', [
                        'code' => $actuatorInfo['code'],
                        'message' => $actuatorInfo['message'] ?? static::$noMessage,
                        'erreur' => $actuatorInfo['erreur'] ?? static::$noError,
                ]);

                $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>13 Fin de la collecte des informations actuator pour le projet</h2>>';

                $erreurContain = $this->generateHtmlErrorContain(
                    $actuatorInfo['code'],
                    $actuatorInfo['message'] ?? static::$noMessage,
                    $actuatorInfo['erreur'] ?? static::$noError );

                $collecte[] = $erreurTitre . $erreurContain;
                $compte_rendu = implode("\n", $collecte);

                return [
                    'code' => $actuatorInfo['code'],
                    'compte_rendu' => $compte_rendu];
        }

        /** Logger dans le projet (info, warn, error, debug) */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des Logger Java pour le projet.');
        $collecte[] =
        '<h2 class="h4 open-sans"><span aria-hidden="true">🔜</span>14 - Collecte des Logger Java pour le projet</h2>';

        $loggerJava = $this->batchCollecteLogger->BatchCollecteLogger(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($loggerJava['code'] === 200 || $loggerJava['code'] === 404){
            $item = $loggerJava['historique'];

            $collecte[] =
            '<p class="open-sans">'. $loggerJava['message'] .'</p>
            <div>
                <ul class="open-sans">
                    <li class="lead">Nombre de Logger INFO : ' . $item['logger_info'] . '</li>
                    <li class="lead">Nombre de Logger WARN : ' . $item['logger_warn'] . '</li>
                    <li class="lead">Nombre de Logger ERROR : ' . $item['logger_error'] . '</li>
                    <li class="lead">Nombre de Logger DEBUG : ' . $item['logger_debug'] . '</li>
                </ul>
            </div>';

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $loggerJava['historique']);
        } else {
                $this->logger->error('[Batch] ❌ Erreur lors du traitement des Logger Java pour le projet.', [
                            'code' => $loggerJava['code'],
                            'message' => $loggerJava['message'] ?? static::$noMessage,
                            'erreur' => $loggerJava['erreur'] ?? static::$noError,
                            'tracker' => $loggerJava['tracker'] ?? static::$noError,
                    ]);

                $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔚</span>Fin de la collecte des informations du projet</h2>';
                $erreurSousTitre = '<h3> class="h5 opens-sans"><span aria-hidden="true">🔠</span>Level : '. $loggerJava['tracker'] ?? static::$noError .'<h3>';

                $erreurContain = $this->generateHtmlErrorContain(
                    $informationProjet['code'],
                    $informationProjet['message'] ?? static::$noMessage,
                    $informationProjet['erreur'] ?? static::$noError );

                $collecte[] = $erreurTitre . $erreurSousTitre .$erreurContain;
                $compte_rendu = implode("\n", $collecte);

            return [
                'code' => $loggerJava['code'],
                'compte_rendu' => $compte_rendu
            ];
        }

        /** Création de la date du jour */
        $this->logger->info('[Batch] ℹ️ Démarrage du traitement des données pour la table historique.');
        $collecte[] =
        '<h2 class="h4 open-sans"><span aria-hidden="true">📜</span>15 - Consolidation des données dans la table historique</h2>';

        $date_historique = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));

        /** Consolidation des données */
        $mergeHistorique = array_merge($mergeHistorique, [
            'maven_key' => $maven_key,
            'initial' => false,
            'mode_collecte' => $mode_collecte,
            'utilisateur_collecte' => $utilisateur_collecte,
            'date_enregistrement' => $date_historique]
        );

        $this->logger->info('[Batch] ℹ️ Mise à jour de la table Collecte.');
        /** Enregistrement dans le table historique */

        $historique = $historiqueRepos->insertHistoriqueAjoutProjet($mergeHistorique, $json);

        if ($historique['code'] === 200){
            $html = $this->generateHtmlFromArray($mergeHistorique);
            $collecte[] =
            '<p class="open-sans">Données collectées</p>
            <div>'. $html. '</div>';
        } else {
                $this->logger->critical('[Batch] 🔴 Erreur lors du traitement des données pour la table historique.', [
                    'code' => $loggerJava['code'],
                    'message' => $loggerJava['message'] ?? static::$noMessage,
                    'erreur' => $loggerJava['erreur'] ?? static::$noError,
                ]);

                $erreurTitre = '<h2 class="h4 open-sans"><span aria-hidden="true">🔴</span>Échec de mise à jour de la table Historique</h2>';

                $erreurContain = $this->generateHtmlErrorContain(
                    $historique['code'],
                    $historique['message'] ?? static::$noMessage,
                    $historique['erreur'] ?? static::$noError );

                $collecte[] = $erreurTitre . $erreurContain;
                $compte_rendu = implode("\n", $collecte);

            return [
                'code'=> 500,
                'compte_rendu' => $compte_rendu
            ];
        }

        /** Fin du traitement */
        $finTraitement = new \DateTime('now', new \DateTimeZone(static::$europeParis));
        $interval = $debutTraitement->diff($finTraitement);

        $collecte[] =
        '<h2 class="h4 open-sans"><span aria-hidden="true">🎉</span>Collecte terminée pour ce projet</h2>
        <div class="fieldset">
            <ul class="open-sans">
                <li class="lead">Portefeuille : '. $portefeuille . '</li>
                <li class="lead">Projet : ' . $maven_key . '</li>
                <li class="lead">Mode : ' . $mode_collecte .'</li>
                <li class="lead">Démarrage : ' . $debutTraitement->format(static::$dateFormat) .'</li>
                <li class="lead">Fin : ' . $finTraitement->format(static::$dateFormat) . '</li>
                <li class="lead">Temps d\'exécution : ' . $interval->format('%H:%i:%s.%f') . '</li>
            </ul>
        </div>';

        /** Rapport de collecte */
        $this->logger->info('[Batch] ℹ️ Sauvegarde du conte-rendu pour le projet.');
        $compte_rendu = implode("\n", $collecte);

        return [
            'code' => 200,
            'message' => 'La collecte et la mise à jour de la table historique pour ce projet est terminée.',
            'historique' => $mergeHistorique,
            'compte_rendu' => $compte_rendu
        ];
    }
}
