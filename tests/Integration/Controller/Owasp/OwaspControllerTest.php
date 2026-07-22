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

namespace App\Tests\Integration\Controller\Owasp;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * MODIF 2026-07-18 : couvre le rendu Twig réel de /owasp (pas de mock du
 * moteur de template comme dans OwaspControllerTest unitaire) sur les
 * chemins de sortie anticipée. Ces chemins rendaient historiquement la page
 * avec un tableau `$render` incomplet (`sonar_version`/`application`/
 * `application_version`/`has_dc_scan` jamais affectés) — Twig en mode strict
 * plantait avec "Variable ... does not exist" au lieu d'afficher le message
 * flash attendu. Bug découvert en usage réel, corrigé dans
 * OwaspController::index() (valeurs par défaut posées avant tout retour).
 */
class OwaspControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    private const MEL = 'emma.durand@ma-moulinette.fr';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->em->getRepository(Utilisateur::class)
            ->findOneBy(['courriel' => self::MEL]);
        $this->assertNotNull($user, 'Fixtures Utilisateur doivent être chargées (emma.durand@ma-moulinette.fr).');

        $user->setRoles(['ROLE_UTILISATEUR']);
        $this->em->flush();

        $this->client->loginUser($user);
    }

    private function buildToken(string $mavenKey, int $salt = 12345): string
    {
        return str_rot13(base64_encode("{$salt}|{$mavenKey}"));
    }

    public function testIndexRendersRealTemplateWithoutCrashingWhenTokenIsMissing(): void
    {
        // MODIF 2026-07-22 : token absent = navigation sans contexte, plus une
        // erreur 400 — message info invitant à passer par la page Projet.
        $this->client->request('GET', '/owasp');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Sélectionnez un projet', $this->client->getResponse()->getContent());
    }

    public function testIndexRendersRealTemplateWithoutCrashingWhenNoGroupeFonctionnel(): void
    {
        $user = $this->em->getRepository(Utilisateur::class)
            ->findOneBy(['courriel' => self::MEL]);
        $user->setListeGroupeFonctionnel([]);
        $this->em->flush();

        $token = $this->buildToken('fr.ma-moulinette:projet-inconnu');
        $this->client->request('GET', '/owasp?token=' . $token);

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('rattaché à un groupe fonctionnel', $this->client->getResponse()->getContent());
    }

    public function testIndexRendersRealTemplateWithoutCrashingWhenProjectNotInGroupe(): void
    {
        $user = $this->em->getRepository(Utilisateur::class)
            ->findOneBy(['courriel' => self::MEL]);
        $user->setListeGroupeFonctionnel(['fr-test']);
        $this->em->flush();

        $token = $this->buildToken('fr.hors-perimetre:projet-externe');
        $this->client->request('GET', '/owasp?token=' . $token);

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }
}
