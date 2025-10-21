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

namespace App\Controller\Batch;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Notes;
use App\Entity\Hotspots;
use App\Service\ClientService;
use App\Service\UrlBuilderService;

/**
 * [Description BatchCollecteNoteController]
 */
class BatchCollecteNoteController extends AbstractController
{
    /** Définition des constantes */
    private static $sonarUrl = "sonar.url";

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ClientService $client,
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for batchNote]
     *
     * @param string $maven_key
     * @param string $mode_collecte
     * @param string $utilisateur_collecte
     * @param string $type
     *
     * @return array
     *
     * Created at: 21/05/2024 23:51:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function batchCollecteNote(string $maven_key, string $mode_collecte, string $utilisateur_collecte, string $type): array
    {
        /** On instancie l'EntityRepository */
        $noteRepos = $this->em->getRepository(Notes::class);
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');

        // Validation du type
        if (!in_array($type, ['reliability', 'security', 'sqale'])) {
            $this->logger->error('[Batch Note] ❌ Type de note invalide', ['type' => $type]);
            return [
                'code' => 400,
                'erreur' => "Type de note '$type' invalide."
            ];
        }

        $this->logger->info('[Batch Note] ℹ️ Démarrage de la collecte des notes pour le projet', [
            'maven_key' => $maven_key,
            'type' => $type,
            'mode' => $mode_collecte,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** On construit l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/measures/component',
            [
                'component' => $maven_key,
                'metricKeys' => $type . "_rating",
            ]
        );

        $this->logger->debug('[Batch Note] 🛠️ Appel API SonarQube', ['url' => $url]);
        $result = $this->client->httpSonarQube($url);
        if (isset($result['code']) && in_array($result['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error('[Batch Note] ❌ Erreur SonarQube', [
                'url' => $url,
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? 'Erreur Sonar inconnue.'
            ]);

            return [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? 'Erreur Sonar inconnue.'
            ];
        }

        /** On supprime les résultats pour la maven_key. */
        $map = ['maven_key' => $maven_key, 'type' => $type];
        $delete = $noteRepos->deleteNotesMavenKey($map);

        if ($delete['code'] != 200) {
            $this->logger->error('[Batch Note] ❌ Échec de la requête deleteNotesMavenKey.', [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ]);

            return [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ];
        }

        /** Création de la date du jour */
        $date = new \DateTimeImmutable('now', new \DateTimeZone("Europe/Paris"));

        /** Enregistrement des nouvelles valeurs */
        /** Attention la valeur de la note est en float dans SonarQube, on le converti en integer */
        $latestNote = null;
        foreach ($result['json']['component']['measures'] as $mesure) {
            $latestNote = intval($mesure['value']);
            $map = [
                'maven_key' => $maven_key,
                'type' => $type,
                'value' => $latestNote,
                'mode_collecte' => $mode_collecte,
                'utilisateur_collecte' => $utilisateur_collecte,
                'date_enregistrement' => $date
            ];

            $insert = $noteRepos->insertNotes($map);
            if ($insert['code'] != 200) {
                $this->logger->error('[Batch Note] ❌ Échec de la requête insertNotes.', [
                    'code' => $insert['code'],
                    'erreur' => $insert['erreur']
                ]);

                return [
                    'code' => $insert['code'],
                    'erreur' => $insert['erreur']
                ];
            }
        }

        if ($latestNote === null) {
            $this->logger->warning('[Batch Note] ⚠️ Aucune note trouvée dans les mesures.', [
                'maven_key' => $maven_key,
                'type' => $type
            ]);

            return [
                'code' => 204,
                'erreur' => "Aucune note disponible pour la catégorie {$type}."
            ];
        }

        $noteMap = [ 1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E' ];
        $note = $noteMap[$latestNote] ?? 'Z';

        $this->logger->info('[Batch Note] ℹ️ Collecte des notes réussie réussie', [
            'note_brute' => $latestNote,
            'note_lettre' => $note,
            'type' => $type,
            'maven_key' => $maven_key
        ]);

        return [
            'code' => 200,
            'message' => "Mise à jour de la note pour la catégorie {$type}.",
            'historique' => ["note_{$type}" => $note]
        ];
    }

    /**
     * [Description for BatchCollecteNoteHotspot]
     *
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 03/06/2024 19:15:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteNoteHotspot($maven_key): array
    {
        $hotspotsRepos = $this->em->getRepository(Hotspots::class);
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');

        $this->logger->info('[Batch Hotspot] ℹ️ Début collecte de la note hotspot.', [
            'maven_key' => $maven_key
        ]);

        // Première requête pour obtenir le nombre de hotspots à réviser
        $map = [ 'maven_key' => $maven_key, 'status' => 'TO_REVIEW' ];
        $toReview = $hotspotsRepos->countHotspotsStatus($map);
        if ($toReview['code'] != 200) {
            $this->logger->error('[Batch Hotspot] ❌ Erreur lors du comptage des hotspots TO_REVIEW.', [
                'code' => $toReview['code'],
                'erreur' => $toReview['erreur']
            ]);

            return [
                'code' => $toReview['code'],
                'erreur' => $toReview['erreur']
            ];
        }

        // Deuxième requête pour les hotspots révisés
        $map = [ 'maven_key' => $maven_key, 'status' => 'REVIEWED' ];
        $reviewed = $hotspotsRepos->countHotspotsStatus($map);

        if ($reviewed['code'] != 200) {
            $this->logger->error('[Batch Hotspot] ❌ Erreur lors du comptage des hotspots REVIEWED.', [
                'code' => $reviewed['code'],
                'erreur' => $reviewed['erreur']
            ]);

            return [
                'code' => $reviewed['code'],
                'erreur' => $reviewed['erreur']
            ];
        }

        // Initialisation et calcul de la note
        $note = 'A';
        $ratio = null;

        if (!empty($toReview['to_review']) || !empty($reviewed['reviewed'])) {
        $toReviewCount = intval($toReview['to_review'] ?? 0);
        $reviewedCount = intval($reviewed['reviewed'] ?? 0);
        $total = $toReviewCount + $reviewedCount;

            if ($total > 0) {
                $ratio = ($reviewedCount * 100) / $total;

                if ($ratio >= 80) {
                    $note = 'A';
                } elseif ($ratio >= 70) {
                    $note = 'B';
                } elseif ($ratio >= 50) {
                    $note = 'C';
                } elseif ($ratio >= 30) {
                    $note = 'D';
                } else {
                    $note = 'E';
                }
            } else {
                $this->logger->warning('[Batch Hotspot] ⚠️ Aucun hotspot trouvé.', [ 'maven_key' => $maven_key ]);
            }
        }

        $this->logger->info('[Batch Hotspot] ℹ️ Note calculée avec succès.', [
            'maven_key' => $maven_key,
            'to_review' => $toReview['to_review'] ?? 0,
            'reviewed' => $reviewed['reviewed'] ?? 0,
            'ratio' => $ratio ?? 'non calculé',
            'note' => $note
        ]);

        return [
            'code' => 200,
            'message' => "La collecte de la note pour les menaces potentielles du projet est terminée.",
            'historique' => ['note_hotspot' => $note]
        ];
    }
}
