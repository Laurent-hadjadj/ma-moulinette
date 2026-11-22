<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  https://www.gnu.org/licenses/agpl-3.0.html
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
 *
 * MODIF 2026-11-17 : BUG CORRIGÉ — exigeait ROLE_GESTIONNAIRE alors que la
 * page appelante (PortefeuilleCrudController) n'exige que ROLE_BATCH ; ces
 * 2 rôles sont des branches indépendantes de la hiérarchie (security.yaml),
 * donc un utilisateur ROLE_BATCH pouvait voir la page « Créer un
 * portefeuille » mais se voyait refuser silencieusement le filtrage par
 * groupe fonctionnel (401/403 JSON). Aligné sur le rôle de la page.
 */
#[IsGranted('ROLE_BATCH')]
class PortefeuilleAjaxController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private LoggerInterface $logger )
    {
    }

    /**
     * Retourne la liste des projets filtrée par tag (nom groupe = tag SonarQube).
     * Si le tag ne correspond à aucun projet (tag jamais posé, ou retiré côté
     * SonarQube), renvoie une liste vide : ce n'est pas une erreur, le groupe
     * fonctionnel n'a simplement aucun projet pour l'instant.
     *
     * MODIF 2026-06-03 : route extraite de PortefeuilleCrudController
     * pour éviter l'interception EasyAdmin (entityId = 'list-projets' → exception SQL integer).
     *
     * MODIF 2026-11-19 : BUG CORRIGÉ — repli sur le périmètre complet quand
     * aucun projet ne porte le tag (ex. tags retirés côté SonarQube) ; un
     * groupe fonctionnel sans projet doit afficher une liste vide, pas tout
     * le périmètre.
     */
    #[Route('/api/secure/admin/portefeuille/list-projets', name: 'admin_ajax_portefeuille_list_projets', methods: ['GET'])]
    public function listProjets(Request $request): JsonResponse
    {
        $this->logger->info("[EasyAdmin] 📥 Requête reçue sur api/secure/admin/portefeuille/list-projets");

        $selectedGroupe = $request->query->get('groupe');
        $params = [];
        $sql = "SELECT name, maven_key FROM ma_moulinette.liste_projet";

        if (!empty($selectedGroupe)) {
            $sql .= " WHERE jsonb_exists(tags::jsonb, :tag)";
            $params['tag'] = mb_strtolower(trim($selectedGroupe));
        }

        $sql .= " ORDER BY name ASC";
        $stmt = $this->em->getConnection()->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }

        return new JsonResponse($stmt->executeQuery()->fetchAllAssociative());
    }
}
