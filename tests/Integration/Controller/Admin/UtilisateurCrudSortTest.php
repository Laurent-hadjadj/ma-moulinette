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

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * MODIF 2026-11-07 : BUG CORRIGÉ — un clic sur l'en-tête de colonne « Groupe
 * fonctionnel » du CRUD EasyAdmin Utilisateurs déclenchait une 500
 * (« could not identify an ordering operator for type json ») : ce champ
 * (`listeGroupeFonctionnel`, colonne `json`, pas `jsonb`, sans opérateur
 * d'ordre en PostgreSQL) était resté sortable par défaut sur la page
 * d'index. Corrigé en scindant le champ en 2 (liste non triable / formulaire,
 * UtilisateurCrudController.php). Ce correctif n'avait jusqu'ici aucun vrai
 * test de régression : seul un commentaire de test Unit (comptage de champs)
 * avait été mis à jour, sans jamais exercer le vrai tri HTTP.
 */
class UtilisateurCrudSortTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    private const MEL = 'emma.durand@ma-moulinette.fr';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->em->getRepository(Utilisateur::class)->findOneBy(['courriel' => self::MEL]);
        $this->assertNotNull($user, 'Fixtures Utilisateur doivent être chargées.');
        $user->setRoles(['ROLE_GESTIONNAIRE']);
        $this->em->flush();
        $this->client->loginUser($user);
    }

    public function testSortingByListeGroupeFonctionnelDoesNotCrash(): void
    {
        $this->client->request('GET', '/admin/utilisateur?sort[listeGroupeFonctionnel]=ASC');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Contrôle de sanité : un champ réellement sortable (colonne scalaire)
     * doit continuer de fonctionner — s'assure que le test ci-dessus ne
     * passe pas pour une raison indépendante du tri lui-même.
     */
    public function testSortingByCourrielStillWorks(): void
    {
        $this->client->request('GET', '/admin/utilisateur?sort[courriel]=ASC');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }
}
