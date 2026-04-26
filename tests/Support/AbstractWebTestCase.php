<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base class pour les tests fonctionnels (WebTestCase).
 *
 * Fournit des helpers communs :
 *  - création du client HTTP Symfony
 *  - authentification d'un utilisateur par courriel ou par rôle
 *  - récupération de l'EntityManager de test
 *  - chargement de fixtures ciblées
 *
 * Utilisation :
 *   class MaPageWebTest extends AbstractWebTestCase { ... $this->loginAs('admin'); }
 */
abstract class AbstractWebTestCase extends WebTestCase
{
    protected ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->client->disableReboot();
    }

    /**
     * Authentifie un utilisateur via son adresse courriel.
     *
     * @throws \RuntimeException si le compte n'existe pas dans la base de test.
     */
    protected function loginByEmail(string $courriel): Utilisateur
    {
        $user = $this->getUtilisateurRepository()->findOneBy(['courriel' => $courriel]);

        if ($user === null) {
            throw new \RuntimeException(sprintf(
                'Utilisateur de test introuvable (courriel=%s). Charger les fixtures : doctrine:fixtures:load --env=test',
                $courriel
            ));
        }

        $this->client->loginUser($user);

        return $user;
    }

    /**
     * Raccourcis pour les comptes fixtures standard du projet.
     * Les comptes correspondent à App\Tests\DataFixtures\UtilisateurFixtures.
     */
    protected function loginAsAdmin(): Utilisateur
    {
        return $this->loginByEmail('admin@ma-moulinette.fr');
    }

    protected function loginAsGestionnaire(): Utilisateur
    {
        return $this->loginByEmail('gestionnaire@ma-moulinette.fr');
    }

    protected function loginAsUtilisateur(): Utilisateur
    {
        return $this->loginByEmail('utilisateur@ma-moulinette.fr');
    }

    protected function loginAsCollecte(): Utilisateur
    {
        return $this->loginByEmail('collecte@ma-moulinette.fr');
    }

    protected function loginAsBatch(): Utilisateur
    {
        return $this->loginByEmail('batch@ma-moulinette.fr');
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function getUtilisateurRepository(): UtilisateurRepository
    {
        return static::getContainer()->get(UtilisateurRepository::class);
    }
}
