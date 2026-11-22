<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Accueil;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * MODIF 2026-11-19 : couverture du nouveau rôle ROLE_REFERENTIAL sur les 2
 * routes ApiAccueilController (accueil_projet_liste, accueil_projet_tags),
 * jusqu'ici non testées pour ce rôle précis (les tests Unit existants
 * instancient le contrôleur directement, sans jamais exercer l'attribut
 * #[IsGranted] évalué par le firewall).
 */
class ApiAccueilAccessControlTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    private const MEL = 'emma.durand@ma-moulinette.fr';

    /** @var array<string, string> */
    private const HEADERS = [
        'HTTP_Origin'           => 'https://localhost',
        'HTTP_X_INTERNAL_FRONT' => 'front-app',
    ];

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

    public function testAccueilProjetListeDeniesRoleUtilisateur(): void
    {
        $this->loginWithRoles(['ROLE_UTILISATEUR']);

        $this->client->request('POST', '/api/secure/accueil/projet', [], [], self::HEADERS);

        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testAccueilProjetListeAllowsRoleReferential(): void
    {
        $this->loginWithRoles(['ROLE_REFERENTIAL']);

        $this->client->request('POST', '/api/secure/accueil/projet', [], [], self::HEADERS);

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAccueilProjetTagsDeniesRoleUtilisateur(): void
    {
        $this->loginWithRoles(['ROLE_UTILISATEUR']);

        $this->client->request('POST', '/api/secure/accueil/tags', [], [], self::HEADERS);

        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testAccueilProjetTagsAllowsRoleReferential(): void
    {
        $this->loginWithRoles(['ROLE_REFERENTIAL']);

        $this->client->request('POST', '/api/secure/accueil/tags', [], [], self::HEADERS);

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }
}
