<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Batch;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Historique;

/** Class API Batch */
use App\Controller\Batch\{BatchCollecteInformationProjetController, BatchCollecteMesureController, BatchCollecteOwaspController, BatchCollecteHotspotController,BatchCollecteAnomalieController, BatchCollecteAnomalieDetailController, BatchCollecteHotspotOwaspController, BatchCollecteHotspotDetailController, BatchCollecteNoSonarController, BatchCollecteTodoController,BatchCollecteActuatorController};

/**
 * [Description CollecteController]
 */
class CollecteController extends AbstractController
{
    private static string $dateFormat = 'Y-m-d H:i:s';
    private static string $europeParis = 'Europe/Paris';
    private static string $noMessage = 'Aucun message remonté.';
    private static string $noError = 'Aucune erreur remontée.';
    private static string $jeNeSaisPas = 'Je ne sais pas.';
    // MODIF 2026-05-26 : extraction des string literals HTML dupliqués (S1192).
    private static string $htmlSectionOpen  = "\n            <section>\n                <p>";
    private static string $htmlSectionClose = "</li>\n                </ul>\n            </section>";
    private static string $htmlDetteMinute  = "</li>\n                    <li><span>Dette en minute : </span>";
    /* MODIF 2026-05-04 : suppression $erreurFinTraitement (uniquement utilisé par les blocs notes retirés) */

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
    }

    /**
     * [Description for generateHtmlForItem]
     *
     * @param array<int|string, mixed> $item
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
                    <td>' . $item['date_version']->format(self::$dateFormat) . '</td>
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
                    <td>' . $item['date_enregistrement']->format(self::$dateFormat) . '</td>
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
    private function generateHtmlErrorContain(int|string $code, string $message, string $erreur): string
    {
        return '<div class="callout alert-callout-border alert callout-ombre"><p>
                        🔴 Le traitement de collecte pour ce projet est arrêté.</p>
                </div>
                <div>
                    <p><strong>Traces :</strong></p>
                    <ul>
                        <li><span>Code : </span>'. $code .'</li>
                        <li><span>Message : </span>'. $message .'</li>
                        <li><span>Erreur : </span>'. $erreur .'</li>
                    </ul>
                </div>';
    }

    /**
     * [Description for collecte]
     *

     * @return array<int|string, mixed>
     *
     * Created at: 11/06/2024 12:58:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function collecte(string $portefeuille, string $maven_key, string $mode_collecte, string $utilisateur_collecte): array
    {
        /** On instancie l'entityRepository */
        $historiqueRepos = $this->em->getRepository(Historique::class);

        /** On initialise la log */
        $collecte = [];

        /** On nettoie les variables du POST */
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $mode_collecte = htmlspecialchars($mode_collecte, ENT_QUOTES, 'UTF-8');

        /** On démarre la mesure du traitement */
        $debutTraitement = new \DateTime('now', new \DateTimeZone(self::$europeParis));
        $this->logger->info("[Batch] ℹ️ Démarrage du traitement différé en mode {$mode_collecte}.");

        $collecte[] =
            '<div role="banner">
                <h1 class="h4"><span aria-hidden="true">🎬</span>Début du traitement</h1>
                <div class="callout alert-callout-border secondary">
                    <ul>
                        <li><span>Portefeuille : </span>'. $portefeuille . '</li>
                        <li><span>Projet : </span>' . $maven_key . '</li>
                        <li><span>Mode : </span>' . $mode_collecte .'</li>
                        <li><span>Date : </span>' . $debutTraitement->format(self::$dateFormat) .'</li>
                    </ul>
                </div>
            </div>';

        /** On récupère les données du projet */
        $mergeHistorique = [];

        /** Informations du projet (nom, type de version) */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des informations du projet.');
        $collecte[] =
        '<h2 class="h5 "><span aria-hidden="true">🔜</span>01 - Collecte pour les informations du projet</h2>';

        $informationProjet = $this->batchCollecteInformation->batchCollecteInformation(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte
        );

        /** Tout vas bien, on peut lancer la collecte */
        if ($informationProjet['code'] === 200){
            $item = $informationProjet['historique'];
            $collecte[] = self::$htmlSectionOpen . $informationProjet['message'] .'</p>
                <ul>
                    <li><span>Clé d’analyse : </span>' . $item['analyse_key'] . '</li>
                    <li><span>Version du projet : </span>' . $item['version'] . '</li>
                    <li><span>date de la Version du projet : </span>' . $item['date_version'] . '</li>
                    <li><span>Nombre Release (ma-moulinette) : </span>' . $item['version_release'] .'</li>
                    <li><span>Nombre Snapshot (ma-moulinette) : </span>' . $item['version_snapshot'] .'</li>
                    <li><span>Nombre Autre (ma-moulinette) : </span>' . $item['version_autre'] .'</li>
                    <li><span>Nombre Release (sonar) : </span>' . $item['version_release_sonar'] .'</li>
                    <li><span>Nombre Snapshot (sonar) : </span>' . $item['version_snapshot_sonar'] .'</li>
                    <li><span>Nombre Autre (sonar) : </span>' . $item['version_autre_sonar'] .'</li>
                    <li><span>Nombre total (sonar) : </span>' . $item['version_sonar'] .'</li>
                    <li><span>Nombre Release (sonar) : </span>' . $item['version_release_sonar'] .self::$htmlSectionClose;

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $informationProjet['historique']);
        }

        /** Le projet existe déjà, il est à jour, on arrête la collecte ou pn met à jour les informations. */
        if ($informationProjet['code'] === 100){
            $this->logger->info('[Batch] ℹ️ Mise à jour pour le projet inutile.');
            /** Pas de mise à jour local = historique = SonarQube */
            $item = $informationProjet['historique'];
            if (isset($informationProjet['historique']) && isset($informationProjet['historique']['Locale'])){
            $collecte[] = self::$htmlSectionOpen . $informationProjet['message'] .'</p>
                <ul>
                    <li><span>Code : </span>' . $item['code'] . '</li>
                    <li><span>Mode : </span>' . $item['mode_collecte'] .'</li>
                </ul>
                <p>Version ma-moulinette.</p>
                <ul>
                    <li><span>Nom : </span>' . $item['Locale']['name'] . '</li>
                    <li><span>Clé d’analyse : </span>' . $item['Locale']['key-analyse'] .'</li>
                    <li><span>version : </span>' . $item['Locale']['version'] .'</li>
                    <li><span>Date analyse : </span>' . $item['Locale']['date-analyse'] .'</li>
                </ul>
                <p>Version SonarQube.</p>
                <ul>
                    <li><span>Clé d’analyse : </span>' . $item['SonarQube']['key-analyse'] .'</li>
                    <li><span>version : </span>' . $item['SonarQube']['version'] .'</li>
                </ul>
            </section>
            <h2 class="h5"><span aria-hidden="true">🔚</span>01 - Fin de la collecte des Informations du projet</h2>
            <div class="callout alert-callout-border warning callout-ombre"><p>
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
                'message' => $informationProjet['message'] ?? self::$noMessage,
                'erreur' => $informationProjet['erreur'] ?? self::$noError,

            ]);

            $erreurTitre = '<h2 class="h5"><span aria-hidden="true">🔚</span>Fin de la collecte des informations du projet</h2>';

            $erreurContain = $this->generateHtmlErrorContain(
                $informationProjet['code'],
                $informationProjet['message'] ?? self::$noMessage,
                $informationProjet['erreur'] ?? self::$noError );

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
        '<h2 class="h5"><span aria-hidden="true">🔜</span>02 - Collecte des mesures pour le projet</h2>';

        $mesure = $this->batchCollecteMesure->batchCollecteMesure(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($mesure['code'] === 200){
            $item = $mesure['historique'];
            $data = $mesure['data'];
            $collecte[] = self::$htmlSectionOpen . $mesure['message'] .'</p>
                <ul>
                <li><span>Clé maven : </span>' . $data['maven_key'] . '</li>
                <li><span>Nombre de lignes : </span>' . $item['lines'] . '</li>
                <li><span>Nombre de lignes de code : </span>' . $item['ncloc'] . '</li>
                <li><span>Distribution des lignes de code par langage : </span>' . $item['ncloc_language_distribution'] . '</li>
                <li><span>Nombre de fichiers : </span>' . $item['files'] . '</li>
                <li><span>Nombre de classes : </span>' . $item['classes'] . '</li>
                <li><span>Nombre de fonctions : </span>' . $item['functions'] . '</li>
                <li><span>Nombre de lignes de commentaires : </span>' . $item['comment_lines'] . '</li>
                <li><span>Densité des lignes de commentaires : </span>' . $item['comment_lines_density'] . '</li>
                <li><span>Notation des lignes de commentaires : </span>' . $item['comment_lines_rating'] . '</li>
                <li><span>Couverture du code : </span>' . $item['coverage'] . '</li>
                <li><span>Couverture des branches : </span>' . $item['branch_coverage'] . '</li>
                <li><span>Couverture des lignes : </span>' . $item['line_coverage'] . '</li>
                <li><span>Lignes à couvrir : </span>' . $item['lines_to_cover'] . '</li>
                <li><span>Conditions à couvrir : </span>' . $item['conditions_to_cover'] . '</li>
                <li><span>Conditions non couvertes : </span>' . $item['uncovered_conditions'] . '</li>
                <li><span>Notation de la couverture : </span>' . $item['coverage_rating'] . '</li>
                <li><span>Tests : </span>' . $item['tests'] . '</li>
                <li><span>Temps d\'exécution des tests : </span>' . $item['test_execution_time'] . '</li>
                <li><span>Erreurs de test : </span>' . $item['test_errors'] . '</li>
                <li><span>Échecs de test : </span>' . $item['test_failures'] . '</li>
                <li><span>Tests ignorés : </span>' . $item['skipped_tests'] . '</li>
                <li><span>Densité des tests réussis : </span>' . $item['test_success_density'] . '</li>
                <li><span>Blocs en double : </span>' . $item['duplicated_blocks'] . '</li>
                <li><span>Fichiers en double : </span>' . $item['duplicated_files'] . '</li>
                <li><span>Lignes en double : </span>' . $item['duplicated_lines'] . '</li>
                <li><span>Densité des lignes en double : </span>' . $item['duplicated_lines_density'] . '</li>
                <li><span>Notation des lignes en double : </span>' . $item['duplicated_lines_rating'] . '</li>
                <li><span>Problèmes ouverts : </span>' . $item['open_issues'] . '</li>
                <li><span>Problèmes réouverts : </span>' . $item['reopened_issues'] . '</li>
                <li><span>Problèmes confirmés : </span>' . $item['confirmed_issues'] . '</li>
                <li><span>Problèmes à faux positif : </span>' . $item['false_positive_issues'] . '</li>
                <li><span>Problèmes à haut impact acceptés : </span>' . $item['high_impact_accepted_issues'] . '</li>
                <li><span>Problèmes acceptés : </span>' . $item['accepted_issues'] . '</li>
                <li><span>Violations : </span>' . $item['violations'] . '</li>
                <li><span>Violations bloquantes : </span>' . $item['blocker_violations'] . '</li>
                <li><span>Violations critiques : </span>' . $item['critical_violations'] . '</li>
                <li><span>Violations majeures : </span>' . $item['major_violations'] . '</li>
                <li><span>Violations mineures : </span>' . $item['minor_violations'] . '</li>
                <li><span>Violations d\'information : </span>' . $item['info_violations'] . '</li>
                <li><span>Problèmes de qualité logicielle bloquants : </span>' . $item['software_quality_blocker_issues'] . '</li>
                <li><span>Problèmes de qualité logicielle à haut impact : </span>' . $item['software_quality_high_issues'] . '</li>
                <li><span>Problèmes de qualité logicielle d\'information : </span>' . $item['software_quality_info_issues'] . '</li>
                <li><span>Problèmes de qualité logicielle à bas impact : </span>' . $item['software_quality_low_issues'] . '</li>
                <li><span>Problèmes de qualité logicielle à moyen impact : </span>' . $item['software_quality_medium_issues'] . '</li>
                <li><span>Complexité : </span>' . $item['complexity'] . '</li>
                <li><span>Complexité cognitive : </span>' . $item['cognitive_complexity'] . '</li>
                <li><span>Rapport de complexité : </span>' . $item['complexity_ratio'] . '</li>
                <li><span>Rapport de complexité cognitive : </span>' . $item['cognitive_complexity_ratio'] . '</li>
                <li><span>Notation de la complexité : </span>' . $item['complexity_rating'] . '</li>
                <li><span>Notation de la complexité cognitive : </span>' . $item['cognitive_complexity_rating'] . '</li>
                <li><span>Smells de code : </span>' . $item['code_smells'] . '</li>
                <li><span>Index SQALE : </span>' . $item['sqale_index'] . '</li>
                <li><span>Notation SQALE : </span>' . $item['sqale_rating'] . '</li>
                <li><span>Rapport de dette SQALE : </span>' . $item['sqale_debt_ratio'] . '</li>
                <li><span>Problèmes de maintenabilité : </span>' . $item['maintainability_issues'] . '</li>
                <li><span>Notation de la maintenabilité de la qualité logicielle : </span>' . $item['software_quality_maintainability_rating'] . '</li>
                <li><span>Rapport de dette de la maintenabilité de la qualité logicielle : </span>' . $item['software_quality_maintainability_debt_ratio'] . '</li>
                <li><span>Effort pour atteindre la notation A de maintenabilité : </span>' . $item['effort_to_reach_maintainability_rating_a'] . '</li>
                <li><span>Effort de remédiation pour la maintenabilité de la qualité logicielle : </span>' . $item['software_quality_maintainability_remediation_effort'] . '</li>
                <li><span>Problèmes de maintenabilité de la qualité logicielle : </span>' . $item['software_quality_maintainability_issues'] . '</li>
                <li><span>Effort pour atteindre la notation A de la qualité logicielle : </span>' . $item['effort_to_reach_software_quality_maintainability_rating_a'] . '</li>
                <li><span>Bugs : </span>' . $item['bugs'] . '</li>
                <li><span>Notation de la fiabilité : </span>' . $item['reliability_rating'] . '</li>
                <li><span>Effort de remédiation pour la fiabilité : </span>' . $item['reliability_remediation_effort'] . '</li>
                <li><span>Problèmes de fiabilité : </span>' . $item['reliability_issues'] . '</li>
                <li><span>Notation de la fiabilité de la qualité logicielle : </span>' . $item['software_quality_reliability_rating'] . '</li>
                <li><span>Effort de remédiation pour la fiabilité de la qualité logicielle : </span>' . $item['software_quality_reliability_remediation_effort'] . '</li>
                <li><span>Problèmes de fiabilité de la qualité logicielle : </span>' . $item['software_quality_reliability_issues'] . '</li>
                <li><span>Vulnérabilités : </span>' . $item['vulnerabilities'] . '</li>
                <li><span>Notation de la sécurité : </span>' . $item['security_rating'] . '</li>
                <li><span>Effort de remédiation pour la sécurité : </span>' . $item['security_remediation_effort'] . '</li>
                <li><span>Notation de la sécurité de la qualité logicielle : </span>' . $item['software_quality_security_rating'] . '</li>
                <li><span>Effort de remédiation pour la sécurité de la qualité logicielle : </span>' . $item['software_quality_security_remediation_effort'] . '</li>
                <li><span>Problèmes de sécurité de la qualité logicielle : </span>' . $item['software_quality_security_issues'] . '</li>
                <li><span>Points chauds de sécurité : </span>' . $item['security_hotspots'] . '</li>
                <li><span>Points chauds de sécurité revus : </span>' . $item['security_hotspots_reviewed'] . '</li>
                <li><span>Notation de revue de sécurité : </span>' . $item['security_review_rating'] . '</li>
                <li><span>Clé maven : </span>' . $item['maven_key'] . '</li>
                <li><span>Nom du projet : </span>' . $item['project_name'] . '</li>
                <li><span>Mode de collecte : </span>' . $item['mode_collecte'] . '</li>
                <li><span>Utilisateur de collecte : </span>' . $item['utilisateur_collecte'] . self::$htmlSectionClose;

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $mesure['historique']);
        } else {
            $this->logger->info('[Batch] ❌ Erreur lors du traitement des mesures du projet.', [
                'code' => $mesure['code'],
                'message' => $mesure['message'] ?? self::$noMessage,
                'erreur' => $mesure['erreur'] ?? self::$noError,
            ]);

            $erreurTitre = '<h2 class="h5"><span aria-hidden="true">🔚</span>02 - Fin de la collecte des mesures pour le projet</h2>';
            $erreurContain = $this->generateHtmlErrorContain(
                $mesure['code'],
                $mesure['message'] ?? self::$noMessage,
                $mesure['erreur'] ?? self::$noError );

            $collecte[] = $erreurTitre . $erreurContain;
            $compte_rendu = implode("\n", $collecte);

            return [
                    'code' => $mesure['code'],
                    'compte_rendu' => $compte_rendu
                ];
        }

        /** Signalement des Anomalies pour le projet  */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des anomalies pour le projet.');
        $collecte[] =
            '<h2 class="h5"><span aria-hidden="true">🔜</span>04 - Collecte des anomalies pour le projet</h2>';

        $anomalie = $this->batchCollecteAnomalie->batchCollecteAnomalie(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($anomalie['code'] === 200){
            $item = $anomalie['data'];
            $collecte[] = self::$htmlSectionOpen . $anomalie['message'] .'</p>
                <ul>
                    <li><span>Nombre d’anomalie total : </span>' . $item['anomalie_total'] . '</li>
                    <li><span>Dette technique : </span>' . $item['dette'] . self::$htmlDetteMinute . $item['dette_minute'] . '</li>
                    <li><span>Dette reliability : </span>' . $item['dette_reliability'] . self::$htmlDetteMinute . $item['dette_reliability_minute'] . '</li>
                    <li><span>Dette vulnerability : </span>' . $item['dette_vulnerability'] . self::$htmlDetteMinute . $item['dette_vulnerability_minute'] . '</li>
                    <li><span>Dette code_smell : </span>' . $item['dette_code_smell'] . self::$htmlDetteMinute . $item['dette_code_smell_minute'] . '</li>
                    <li><span>Nombre d’anomalie de type BUG : </span>' . $item['bug'] . '</li>
                    <li><span>Nombre d’anomalie de type VULNERABILITY : </span>' . $item['vulnerability'] . '</li>
                    <li><span>Nombre d’anomalie de type CODE_SMELL : </span>' . $item['code_smell'] . '</li>
                    <li><span>Nombre d’anomalie frontend : </span>' . $item['frontend'] . '</li>
                    <li><span>Nombre d’anomalie backend : </span>' . $item['backend'] . '</li>
                    <li><span>Nombre d’anomalie autre : </span>' . $item['autre'] . '</li>
                    <li><span>Nombre d’anomalie inconnu : </span>' . $item['inconnu'] . '</li>
                    <li><span>Nombre d’anomalie bloquant : </span>' . $item['blocker'] . '</li>
                    <li><span>Nombre d’anomalie critique: </span>' . $item['critical'] . '</li>
                    <li><span>Nombre d’anomalie majeur: </span>' . $item['major'] . '</li>
                    <li><span>Nombre d’anomalie en info: </span>' . $item['info'] . '</li>
                    <li><span>Nombre d’anomalie mineur: </span>' . $item['minor'] . self::$htmlSectionClose;

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $anomalie['historique']);
        } else {
            $this->logger->error('[Batch] ❌ Erreur lors du traitement des anomalies du projet.', [
                    'code' => $anomalie['code'],
                    'type' => $anomalie['type'] ?? '',
                    'message' => $anomalie['message'] ?? self::$noMessage,
                    'erreur' => $anomalie['erreur'] ?? self::$noError,
            ]);

            $erreurTitre = '<h2 class="h5"><span aria-hidden="true">🔚</span>04 - Fin de la collecte des anomalies pour le projet</h2>';
            $erreurSousTitre = '<h3> class="h5 opens-sans"><span aria-hidden="true">🔠</span>Type : </span>'. ($anomalie['type'] ?? ' Information manquante') .'<h3>';

            $erreurContain = $this->generateHtmlErrorContain(
                    $anomalie['code'],
                    $anomalie['message'] ?? self::$noMessage,
                    $anomalie['erreur'] ?? self::$noError );

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
            '<h2 class="h5"><span aria-hidden="true">🔜</span>05 - Collecte du détails des anomalies pour le projet</h2>';

        $anomalieDetail = $this->batchCollecteAnomalieDetail->BatchCollecteAnomalieDetail(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($anomalieDetail['code'] === 200){
            $item = $anomalieDetail['historique'];
            if (empty($item)){
                $msg = htmlspecialchars($anomalieDetail['message']);
                $collecte[] = '<p>'. $msg .'</p>';
            } else  {
                $collecte[] ='
                <section>
                    <p>'. htmlspecialchars($anomalieDetail['message']) .'</p>
                    <ul>
                        <li><span>Nombre de Bug Bloquant : </span>' . htmlspecialchars($item['bug_blocker']) . '</li>
                        <li><span>Nombre de Bug Critique : </span>' . htmlspecialchars($item['bug_critical']) . '</li>
                        <li><span>Nombre de Bug Majeur : </span>' . htmlspecialchars($item['bug_major']) . '</li>
                        <li><span>Nombre de Bug Mineur : </span>' . htmlspecialchars($item['bug_minor']) . '</li>
                        <li><span>Nombre de Bug Info : </span>' . htmlspecialchars($item['bug_info']) . '</li>
                        <li><span>Nombre de Vulnérabilité Bloquant : </span>' . htmlspecialchars($item['vulnerability_blocker']) . '</li>
                        <li><span>Nombre de Vulnérabilité Critique : </span>' . htmlspecialchars($item['vulnerability_critical']) . '</li>
                        <li><span>Nombre de Vulnérabilité Majeur : </span>' . htmlspecialchars($item['vulnerability_major']) . '</li>
                        <li><span>Nombre de Vulnérabilité Mineur : </span>' . htmlspecialchars($item['vulnerability_minor']) . '</li>
                        <li><span>Nombre de Vulnérabilité Info : </span>' . htmlspecialchars($item['vulnerability_info']) . '</li>
                        <li><span>Nombre de Mauvaise Pratique Bloquant : </span>' . htmlspecialchars($item['code_smell_blocker']) . '</li>
                        <li><span>Nombre de Mauvaise Pratique Critique : </span>' . htmlspecialchars($item['code_smell_critical']) . '</li>
                        <li><span>Nombre de Mauvaise Pratique Majeur : </span>' . htmlspecialchars($item['code_smell_major']) . '</li>
                        <li><span>Nombre de Mauvaise Pratique Mineur : </span>' . htmlspecialchars($item['code_smell_minor']) . '</li>
                        <li><span>Nombre de Mauvaise Pratique Info : </span>' . htmlspecialchars($item['code_smell_info']) . '</li>
                    </ul>
                </section>';
            }

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $anomalieDetail['historique']);
        } else {
            $this->logger->error('[Batch] ❌ Erreur lors du traitement du détail des anomalies du projet.', [
                    'code' => $anomalieDetail['code'],
                    'type' => $anomalieDetail['type'] ?? '',
                    'message' => $anomalieDetail['message'] ?? self::$noMessage,
                    'erreur' => $anomalieDetail['erreur'] ?? self::$noError,
            ]);

            $erreurTitre = '<h2 class="h5"><span aria-hidden="true">🔚</span>05 - Fin de la collecte du détails des anomalies pour le projet</h2>';
            $erreurSousTitre = '<h3> class="h5 opens-sans"><span aria-hidden="true">🔠</span>Type : </span>'. ($anomalieDetail['type'] ?? 'Information manquante') .'<h3>';

            $erreurContain = $this->generateHtmlErrorContain(
                    $anomalieDetail['code'],
                    $anomalieDetail['message'] ?? self::$noMessage,
                    $anomalieDetail['erreur'] ?? self::$noError );

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
        '<h2 class="h5"><span aria-hidden="true">🔜</span>06 - Collecte des menaces potentielles pour le projet</h2>';

        $hotspot = $this->batchCollecteHotspot->batchCollecteHotspot(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($hotspot['code'] === 200){
            $item = $hotspot['historique'];
            $collecte[] = self::$htmlSectionOpen . $hotspot['message'] .'</p>
                <ul>
                    <li><span>Nombre de Hotspot de niveau High : </span>' . $item['hotspot_high'] . '</li>
                    <li><span>Nombre de Hotspot de niveau Medium : </span>' . $item['hotspot_medium'] . '</li>
                    <li><span>Nombre de Hotspot de niveau Low : </span>' . $item['hotspot_low'] . '</li>
                    <li><span>Nombre de Hotspot : </span>' . $item['nombre_hotspot'] . self::$htmlSectionClose;

        } else {
            $this->logger->error('[Batch] ❌ Erreur lors du traitement des menaces du projet.', [
                    'code' => $hotspot['code'],
                    'message' => $hotspot['message'] ?? self::$noMessage,
                    'erreur' => $hotspot['erreur'] ?? self::$noError,
            ]);

            $erreurTitre = '<h2 class="h5"><span aria-hidden="true">🔚</span>06 - Fin de la collecte des menaces potentielles pour le projet</h2>';
            $erreurContain = $this->generateHtmlErrorContain(
                $hotspot['code'],
                $hotspot['message'] ?? self::$noMessage,
                $hotspot['erreur'] ?? self::$noError );

            $collecte[] = $erreurTitre . $erreurContain;
            $compte_rendu = implode("\n", $collecte);

            return [
                'code' => $hotspot['code'],
                'compte_rendu' => $compte_rendu
            ];
        }

        /** Signalement du détail des Hotspots pour le projet */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte du détail des hotspots pour le projet.');
        $collecte[] =
        '<h2 class="h5"><span aria-hidden="true">🔜</span>08 - Collecte du détail des menaces potentielles pour le projet</h2>';

        $hotspotDetails = $this->batchCollecteHotspotDetail->batchCollecteHotspotDetail(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($hotspotDetails['code'] === 200){
            $item = $hotspotDetails['historique'];
            if (empty($item)){
                $msg = htmlspecialchars($hotspotDetails['message']);
                $collecte[] ='<p>'. $msg .'</p>';
            } else  {
                //"menace_potentielle_totale" => 61

                $collecte[] ='
                <section>
                    <p>'. htmlspecialchars($hotspotDetails['message']) .'</p>
                    <ul>
                        <li><span>Nombre d’élément dans la liste  : </span>' .
                            (htmlspecialchars($hotspotDetails['nombre']) ?: self::$jeNeSaisPas) . '</li>
                        <li><span>Nombre de menaces total  : </span>' .
                            (htmlspecialchars($item['menace_potentielle_totale']) ?: self::$jeNeSaisPas) . '</li>
                        <li><span>Nombre de menaces de niveau HIGH au statut REVIEW : </span>' .
                            (htmlspecialchars($item['menace_potentielle_to_review_high']) ?: self::$jeNeSaisPas) . '</li>
                        <li><span>Menaces de niveau MEDIUM au statut REVIEW : </span>' .
                            (htmlspecialchars($item['menace_potentielle_to_review_medium']) ?: self::$jeNeSaisPas) . '</li>
                        <li><span>Menaces de niveau LOW au statut REVIEW : </span>' .
                            (htmlspecialchars($item['menace_potentielle_to_review_low']) ?: self::$jeNeSaisPas) . '</li>
                        <li><span>Menaces de niveau HIGH au statut REVIEWED : </span>' .
                            (htmlspecialchars($item['menace_potentielle_reviewed_high']) ?: self::$jeNeSaisPas) . '</li>
                        <li><span>Menaces de niveau MEDIUM au statut REVIEWED : </span>' .
                            (htmlspecialchars($item['menace_potentielle_reviewed_medium']) ?: self::$jeNeSaisPas) . '</li>
                        <li><span>Menaces de niveau LOW au statut REVIEWED : </span>' .
                        (htmlspecialchars($item['menace_potentielle_reviewed_low']) ?: self::$jeNeSaisPas) . '</li>
                    </ul>
                </section>';
                /** On met à jour le tableau des données pour la table historique */
                $mergeHistorique = array_merge($mergeHistorique, $hotspotDetails['historique']);
            }
        } else {
            $this->logger->error('[Batch] ❌ Erreur lors du traitement du détail des menaces potentielles du projet.', [
                        'code' => $hotspotDetails['code'],
                        'message' => $hotspotDetails['message'] ?? self::$noMessage,
                        'erreur' => $hotspotDetails['erreur'] ?? self::$noError,
                ]);

            $erreurTitre = '<h2 class="h5"><span aria-hidden="true">🔚</span>08 - Fin de la collecte du détail des menaces potentielles pour le projet</h2>';

            $erreurContain = $this->generateHtmlErrorContain(
                $hotspotDetails['code'],
                $hotspotDetails['message'] ?? self::$noMessage,
                $hotspotDetails['erreur'] ?? self::$noError );

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
        '<h2 class="h5"><span aria-hidden="true">🔜</span>09 - Collecte des menaces OWASP pour le projet</h2>';

        $owasp = $this->batchCollecteOwasp->batchCollecteOwasp(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($owasp['code'] === 200){
            $collecte[] ='
            <section>
                <p>'. $owasp['message'] .'</p>
                <ul>
                    <li><span>Nombre de menace 2017  : </span>' . $owasp['owasp2017'] . '</li>
                    <li><span>Nombre de menace 2021  : </span>' . $owasp['owasp2021'] . '</li>
                </ul>
            </section>';
                //<li><span>Nombre de menace 2025  : </span>' . isset($owasp['owasp2025']) ? $owasp['owasp2025'] : "Non disponible pour le moment" . '</li>

            /** Pas de données historisées */
        } else {
            $this->logger->error('[Batch] ❌ Erreur lors du traitement des menaces OWASP du projet.', [
                        'code' => $owasp['code'],
                        'message' => $owasp['message'] ?? self::$noMessage,
                        'erreur' => $owasp['erreur'] ?? self::$noError,
                ]);

            $erreurTitre = '<h2 class="h5"><span aria-hidden="true">🔚</span>09 - Fin de la collecte des menaces OWASP pour le projet</h2>';

            $erreurContain = $this->generateHtmlErrorContain(
                $owasp['code'],
                $owasp['message'] ?? self::$noMessage,
                $owasp['erreur'] ?? self::$noError );

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
            '<h2 class="h5"><span aria-hidden="true">🔜</span>10 - Collecte des menaces OWASP potentielles pour le projet</h2>';

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
                    '<p>'. $hotspotOwasp['message'].'</p>
                    <div>
                        <ul>
                            <li><span>Mode  : </span>' . $hotspotOwasp['info'] . '</li>
                            <li><span>Menace  : </span>' . strtoupper($owaspKey) . '</li>
                            <li><span>Nombre de menace 2017  : </span>' . $hotspotOwasp['owasp_2017'] . '</li>
                            <li><span>Nombre de menace 2021  : </span>' . $hotspotOwasp['owasp_2021'] . '</li>
                        </ul>
                    </div>
                    <p>Détail</p>
                    <div class="callout">'. $html .'</div>';
                    //# <li><span>Nombre de menace 2025  : </span>' . $hotspotOwasp['owasp_2025'] ?? "Non disponible pour le moment" . '</li> #}
                }
            } else {
                $this->logger->error('[Batch] ❌ Erreur lors du traitement des menaces OWASP potentielles du projet.', [
                        'code' => $hotspotOwasp['code'],
                        'type' => $hotspotOwasp['type'] ?? 'inconnu',
                        'message' => $hotspotOwasp['message'] ?? self::$noMessage,
                        'erreur' => $hotspotOwasp['erreur'] ?? self::$noError,
                ]);

                $erreurTitre = '<h2 class="h5"><span aria-hidden="true">🔚</span>10 - Fin de la collecte des menaces OWASP potentielles pour le projet</h2>';
                $erreurSousTitre = '<h3> class="h5 opens-sans"><span aria-hidden="true">🔠</span>Menace : </span>'. ($hotspotOwasp['type'] ?? 'Information manquante') .'<h3>';

                $erreurContain = $this->generateHtmlErrorContain(
                        $hotspotOwasp['code'],
                        $hotspotOwasp['message'] ?? self::$noMessage,
                        $hotspotOwasp['erreur'] ?? self::$noError );

                $collecte[] = $erreurTitre . $erreurSousTitre. $erreurContain;
                $errorEncountered = true;
            }

            if ($errorEncountered) {
                return [
                    'code' => 500,
                    'compte_rendu' => implode("\n", $collecte)
                ];
            }
        }

        /** Signalement NoSonar et suppressWarning pour les projets Java */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des annotations noSonar et suppressWarning pour le projet.');
        $collecte[] =
        '<h2 class="h5"><span aria-hidden="true">🔜</span>11 - Collecte des annotations noSonar et suppressWarning pour le projet</h2>';

        $noSonar = $this->batchCollecteNoSonar->batchCollecteNoSonar(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($noSonar['code'] === 200){
            $item = $noSonar['historique'];
            $collecte[] = self::$htmlSectionOpen . $noSonar['message'] .'</p>
                <ul>
                    <li><span>Nombre de noSonar (Java) : </span>' . $item["java_no_sonar"] . '</li>
                    <li><span>Nombre de noSonar (Python) : </span>' . $item["python_no_sonar"] . '</li>
                    <li><span>Nombre de noSonar (PHP) : </span>' . $item["php_no_sonar"] . '</li>
                    <li><span>Nombre de noSonar (Checkstyle) : </span>' . $item["check_style"] . '</li>
                    <li><span>Nombre de noSonar (PMD) : </span>' . $item["no_pmd"] . '</li>
                    <li><span>Nombre de suppressWarning : </span>' . $item["suppress_warning"] . '</li>
                    <li><span>Nombre de noSonar (Total) : </span>' . $item["total_no_sonar"] . '</li>
                </ul>
            </div>' . self::$htmlSectionClose;

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $noSonar['historique']);
        } else {
                $this->logger->error('[Batch] ❌ Erreur lors du traitement des annotations noSonar/suppressWarning du projet.', [
                        'code' => $noSonar['code'],
                        'message' => $noSonar['message'] ?? self::$noMessage,
                        'erreur' => $noSonar['erreur'] ?? self::$noError,
                ]);

            $erreurTitre = '<h2 class="h5"><span aria-hidden="true">🔚</span>11 Fin de la collecte des annotations noSonar et suppressWarning pour le projet</h2>';

            $erreurContain = $this->generateHtmlErrorContain(
                $noSonar['code'],
                $noSonar['message'] ?? self::$noMessage,
                $noSonar['erreur'] ?? self::$noError );

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
        '<h2 class="h5"><span aria-hidden="true">🔜</span>12 - Collecte des todo pour le projet</h2>';

        $todo = $this->batchCollecteTodo->batchCollecteTodo(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($todo['code'] === 200){
            $item = $todo['historique'];
            $collecte[] =
            '<p>'. $todo['message'] .'</p>
            <div>
                <ul>
                    <li><span>Nombre de todo (Java) : </span>' . $item['java_todo'] . '</li>
                    <li><span>Nombre de todo (Python) : </span>' . $item['python_todo'] . '</li>
                    <li><span>Nombre de todo (PHP) : </span>' . $item['php_todo'] . '</li>
                    <li><span>Nombre de todo (XML) : </span>' . $item['xml_todo'] . '</li>
                    <li><span>Nombre de todo (Web) : </span>' . $item['web_todo'] . '</li>
                    <li><span>Nombre de todo (JavaScript) : </span>' . $item['javascript_todo'] . '</li>
                    <li><span>Nombre de todo (TypeScript) : </span>' . $item['typescript_todo'] . '</li>
                    <li><span>Nombre de todo (Ruby) : </span>' . $item['ruby_todo'] . '</li>
                    <li><span>Nombre de todo (Total) : </span>' . $item['total_todo'] . '</li>
                </ul>
            </div>';

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $todo['historique']);
        } else {
            $this->logger->error('[Batch] ❌ Erreur lors du traitement des todo du projet.', [
                'code' => $noSonar['code'],
                'message' => $noSonar['message'] ?? self::$noMessage,
                'erreur' => $noSonar['erreur'] ?? self::$noError,
            ]);

            $erreurTitre = '<h2 class="h5"><span aria-hidden="true">🔚</span>12 Fin de la collecte des todo pour le projet</h2>';

            $erreurContain = $this->generateHtmlErrorContain(
                $todo['code'],
                $todo['message'] ?? self::$noMessage,
                $todo['erreur'] ?? self::$noError );

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
        '<h2 class="h5"><span aria-hidden="true">🔜</span>13 - Collecte des informations Actuator pour le projet</h2>';

        $actuatorInfo = $this->batchCollecteActuator->BatchCollecteActuatorInfo($maven_key);

        /* MODIF 2026-07-23 : Actuator est "best effort" — seule une erreur interne
         * Ma-Moulinette (recherche du point d'accès en base, clé 'fatal') stoppe la
         * collecte du projet, comme les autres étapes. Un point d'accès injoignable
         * ou en erreur HTTP ne bloque plus rien : le JSON d'échec est stocké tel quel
         * dans l'historique (pastille rouge côté page Projet) et la collecte continue. */
        if (!empty($actuatorInfo['fatal'])) {
                $this->logger->error('[Batch] ❌ Erreur lors du traitement des informations actuator du projet.', [
                        'code' => $actuatorInfo['code'],
                        'message' => $actuatorInfo['message'] ?? self::$noMessage,
                        'erreur' => $actuatorInfo['erreur'] ?? self::$noError,
                ]);

                $erreurTitre = '<h2 class="h5"><span aria-hidden="true">🔚</span>13 Fin de la collecte des informations actuator pour le projet</h2>>';

                $erreurContain = $this->generateHtmlErrorContain(
                    $actuatorInfo['code'],
                    $actuatorInfo['message'] ?? self::$noMessage,
                    $actuatorInfo['erreur'] ?? self::$noError );

                $collecte[] = $erreurTitre . $erreurContain;
                $compte_rendu = implode("\n", $collecte);

                return [
                    'code' => $actuatorInfo['code'],
                    'compte_rendu' => $compte_rendu];
        }

        $collecte[] = '<p>'. ($actuatorInfo['message'] ?? self::$noMessage) .'</p>';

        /** Information a joindre dans l'historique ([] si pas de point d'accès déclaré) */
        $json = $actuatorInfo['json'];

        /** Logger dans le projet (info, warn, error, debug) */
        $this->logger->info('[Batch] ℹ️ Démarrage de la collecte des Logger Java pour le projet.');
        $collecte[] =
        '<h2 class="h5"><span aria-hidden="true">🔜</span>14 - Collecte des Logger Java pour le projet</h2>';

        $loggerJava = $this->batchCollecteLogger->BatchCollecteLogger(
            $maven_key,
            $mode_collecte,
            $utilisateur_collecte);

        if ($loggerJava['code'] === 200 || $loggerJava['code'] === 404){
            $item = $loggerJava['historique'];

            $collecte[] = self::$htmlSectionOpen . $loggerJava['message'] .'</p>
                <ul>
                    <li><span>Nombre de Logger INFO : </span>' . $item['logger_info'] . '</li>
                    <li><span>Nombre de Logger WARN : </span>' . $item['logger_warn'] . '</li>
                    <li><span>Nombre de Logger ERROR : </span>' . $item['logger_error'] . '</li>
                    <li><span>Nombre de Logger DEBUG : </span>' . $item['logger_debug'] . self::$htmlSectionClose;

            /** On met à jour le tableau des données pour la table historique */
            $mergeHistorique = array_merge($mergeHistorique, $loggerJava['historique']);
        } else {
                $this->logger->error('[Batch] ❌ Erreur lors du traitement des Logger Java pour le projet.', [
                            'code' => $loggerJava['code'],
                            'message' => $loggerJava['message'] ?? self::$noMessage,
                            'erreur' => $loggerJava['erreur'] ?? self::$noError,
                            'tracker' => $loggerJava['tracker'] ?? self::$noError,
                    ]);

                $erreurTitre = '<h2 class="h5"><span aria-hidden="true">🔚</span>Fin de la collecte des informations du projet</h2>';
                $erreurSousTitre = '<h3> class="h5 opens-sans"><span aria-hidden="true">🔠</span>Level : </span>'. ($loggerJava['tracker'] ?? self::$noError) .'<h3>';

                $erreurContain = $this->generateHtmlErrorContain(
                    $informationProjet['code'],
                    $informationProjet['message'] ?? self::$noMessage,
                    $informationProjet['erreur'] ?? self::$noError );

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
        '<h2 class="h5"><span aria-hidden="true">📜</span>15 - Consolidation des données dans la table historique</h2>';

        $date_historique = new \DateTimeImmutable('now', new \DateTimeZone(self::$europeParis));

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

        if ($historique['code'] == 200){
            $collecte[] =
                '<div class="callout alert-callout-border primary">Données collectées et sauvegardé avec succès.</div>';
            } else {
            $this->logger->critical('[Batch] 🔴 Erreur lors du traitement des données pour la table historique.', [
                'code' => $loggerJava['code'],
                'message' => $loggerJava['message'] ?? self::$noMessage,
                'erreur' => $loggerJava['erreur'] ?? self::$noError,
            ]);

            $erreurTitre = '<h2 class="h5"><span aria-hidden="true">🔴</span>Échec de mise à jour de la table Historique</h2>';

            $erreurContain = $this->generateHtmlErrorContain(
                $historique['code'],
                $historique['message'] ?? self::$noMessage,
                $historique['erreur'] ?? self::$noError );

            $collecte[] = $erreurTitre . $erreurContain;
            $compte_rendu = implode("\n", $collecte);

            unset($mergeHistorique);
            unset($collecte);
            return [
                'code'=> 500,
                'compte_rendu' => $compte_rendu
            ];
        }

        /** Fin du traitement */
        $finTraitement = new \DateTime('now', new \DateTimeZone(self::$europeParis));
        $interval = $debutTraitement->diff($finTraitement);

        $collecte[] =
        '<h2 class="h5"><span aria-hidden="true">🎉</span>Collecte terminée pour ce projet</h2>
        <div class="callout alert-callout-border secondary">
            <ul>
                <li><span>Portefeuille : </span>' . $portefeuille . '</li>
                <li><span>Projet : </span>' . $maven_key . '</li>
                <li><span>Mode : </span>' . $mode_collecte .'</li>
                <li><span>Démarrage : </span>' . $debutTraitement->format(self::$dateFormat) .'</li>
                <li><span>Fin : </span>' . $finTraitement->format(self::$dateFormat) . '</li>
                <li><span>Temps d\'exécution : </span>' . $interval->format('%H:%i:%s.%f') . '</li>
            </ul>
        </div>';

        /** Rapport de collecte */
        $this->logger->info('[Batch] ℹ️ Sauvegarde du conte-rendu pour le projet.');
        $compte_rendu = implode("\n", $collecte);

        unset($collecte);
        gc_collect_cycles();

        $is_historique = (strtolower($mode_collecte) === 'collecte') ? $mergeHistorique : '';
        $is_compte_rendu = (strtolower($mode_collecte) === 'collecte') ? '' : $compte_rendu;

        return [
            'code' => 200,
            'message' => 'La collecte et la mise à jour de la table historique pour ce projet est terminée.',
            'historique' => $is_historique,
            'compte_rendu' => $is_compte_rendu
        ];
    }
}
