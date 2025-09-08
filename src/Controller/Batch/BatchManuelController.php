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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Controller\Batch\CollecteController;
use App\Entity\Portefeuille;
use App\Entity\BatchTraitement;

/**
 * [Description BatchController]
 */
class BatchManuelController extends AbstractController
{
    private static $request = "requête : ";
    private static $titreJS = "<strong>[TRAITEMENT]</strong> ";
    private static $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static $erreur404 = "L'appel à l'API n'a pas abouti (Erreur 404).";
    private static $noMessage = 'Aucun message remonté.';
    private static $noError = 'Aucune erreur remontée.';

    public function __construct(
        private CollecteController $collecte,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private Security $security,
    ) {
    }

    /**
     * [Description for lireJournal]
     * Affiche le journal d'execution pour le portefeuille
     *
     * @param string $portefeuille
     * @param string $type
     *
     * @return response
     *
     * Created at: 05/03/2023, 01:50:53 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/traitement/journal/lire', name: 'traitement_journal_lire', methods: ['POST'])]
    public function lireJournal(Request $request): response
    {
        /** On récupère le job et le type (manuel ou automatique) */
        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'portefeuille') || !property_exists($data, 'type')) {
            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$titreJS . static::$erreur400
            ], Response::HTTP_OK);
        }

        //$journal=$this->logger->downloadContent($data->portefeuille, $data->type);
        return new JsonResponse([
            'code' => 200,
            'recherche' => "journal['recherche']",
            'journal' => "journal['content']"
        ], Response::HTTP_OK);
    }

    #[Route('/traitement/journal/efface', name: 'journal_efface', methods: ['DELETE'])]
    public function effaceJournal(Request $request): response
    {
        /** On créé on objet de response HTTP */
        $response = new JsonResponse();

        /** On récupère le job et le type (manuel ou automatique) */
        $data = json_decode($request->getContent());
        if ($data === null ||
                !property_exists($data, 'portefeuille') ||
                !property_exists($data, 'type')) {
            return $response->setData([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$titreJS . static::$erreur400
            ], Response::HTTP_OK);
        }

        //$this->logger->log($data->portefeuille, $data->type, 'delete');

        return new JsonResponse(['code' => 200], Response::HTTP_OK);
    }

    /**
     * [Description for listeProjet]
     * Récupère la liste des projets depuis un portefeuille de projets.
     *
     * @param string $titrePortefeuille
     * @param string $portefeuille
     *
     * @return array
     *
     * Created at: 09/12/2022, 12:05:30 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listeProjet(string $titrePortefeuille, string $portefeuille): array
    {
        /*** On instancie l'entityRepository */
        $portefeuilleRepos = $this->em->getRepository(Portefeuille::class);
        $batchTraitementRepos = $this->em->getRepository(BatchTraitement::class);

        /** On envoi le titre du portefeuille et le nom du portefeuille */
        $map = [
            'titre_portefeuille' => $titrePortefeuille,
            'portefeuille' => $portefeuille
        ];

        /** On vérifie que le portefeuille n'est pas vide */
        $listeProjets = $batchTraitementRepos->selectBatchTraitement($map);

        //debug : dd($portefeuille, $listeProjets, $map);
        if ($listeProjets['code']!=200) {
            $this->logger->error('[Batch Manuel] ❌ Échec de la requête selectBatchTraitement', [
                'code' => $listeProjets['code'],
                'message' => $listeProjets['message'] ?? static::$noMessage,
                'erreur' => $listeProjets['erreur'] ?? static::$noError,
                ]);

            return [
                'code' => $listeProjets['code'],
                static::$request=>'selectBatchTraitement'
            ];
        }

        /** La liste est vide */
        if (!isset($listeProjets['liste']) || count($listeProjets['liste']) === 0)
        {
            $this->logger->warning('[Batch Manuel] ⚠️ La liste des traitements ne contient pas le portefeuille !', [
                'code' => $listeProjets['code'],
                'message' => $listeProjets['message'] ?? static::$noMessage,
                'erreur' => $listeProjets['erreur'] ?? static::$noError,
                'portefeuille' => $titrePortefeuille ?? 'inconnu'
                ]);

            return [
                'code' => 404,
                'type' => 'warning',
                'message' =>  static::$titreJS . 'La liste des traitements ne contient pas le portefeuille !'
            ];
        }

        /** On récupère le portefeuille de projets */
        $result = $portefeuilleRepos->selectPortefeuille($map);

        if ($result['code'] != 200) {
            $this->logger->error('[Batch Manuel] ❌ Échec de la requête selectPortefeuille', [
                'code' => $listeProjets['code'],
                'message' => $listeProjets['message'] ?? static::$noMessage,
                'erreur' => $listeProjets['erreur'] ?? static::$noError,
                'portefeuille' => $titrePortefeuille ?? 'inconnu'
                ]);

            return [
                'code' => $result['code'],
                'type' => 'alert',
                'message' => static::$titreJS . "Le portefeuille de projet n'est pas accessible (Erreur {$result['code']})."

            ];
        }

        if (empty($result['liste'])) {
            $this->logger->warning('[Batch Manuel] ⚠️ La liste des traitements ne contient pas votre portefeuille !', [
                'code' => $listeProjets['code'],
                'message' => $listeProjets['message'] ?? static::$noMessage,
                'erreur' => $listeProjets['erreur'] ?? static::$noError,
                'portefeuille' => $titrePortefeuille ?? 'inconnu'
                ]);

            return [
                'code' => 404,
                'type' => 'warning',
                'message' => static::$titreJS . 'Votre portefeuille ne contient pas ce projet !'
            ];
        }

        $liste = [];
        foreach (json_decode($result['liste'][0]['liste']) as $value) {
            array_push($liste, $value);
        }
        return [
            'code' => 200,
            'liste' => $liste];
    }

    /**
     * [Description for traitementManuel]
     * Lance le traitement des projets en manuel
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 01/03/2023, 09:21:45 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/traitement/manuel', name: 'traitement_manuel', methods: ['POST'])]
    public function traitementManuel(Request $request): Response
    {
        $this->denyAccessUnlessGranted("ROLE_BATCH", null, "L'utilisateur essaye d’accéder à la page sans avoir le rôle ROLE_BATCH");

        /** On récupère les données du POST */
        $data = json_decode($request->getContent());

        if ($data === null ||
                !property_exists($data, 'titre_portefeuille') ||
                !property_exists($data, 'portefeuille'))
            {
                $this->logger->error("[Batch Manuel] ❌ Requête invalide : clé 'titre_portefeuille' ou 'portefeuille' manquante ou JSON mal formé.",[
                    'payload' => $data
                ]);

                return new JsonResponse([
                    'code' => 400,
                    'type' => 'alert',
                    'message' => static::$titreJS . static::$erreur400
                ], Response::HTTP_OK);
            }

        // On extrait la liste des projets pour le portefeuille
        $les_projets = $this->listeProjet($data->titre_portefeuille, $data->portefeuille);
        if ($les_projets['code']===404){
            return new JsonResponse([
                'code' => 404,
                'type' => 'alert',
                'message' => static::$titreJS . static::$erreur404,
                'trace' => $les_projets['message']
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();

        /** On lance la collecte */
        foreach ($les_projets[0] as $le_projet){
            $result = $this->collecte->collecte($data->portefeuille, $le_projet, 'TRAITEMENT MANUEL', $utilisateur_collecte);
            if ($result['code'] === 500){
                $code = $result['code'];
                $type = 'warning';
                $message = static::$titreJS . "La collecte du projet <strong>$le_projet</strong> n'a pas abouti.<br>Consulter le journal d'execution pour avoir plus d'information.";

                return new JsonResponse(compact('code', 'type', 'message'),
                Response::HTTP_OK);
            }
        }
        return new JsonResponse(['code' => 200], Response::HTTP_OK);
    }

}
