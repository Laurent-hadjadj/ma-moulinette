<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * MODIF 2026-07-20 : 5 CrudController EasyAdmin (Utilisateur,
 * GroupeUtilisateur, GroupeFonctionnel, Portefeuille, Batch) n'avaient
 * aucune restriction de rôle côté serveur — seul home.html.twig masquait
 * les cartes correspondantes (is_granted('ROLE_GESTIONNAIRE')/
 * is_granted('ROLE_BATCH')). Corrigé par
 * l'ajout de #[IsGranted(...)] sur chaque contrôleur. Ce test vérifie le
 * 403 pour un rôle insuffisant et le 200 pour le rôle attendu.
 */
class AdminCrudAccessControlTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    private const MEL = 'emma.durand@ma-moulinette.fr';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @param array<int, string> $roles
     */
    private function loginWithRoles(array $roles): Utilisateur
    {
        $user = $this->em->getRepository(Utilisateur::class)->findOneBy(['courriel' => self::MEL]);
        $this->assertNotNull($user, 'Fixtures Utilisateur doivent être chargées (emma.durand@ma-moulinette.fr).');

        $user->setRoles($roles);
        $this->em->flush();

        $this->client->loginUser($user);

        return $user;
    }

    public function testUtilisateurCrudDeniesRoleUtilisateur(): void
    {
        $this->loginWithRoles(['ROLE_UTILISATEUR']);

        $this->client->request('GET', '/admin/utilisateur');

        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testUtilisateurCrudAllowsRoleGestionnaire(): void
    {
        $this->loginWithRoles(['ROLE_GESTIONNAIRE']);

        $this->client->request('GET', '/admin/utilisateur');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testGroupeUtilisateurCrudDeniesRoleUtilisateur(): void
    {
        $this->loginWithRoles(['ROLE_UTILISATEUR']);

        $this->client->request('GET', '/admin/groupe-utilisateur');

        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testGroupeUtilisateurCrudAllowsRoleGestionnaire(): void
    {
        $this->loginWithRoles(['ROLE_GESTIONNAIRE']);

        $this->client->request('GET', '/admin/groupe-utilisateur');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testGroupeFonctionnelCrudDeniesRoleUtilisateur(): void
    {
        $this->loginWithRoles(['ROLE_UTILISATEUR']);

        $this->client->request('GET', '/admin/groupe-fonctionnel');

        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testGroupeFonctionnelCrudAllowsRoleGestionnaire(): void
    {
        $this->loginWithRoles(['ROLE_GESTIONNAIRE']);

        $this->client->request('GET', '/admin/groupe-fonctionnel');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testPortefeuilleCrudDeniesRoleUtilisateur(): void
    {
        $this->loginWithRoles(['ROLE_UTILISATEUR']);

        $this->client->request('GET', '/admin/portefeuille');

        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testPortefeuilleCrudAllowsRoleBatch(): void
    {
        $this->loginWithRoles(['ROLE_BATCH']);

        $this->client->request('GET', '/admin/portefeuille');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testBatchCrudDeniesRoleUtilisateur(): void
    {
        $this->loginWithRoles(['ROLE_UTILISATEUR']);

        $this->client->request('GET', '/admin/batch');

        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testBatchCrudAllowsRoleBatch(): void
    {
        $this->loginWithRoles(['ROLE_BATCH']);

        $this->client->request('GET', '/admin/batch');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }
}
