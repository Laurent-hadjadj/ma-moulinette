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

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur AJAX pour les endpoints Portefeuille.
 * N'hérite PAS de AbstractCrudController pour éviter l'interception EasyAdmin.
 * Route sous /admin/ajax/ → protégée par le firewall ROLE_GESTIONNAIRE.
 */
#[IsGranted('ROLE_GESTIONNAIRE')]
class PortefeuilleAjaxController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private LoggerInterface $logger )
    {
    }

    /**
     * Retourne la liste des projets filtrée par tag (nom groupe = tag SonarQube).
     * Si le tag ne correspond à aucun projet, renvoie le périmètre complet.
     *
     * MODIF 2026-06-03 : route extraite de PortefeuilleCrudController
     * pour éviter l'interception EasyAdmin (entityId = 'list-projets' → exception SQL integer).
     */
    #[Route('/api/secure/admin/portefeuille/list-projets', name: 'admin_ajax_portefeuille_list_projets', methods: ['GET'])]
    public function listProjets(Request $request): JsonResponse
    {
        $this->logger->info("[EasyAdmin] 📥 Requête reçue sur api/secure/admin/portefeuille/list-projets");

        $selectedGroupe = $request->query->get('groupe');
        $params = [];
        $sql = "SELECT name, maven_key FROM ma_moulinette.liste_projet";

        if (!empty($selectedGroupe)) {
            $tag = mb_strtolower(trim($selectedGroupe));
            $matchCount = (int) $this->em->getConnection()->fetchOne(
                "SELECT COUNT(*) FROM ma_moulinette.liste_projet WHERE jsonb_exists(tags::jsonb, :tag)",
                ['tag' => $tag]
            );
            if ($matchCount > 0) {
                $sql .= " WHERE jsonb_exists(tags::jsonb, :tag)";
                $params['tag'] = $tag;
            }
        }

        $sql .= " ORDER BY name ASC";
        $stmt = $this->em->getConnection()->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }

        return new JsonResponse($stmt->executeQuery()->fetchAllAssociative());
    }
}
