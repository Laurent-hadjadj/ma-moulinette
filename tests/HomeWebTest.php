<?php

namespace App\Tests;

use App\Repository\Main\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeWebTest extends WebTestCase
{
    private static $userTest='admin@ma-moulinette.fr';
    /**
     * [Description for testLoginFail]
     * On vérifie que si on saisie un identifiant erroné alors on est redirigé vers
     * la page de login.
     * @return void
     *
     * Created at: 26/01/2023, 09:16:39 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testLoginFail(): void
    {
        $client = static::createClient();
        $client->followRedirects(false);
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('valider-formulaire-login')->form();
        $form['courriel'] = 'test';
        $form['password'] = 'test';
        $client->submit($form);
        $this->assertSelectorTextContains('title', 'Redirecting to /login');
    }

    public function testHome(): void
    {
      $client = static::createClient();
      $userRepository = static::getContainer()->get(UtilisateurRepository::class);
      $testUser = $userRepository->findOneByCourriel(static::$userTest);
      $client->loginUser($testUser);

      $client->request('GET', '/');
      $this->assertPageTitleContains('/home');
    }

    public function testProjet(): void
    {
      $client = static::createClient();
      $userRepository = static::getContainer()->get(UtilisateurRepository::class);
      $testUser = $userRepository->findOneByCourriel(static::$userTest);
      $client->loginUser($testUser);

      $client->request('GET', '/projet');
      $this->assertPageTitleContains('mes outils');
    }

    public function testOwasp(): void
    {
      $client = static::createClient();
      $userRepository = static::getContainer()->get(UtilisateurRepository::class);
      $testUser = $userRepository->findOneByCourriel(static::$userTest);
      $client->loginUser($testUser);

      $client->request('GET', '/owasp');
      $this->assertPageTitleContains('Analyse Owasp');
    }

}
