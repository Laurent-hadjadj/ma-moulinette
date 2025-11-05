<?php

/**
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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Uid\Ulid;
use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityManagerInterface;

use App\Controller\Batch\CollecteController;
use App\Entity\Portefeuille;
use App\Entity\BatchTraitement;
use App\Entity\BatchExecution;
use App\Entity\BatchExecutionJournal;
use App\Entity\BatchProfiling;

/**
 * [Description BatchController]
 */
class BatchAutoController extends AbstractController
{
    private static $erreur400 = "❌ La requête est incorrecte (Erreur 400).";
    private static $erreur403 = "🚫 Vous n'êtes pas autorisé à acceder à ce service. (Erreur 403).";
    private static $noMessage = 'Aucun message remonté.';
    private static $noError = 'Aucune erreur remontée.';
    private static $europeParis = 'Europe/Paris';
    private static $dateFormat = "Y-m-d H:i:s";

    public function __construct(
        private CollecteController $collecte,
        private ParameterBagInterface $params,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        #[Autowire(service: 'monolog.logger.profiling')]
        private LoggerInterface $profilerLogger,
    ) {
    }



    /**
     * [Description for traitement]
     * On lance les traitements automatiques
     *
     * @return JsonResponse
     *
     * Created at: 04/12/2022, 17:42:22 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function traitement(): JsonResponse
    {
        /** On récupère la liste des traitements planifiés */
        $sql = "SELECT id, demarrage, titre, portefeuille, nombre_projet as projet
                FROM batch_traitement
                WHERE demarrage = 'Auto'
                ORDER BY nombre_projet ASC";
        $r = $this->em->fetchAllAssociative($sql);

        dd($r);

        /** On log si il n'y a pas de job à lancer */
        if (empty($r)) {
            $this->logger->INFO("[BATCH-007] Pas de traitezments programmé aujoud'hui !");
            return new JsonResponse([
                "message" => "[BATCH-007]",
                "description" => "Pas de traitements programmé aujoud'hui !"
              ], Response::HTTP_OK);
        }

        /**
         * On a trouvé un job :
         * { "93" "Auto" "0" "ANALYSE MA-MOULINETTE"
         *   "APPLICATIONS DE GESTION SONAR" "1"
         *   "admin" "@ma-moulinette" "2023-01-12 10:04:05" }
         *
         * On traite la liste jobs en Auto */
        foreach ($r as $value) {
            /** On récupère l'id du job */
            $id = $value['id'];
            /**
             * On récupère la liste des jobs
             * liste" => array:1 [ 0 => "fr.ma-petite-entreprise:ma-moulinette" ]
             */
            $listeProjet = $this->listeProjet($value['portefeuille']);

            /** On continue le traitement si la liste n'est pas vide */
            $message = explode(" ", $listeProjet['message']);
            if ($message[0] === "[BATCH-005]") {

                /** On démarre la mesure du batch */
                $debutBatch = new \DateTime();
                $debutBatch->setTimezone(new \DateTimeZone(static::$europeParis));
                $tempoDebutBatch = $debutBatch->format(static::$dateFormat);

                /** Pour chaque projet de la liste */
                foreach($listeProjet['liste'] as $mavenKey) {

                /**
                 * On met à jour la table des traitements
                 * { "98" "Auto" "1" "ANALYSE MA-MOULINETTE" "APPLICATIONS DE GESTION SONAR"
                 *   "1" "admin" "@ma-moulinette"
                 *   "2023-01-12 10:37:13" "2023-01-12 10:37:13" "2023-01-12 10:37:13" }
                 */
                $sql = "UPDATE batch_traitement
                  SET debut_traitement='$tempoDebutBatch',
                      fin_traitement='$tempoFinBatch',
                      resultat = 1
                  WHERE id=$id;";
                $this->em->getConnection()->prepare($sql)->executeQuery();
            }

        }


        return new JsonResponse([
          "message" => "Tout va bien"
        ],  Response::HTTP_OK);
    }
  }

    /**
     * [Description for traitementAuto]
     * Lance le traitement automatique programmé
     *
     * @param Client $client
     * @param Request $request
     *
     * @return [type]
     *
     * Created at: 10/04/2024 07:48:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/public/traitement/automatique', name: 'traitement_automatique', methods: ['PUT'])]
    public function traitementAuto(Request $request): JsonResponse
    {
        /** On récupère les données du POST */
        $data = json_decode($request->getContent());

        if ($data === null ||
                !property_exists($data, 'token') ||
                !property_exists($data, 'titre_portefeuille') ||
                !property_exists($data, 'portefeuille')){
                $this->logger->error("[Traitement-Auto] ❌ Requête invalide : clé 'token', 'titre_portefeuille' ou 'portefeuille' manquante ou JSON mal formé.",[
                    'payload' => $data
                ]);

                return new JsonResponse([
                    'code' => 400,
                    'type' => 'error',
                    'message' => static::$erreur400
                ], Response::HTTP_OK);
        }

      // Vérification du token
      $token = $this->params->get('api.client_token');
      if ($data->token !== $token){
        $this->logger->error("[Traitement-Auto] 🚫 Vous n'êtes pas autorisé à acceder à ce service.", ['token' => $data->token]);

        return new JsonResponse([
            'code' => 403,
            'type' => 'error',
            'message' => static::$erreur403
        ], Response::HTTP_FORBIDDEN);
      }

      return new JsonResponse([
        'code' => 200,
        'message' => '$message'
      ], Response::HTTP_OK);
  }
}
