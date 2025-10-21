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

use App\Entity\NoSonar;
use App\service\ClientService;
use App\Service\UrlBuilderService;

/**
 * [Description BatchCollecteNoSonarController]
 */
class BatchCollecteNoSonarController extends AbstractController
{
    /** Définition des constantes */
    private static $sonarUrl = "sonar.url";
    private static $europeParis = "Europe/Paris";

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
     * [Description for BatchCollecteNoSonar]
     *
     * @param string $maven_key
     * @param string $mode_collecte
     * @param string $utilisateur_collecte
     *
     * @return array
     *
     * Created at: 21/05/2024 22:25:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteNoSonar(string $maven_key, string $mode_collecte, string $utilisateur_collecte): array
    {
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $noSonarRepository = $this->em->getRepository(NoSonar::class);
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        $this->logger->info('[Batch NoSonar] ℹ️ Début de collecte des annotations noSonar et suppressWarning', [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/issues/search',
            [
                'componentKeys' => $maven_key,
                'rules' => 'java:S1309,java:NoSonar',
                'p' => 1,
                'ps' => 500
            ]
        );

        $this->logger->debug('[Batch Logger] 🛠️ Appel API SonarQube', ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

         /** On catch les erreurs HTTP :) */
        if (isset($result['code']) && in_array($result['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error('[Batch NoSonar] ❌ Erreur SonarQube', [
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
        $map = ['maven_key' => $maven_key];
        $delete = $noSonarRepository->deleteNoSonarMavenKey($map);
        if ($delete['code'] != 200) {
            $this->logger->error('[Batch NoSonar] ❌ Échec de la requête deleteNoSonarMavenKey', [
                'maven_key' => $maven_key,
                'erreur' => $delete['erreur']
            ]);

            return [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ];
        }

        /**
         * Si on a trouvé des @notations de type noSonar ou suppressWarning.
         * dans le code alors on les dénombre
         */
        $noSonar = $suppressWarning = $inconnu = 0;
        $mapData=[];
        if ($result['json']['paging']['total'] !== 0) {
            foreach ($result['json']['issues'] as $issue) {
                switch ($issue['rule']) {
                    case 'java:S1309':
                        $suppressWarning++;
                        break;
                    case 'java:NoSonar':
                        $noSonar++;
                        break;
                    default:
                        $inconnu++;
                        break;
                }
                $component = str_replace('$maven_key :', '', $issue['component']);
                $line = empty($issue['line']) ? 0 : $issue['line'];

                /** On créé la map */
                $mapData[] = [
                    'maven_key' => $maven_key,
                    'rule' => $issue["rule"],
                    'component' => $component,
                    'line' => $line,
                    'mode_collecte' => $mode_collecte,
                    'utilisateur_collecte' => $utilisateur_collecte,
                    'date_enregistrement' => $date
                ];
            }
        } else {
            /** Il n'y a pas de noSOnar ou de suppressWarning */
            $this->logger->info("[Batch NoSonar] ℹ️ Il n'y a pas de noSOnar ou de suppressWarning.");
        }

        $this->logger->debug('[Batch NoSonar] 🛠️  Résultats analysés', [
            'no_sonar' => $noSonar,
            'suppress_warning' => $suppressWarning,
            'inconnu' => $inconnu
        ]);

        /* On enregistre */
        $insert = $noSonarRepository->insertNoSonar($mapData);
        if ($insert['code'] != 200) {
            $this->logger->error('[Batch NoSonar] ❌ Échec de la requête insertNoSonar', [
                'maven_key' => $maven_key,
                'erreur' => $insert['erreur']
            ]);
            return [
                'code' => $insert['code'],
                'erreur' => $insert['erreur']
            ];
        }

        $this->logger->info('[Batch NoSonar] ℹ️ Insertion terminée avec succès', [
            'maven_key' => $maven_key,
            'no_sonar' => $noSonar,
            'suppress_warning' => $suppressWarning,
            'autre' => $inconnu
        ]);

        /** On prépare les données pour l'historique */
        $historique = [
                'suppress_warning' => $suppressWarning,
                'no_sonar' => $noSonar,
            ];

        return [
            'code' => 200,
            'message' => 'La collecte des annotations noSonar et suppressWarning est terminée.',
            'historique' => $historique
        ];
    }
}
