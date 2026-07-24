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
use App\Entity\{Actuator};
use App\Repository\ActuatorInfoRepository;
use App\Service\ClientService;

/**
 * Collecte des informations Actuator (endpoint /actuator/info) d'un projet.
 *
 * "Best effort" : hormis une erreur interne Ma-Moulinette (clé 'fatal' à true,
 * recherche du point d'accès en base), aucun échec ici n'interrompt la collecte
 * globale du projet (CollecteController) — un point d'accès Actuator injoignable
 * ou en erreur produit un JSON d'échec stocké tel quel dans l'historique (pastille
 * rouge côté page Projet), pas un abandon du reste de la collecte.
 */
class BatchCollecteActuatorController extends AbstractController
{
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
        private ActuatorInfoRepository $actuatorInfoRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for BatchCollecteActuatorInfo]
     *
     * @return array<int|string, mixed>
     *
     * Created at: 25/06/2024 14:50:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteActuatorInfo(string $maven_key): array
    {
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $actuatorRepos = $this->em->getRepository(Actuator::class);

        $this->logger->info("[Batch Actuator] ℹ️  Lancement de la collecte pour {$maven_key}");

        /** On regarde si, il y a une point d'accès défini pour le projet */
        $map = [ 'maven_key' => $maven_key ];
        $actuatorEndpoint = $actuatorRepos->findActuatorMavenKey($map);

        if (isset($actuatorEndpoint['code']) && in_array($actuatorEndpoint['code'], [23502, 23505, 500, 503])) {
            $this->logger->error("[Batch Actuator] ❌ Erreur de récupération du endpoint pour {$maven_key}", [
                'erreur' => $actuatorEndpoint['erreur']]);

            return [
                    'code' => $actuatorEndpoint['code'],
                    'erreur' => $actuatorEndpoint['erreur'],
                    'json' => [],
                    'fatal' => true,
                ];
        }

        /** Il n'y a pas de endpoint pour ce projet : pas une erreur, JSON vide (pastille grise) */
        if ($actuatorEndpoint['code'] === 404){
            $this->logger->warning("[Batch Actuator] ⚠️ Aucun endpoint défini pour le projet {$maven_key}");
            return [
                    'code' => 404,
                    'type' => 'warning',
                    'message' => "Il n'y a pas de point-d'accès défini pour ce projet (Erreur 404).",
                    'json' => [],
                ];
        }

        /* MODIF 2026-07-23 : l'URL enregistrée (Actuator::$url) est désormais TOUJOURS
         * complète (auto-complétée avec /actuator/info à l'enregistrement, cf.
         * ActuatorController::completerUrlActuator) — on l'appelle telle quelle.
         * Avant cette évolution, UrlBuilderService::build() ajoutait ici le
         * suffixe "actuator/info" + un paramètre "?project=..." qui n'a jamais eu
         * de sens pour un endpoint Actuator (pas un concept SonarQube) : ça
         * dupliquait le suffixe (".../actuator/info/actuator/info?project=...")
         * et faisait 404 chez le serveur distant (bug réel trouvé en test manuel). */
        $actuatorUser = $actuatorEndpoint['user'];
        $actuatorPassword = $actuatorEndpoint['password'];
        $url = $actuatorEndpoint['url'];
        $actuatorId = $actuatorEndpoint['id'];

        $this->logger->debug("[Batch Actuator] 🛠️ Appel du point d'accès Actuator", ['url' => $url]);
        /** Appelle le clientActuator HTTP */
        try {
                $actuatorInfo = $this->client->httpActuator($url, $actuatorUser, $actuatorPassword);
        } catch (\Throwable $e) {
            $message = 'Exception lors de l’appel HTTP : ' . $e->getMessage();
            $this->logger->critical("[Batch Actuator] 🔴 Exception lors de l'appel HTTP", [
                'exception' => $e->getMessage()]);

            return [
                'code' => 500,
                'type' => 'error',
                'erreur' => [$message],
                'json' => $this->buildFailureJson(500, $message),
            ];
        }

        /* MODIF 2026-07-23 : ClientService::httpActuator() catch déjà en interne les
         * erreurs HTTP (timeout, transport, 4xx/5xx) et renvoie alors {code, erreur}
         * SANS clé 'json' — l'ancien test `isset($dataJson['code']) && in_array(...)`
         * qui suivait ce bloc était du code mort (accès à une clé 'json' absente,
         * "Undefined array key" en pratique, crash réel trouvé en test manuel) :
         * il supposait que les erreurs HTTP remontaient dans le corps JSON décodé,
         * alors qu'elles interrompent l'exécution avant. */
        if (!isset($actuatorInfo['json'])) {
                $code = (int) ($actuatorInfo['code'] ?? 500);
                $erreurBrute = $actuatorInfo['erreur'] ?? 'Erreur HTTP non précisée.';
                $message = is_array($erreurBrute) ? implode(', ', $erreurBrute) : (string) $erreurBrute;

                $this->logger->error("[Batch Actuator] ❌ Erreur HTTP détectée", [
                    'code' => $code,
                    'details' => $message
                ]);

                return [
                        'code' => $code,
                        'erreur' => $erreurBrute,
                        'json' => $this->buildFailureJson($code, $message),
                    ];
        }

        $dataJson = $actuatorInfo['json'];

        $this->logger->info("[Batch Actuator] ℹ️ Données reçues avec succès pour {$maven_key}");

        /** Extraction des clés déclarées par l'utilisateur (nœuds JSON, ex. app.version) */
        $cles = $this->actuatorInfoRepository->findActuatorInfoById(['actuator_id' => $actuatorId]);
        $valeurs = [];
        if (($cles['code'] ?? null) === 200) {
            foreach ($cles['liste'] as $ligne) {
                $chemin = $ligne['cle'] ?? null;
                if ($chemin === null || $chemin === '') {
                    continue;
                }
                $valeurs[$chemin] = $this->extraireValeurJson($dataJson, $chemin);
            }
        }

        $message = 'La collecte des informations Actuator pour le projet est terminée.';
        $json = array_merge(
            [
                'date_extraction' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'code' => 200,
                'message' => $message,
            ],
            $valeurs
        );

        /** On renvoi les résultats */
        return [
            'code' => 200,
            'message' => $message,
            'dataJson' => $dataJson,
            'json' => $json,
        ];
    }

    /**
     * [Description for buildFailureJson]
     * JSON minimal stocké dans l'historique quand la collecte échoue
     * (timeout, erreur HTTP, exception) — pas de clés extraites, juste
     * de quoi afficher la pastille rouge et le message côté page Projet.
     *
     * @param int $code
     * @param string $message
     *
     * @return array<string, mixed>
     *
     * Created at: 24/07/2026 17:42:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function buildFailureJson(int $code, string $message): array
    {
        return [
            'date_extraction' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'code' => $code,
            'message' => $message,
        ];
    }

    /**
     * [Description for extraireValeurJson]
     * Extrait la valeur d'un nœud JSON désigné par un chemin à points
     * (ex. "app.version" -> $data['app']['version']). Renvoie null si le
     * chemin n'existe pas dans la réponse distante (clé absente, pas une erreur).
     *
     * @param array<int|string, mixed> $data
     *
     * @return mixed
     *
     * Created at: 24/07/2026 17:43:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function extraireValeurJson(array $data, string $chemin): mixed
    {
        $valeur = $data;
        foreach (explode('.', $chemin) as $segment) {
            if (!is_array($valeur) || !array_key_exists($segment, $valeur)) {
                return null;
            }
            $valeur = $valeur[$segment];
        }

        return $valeur;
    }
}
