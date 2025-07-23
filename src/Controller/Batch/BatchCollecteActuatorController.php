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

use App\Service\Client;
use App\Entity\Actuator;
use App\Entity\ActuatorInfo;
use App\Service\UrlBuilderService;

/**
 * [Description BatchCollecteActuatorController]
 */
class BatchCollecteActuatorController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function __construct(
        private EntityManagerInterface $em,
        private Client $client,
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for BatchCollecteActuatorInfo]
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 25/06/2024 14:50:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteActuatorInfo(string $maven_key): array
    {

        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $actuatorRepos = $this->em->getRepository(Actuator::class);

        $this->logger->info("ℹ️ [Actuator] Lancement de la collecte pour {$maven_key}");

        /** On regarde si, il y a une point d'accès défini pour le projet */
        $map = [ 'maven_key' => $maven_key ];
        $actuatorEndpoint = $actuatorRepos->findActuatorMavenKey($map);

        if (isset($actuatorEndpoint['code']) && in_array($actuatorEndpoint['code'], [23502, 23505, 500, 503])) {
            $this->logger->error("❌ [Actuator] Erreur de récupération du endpoint pour {$maven_key}", ['erreur' => $actuatorEndpoint['erreur']]);
            return [
                    'code' => $actuatorEndpoint['code'],
                    'erreur' => $actuatorEndpoint['erreur']
                ];
        }

        /** Il n'y a pas de endpoint pour ce projet */
        if ($actuatorEndpoint['code'] === 404){
            $this->logger->warning("⚠️ [Actuator] Aucun endpoint défini pour le projet {$maven_key}");
            return [
                    'code' => 404,
                    'type' => 'warning',
                    'message' => "Il n'y a pas de point-d'accès défini pour ce projet (Erreur 404).",
                    'trace' => null
                ];
        }

        /** On construit l'URL */
        $actuatorUser = $actuatorEndpoint['user'];
        $actuatorPassword = $actuatorEndpoint['password'];
        $baseUrl = $actuatorEndpoint['url'];

        $url = $this->urlBuilder->build(
            $baseUrl,
            'actuator/info',
            [ 'project' => $maven_key ]
        );

        $this->logger->info("ℹ️ [Actuator] Appel de l’API actuator/info", ['url' => $url]);

         /** Appelle le clientActuator HTTP */
        try {
                $actuatorInfo = $this->client->httpActuator($url, $actuatorUser, $actuatorPassword);
        } catch (\Throwable $e) {
            $this->logger->critical("❌ [Actuator] Exception lors de l'appel HTTP", ['exception' => $e->getMessage()]);
            return [
                'code' => 500,
                'type' => 'alert',
                'erreur' => ['Exception lors de l’appel HTTP : ' . $e->getMessage()]
            ];
        }

        $data = $actuatorInfo['json'];

        /** On catch les erreurs HTTP  */
        if (isset($data['code']) && in_array($data['code'], [400, 401, 403, 404, 500, 503, 504])) {
                $this->logger->error("❌ [Actuator] Erreur HTTP détectée", ['code' => $data['code'], 'details' => $data['erreur'] ?? 'non précisé']);
                return [
                        'code' => $data['code'],
                        'erreur' => [$data['erreur']]
                    ];
        }

        $this->logger->info("ℹ️ [Actuator] Données reçues avec succès pour {$maven_key}");

        /** On renvoi les résultats */
        return [
            'code' => 200,
            'message' => $data
        ];
    }

}
