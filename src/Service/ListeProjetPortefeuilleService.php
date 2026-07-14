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

namespace App\Service;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Portefeuille;

/**
 * [Description ListeProjetPortefeuilleService]
 */
class ListeProjetPortefeuilleService
{
    private static string $noMessage = 'Aucun message remonté.';
    private static string $noError = 'Aucune erreur remontée.';

    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * [Description for listeProjet]
     * Récupère la liste des projets depuis un portefeuille de projets.
     *
     * @param string $portefeuille
     * @param string $groupe_fonctionnel
     *
     * @return array<int|string, mixed>
     *
     * Created at: 09/12/2022, 12:05:30 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listeProjet(string $portefeuille, string $groupe_fonctionnel): array
    {
        /*** On instancie l'entityRepository */
        $portefeuilleRepos = $this->em->getRepository(Portefeuille::class);

        /** On envoi le titre du portefeuille et le nom du portefeuille */
        $map = [
            'portefeuille' => $portefeuille,
            'groupe_fonctionnel' => $groupe_fonctionnel
        ];

        /** On récupère le portefeuille de projets */
        $liste_projets = $portefeuilleRepos->selectPortefeuille($map);

        if ($liste_projets['code'] !== 200) {
            $this->logger->error('[ListeProjetPortefeuilleService] ❌ Échec de la requête selectPortefeuille', [
                'code' => $liste_projets['code'],
                'message' => $liste_projets['message'] ?? self::$noMessage,
                'erreur' => $liste_projets['erreur'] ?? self::$noError,
                'portefeuille' => $portefeuille,
                'groupe_fonctionnel' => $groupe_fonctionnel
                ]);

            return [
                'code' => $liste_projets['code'],
                'type' => 'error',
                'message' => "Le portefeuille de projet n'est pas accessible (Erreur {$liste_projets['code']}).",
                'erreur' =>  $liste_projets['erreur'] ?? null
            ];
        }

        if (empty($liste_projets['liste'])) {
            $this->logger->warning('[ListeProjetPortefeuilleService] ⚠️ La liste des traitements ne contient pas votre portefeuille !', [
                'code' => $liste_projets['code'],
                'message' => $liste_projets['message'] ?? self::$noMessage,
                'erreur' => $liste_projets['erreur'] ?? self::$noError,
                'portefeuille' => $portefeuille,
                'groupe_fonctionnel' => $groupe_fonctionnel
                ]);

            return [
                'code' => 404,
                'type' => 'warning',
                'message' => "Votre portefeuille ne contient pas ce projet (Erreur 404).",
                'erreur' => 404
            ];
        }

        $liste = json_decode($liste_projets['liste'][0]['liste'], true) ?? [];

        // On renvoie une liste de maven_key
        return [
            'code' => 200,
            'liste' => $liste
        ];
    }
}
