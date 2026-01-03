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
use App\Entity\Todo;
use App\Service\{ClientService, UrlBuilderService};

/**
 * [Description BatchCollecteTodoController]
 */
class BatchCollecteTodoController extends AbstractController
{
    /** Définition des constantes */
    private static $sonarUrl = "sonar.url";
    private static $europeParis = "Europe/Paris";
    private static $erreurSonarInconnue = 'Erreur SonarQube inconnue.';

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
     * [Description for batchCollecte]
     *
     * @param mixed $maven_key
     * @param mixed $index
     * @param mixed $batchSize
     *
     * @return array
     *
     * Created at: 22/07/2025 18:51:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function batchCollecte($maven_key, $index, $batchSize) :array
    {
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');

       /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/issues/search',
            [
                'projects' => $maven_key,
                'rules' => 'javascript:S1135,xml:S1135,typescript:S1135,Web:S1135,java:S1135,php:s1135,ruby:s1135,python:s1135',
                'p' => $index,
                'ps' => $batchSize
            ]
        );

        $this->logger->debug('[Batch Todo] 🛠️ Appel API SonarQube', ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

        /** On catch les erreurs HTTP :) */
        if (isset($result['code']) && in_array($result['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error('[Batch Todo] ❌ Erreur SonarQube', [
                'url' => $url,
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? static::$erreurSonarInconnue
            ]);
            return [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? static::$erreurSonarInconnue
            ];
        }

        $this->logger->debug('[Batch Todo] 🛠️ Résultat API SonarQube reçu', [
            'total' => $result['json']['paging']['total'] ?? 'inconnu'
        ]);

        /** On renvoie le résultat */
        return $result['json'];
    }

    /**
     * [Description for BatchCollecteTodo]
     *
     * @param string $maven_key
     * @param string $mode_collecte
     * @param string $utilisateur_collecte
     *
     * @return array
     *
     * Created at: 31/05/2024 20:28:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteTodo(string $maven_key, string $mode_collecte, string $utilisateur_collecte): array
    {
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $todoRepository = $this->em->getRepository(Todo::class);
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        $this->logger->info('[Batch Todo] ℹ️ Début de collecte des todo pour le projet', [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** Récupération de la première page */
        $result = $this->batchCollecte($maven_key, 1, 1);
        $batchSize = 500;
        $maxPages = 20;

        // On efface les données du projet :
        $map = [ 'maven_key' => $maven_key ];
        $this->logger->debug('[Batch Todo] 🛠️ Suppression des anciennes entrées TODO');

        $delete = $todoRepository->deleteTodoMavenKey($map);

        if ($delete['code'] !== 200) {
            $this->logger->error('[Batch Todo] ❌ Échec de la requête deleteTodoMavenKe', [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ]);

            return [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ];
        }

        /** Si pas d'issues, on arrête */
        if (empty($result['issues']) || $result['paging']['total'] === 0) {
            $this->logger->info('[Batch Todo] ℹ️ Aucun TODO trouvé pour ce projet.', [
                'maven_key' => $maven_key
            ]);

            return [
                    'code' => 200,
                    'nombre' => 0,
                    ];
        }

        /** Si on a trouvé des to.do dans le code alors on les dénombre */
        $mapData = [];
        for ($i = 1; $i <= $maxPages; $i++) {
            // Appel de l'API pour la page $i
            $result = $this->batchCollecte($maven_key, $i, $batchSize);

            // S'il n'y a plus d'issues, on sort de la boucle
            if (empty($result['issues'])) {
                $this->logger->debug("[Batch Todo] 🛠️ Aucune issue trouvée à la page {$i}, fin de boucle.");
                break;
            }

            foreach ($result['issues'] as $issueData) {
                $component = str_replace('$maven_key :', '', $issueData['component']);
                $line = empty($issueData['line']) ? 0 : $issueData['line'];

                /** On créé la map */
                $mapData[] = [
                    'maven_key' => $maven_key,
                    'rule' => $issueData['rule'],
                    'component' => $component,
                    'line' => $line,
                    'mode_collecte' => $mode_collecte,
                    'utilisateur_collecte' => $utilisateur_collecte,
                    'date_enregistrement' => $date
                ];
            }

            // Insérer les issues récupérées pour cette page
            $batchInsert = $todoRepository->insertTodo($mapData);

            if ($batchInsert['code'] !== 200) {
                $this->logger->error('[Batch Todo] ❌ Échec de la requête insertTodo', [
                    'code' => $batchInsert['code'],
                    'erreur' => $batchInsert['erreur']
                ]);

                return [
                    'code' => $batchInsert['code'],
                    'type' => 'alert',
                    'message' => "Une erreur est survenue lors de la mise à jour de la table ({$batchInsert['code']}).",
                    'trace' => $batchInsert['erreur']
                ];
            }

            $mapData = [];
        }

        $nombre = $result['paging']['total'] ?? 0;

        $this->logger->info('[Batch Todo] ℹ️ Collecte terminée avec succès', [
            'maven_key' => $maven_key,
            'total_todo' => $nombre
        ]);

        /** On enregistre les données */
        return [
            'code' => 200,
            'nombre' => $result['paging']['total'] ?? 0,
            'message' => 'La collecte des todo pour le projet est terminées.',
            'historique' => ['todo' => $nombre]
        ];
    }
}
