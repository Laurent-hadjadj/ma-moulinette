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

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\DemoModeSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class DemoModeSubscriberTest extends TestCase
{
    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    private RouterInterface $routeur;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->routeur = $this->createStub(RouterInterface::class);
        $this->routeur->method('getContext')->willReturn(new RequestContext());
        $this->routeur->method('generate')->willReturnCallback(
            static fn (string $nom): string => $nom === 'demo_indisponible' ? '/demo/indisponible' : '/inconnu'
        );
    }

    private function evenement(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createStub(HttpKernelInterface::class), $request, $type);
    }

    public function testEcouteLaRequeteAvecUnePrioriteSuperieureAuRoutageEtAuPareFeu(): void
    {
        $this->logger->expects($this->never())->method('warning');
        $abonnements = DemoModeSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $abonnements);
        // Routage = 32, contrôleurs de jetons = 25, pare-feu = 8.
        $this->assertGreaterThan(32, $abonnements[KernelEvents::REQUEST][1]);
    }

    /**
     * Non-régression : hors mode démo (production, développement), le sous-système est totalement inerte.
     */
    public function testSansModeDemoRienNEstBloque(): void
    {
        $this->logger->expects($this->never())->method('warning');
        $evenement = $this->evenement(Request::create('/admin', 'DELETE'));

        (new DemoModeSubscriber(false, $this->logger, $this->routeur))->onKernelRequest($evenement);

        $this->assertNull($evenement->getResponse());
    }

    public function testRequeteAutoriseeEstLaisseePasser(): void
    {
        $this->logger->expects($this->never())->method('warning');
        $evenement = $this->evenement(Request::create('/accueil', 'GET'));

        (new DemoModeSubscriber(true, $this->logger, $this->routeur))->onKernelRequest($evenement);

        $this->assertNull($evenement->getResponse());
    }

    public function testRequeteHorsListeBlancheRecoitUn403Journalise(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                '[Démo] requête refusée (hors liste blanche)',
                ['methode' => 'POST', 'chemin' => '/api/secure/collecte/mesure', 'client_ip' => '203.0.113.7']
            );
        $requete = Request::create('/api/secure/collecte/mesure', 'POST', [], [], [], ['REMOTE_ADDR' => '203.0.113.7']);
        $evenement = $this->evenement($requete);

        (new DemoModeSubscriber(true, $this->logger, $this->routeur))->onKernelRequest($evenement);

        $reponse = $evenement->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $reponse);
        $this->assertSame(Response::HTTP_FORBIDDEN, $reponse->getStatusCode());
        $this->assertSame(
            ['code' => 403, 'type' => 'warning', 'message' => DemoModeSubscriber::MESSAGE],
            json_decode((string) $reponse->getContent(), true)
        );
    }

    public function testPageHorsListeBlancheEstRedirigeeVersLaPageActionIndisponible(): void
    {
        $this->logger->expects($this->once())->method('warning');
        $evenement = $this->evenement(Request::create('/admin', 'GET'));

        (new DemoModeSubscriber(true, $this->logger, $this->routeur))->onKernelRequest($evenement);

        $reponse = $evenement->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $reponse);
        $this->assertSame('/demo/indisponible', $reponse->getTargetUrl());
        $this->assertNotSame(Response::HTTP_FORBIDDEN, $reponse->getStatusCode());
    }

    /**
     * Non-régression : la page de refus doit rester accessible, sinon la redirection bouclerait.
     */
    public function testLaPageActionIndisponibleNEstPasRedirigeeSurElleMeme(): void
    {
        $this->logger->expects($this->never())->method('warning');
        $evenement = $this->evenement(Request::create('/demo/indisponible', 'GET'));

        (new DemoModeSubscriber(true, $this->logger, $this->routeur))->onKernelRequest($evenement);

        $this->assertNull($evenement->getResponse());
    }

    /**
     * Non-régression : l'interface envoie X-Api-Custom-403 et lit `code`, `type` et `message` d'une réponse HTTP 200 ;
     * un 403 ou un corps sans `type` s'affichait « Erreur technique ... [object Object] » (page profil).
     */
    public function testAppelDInterfaceAvecEnTeteCustom403RecoitUn200ExploitableParLInterface(): void
    {
        $this->logger->expects($this->once())->method('warning');
        $requete = Request::create('/api/secure/quality/profiles', 'POST', [], [], [], ['HTTP_X_API_CUSTOM_403' => 'true']);
        $evenement = $this->evenement($requete);

        (new DemoModeSubscriber(true, $this->logger, $this->routeur))->onKernelRequest($evenement);

        $reponse = $evenement->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $reponse);
        $this->assertSame(Response::HTTP_OK, $reponse->getStatusCode());
        $this->assertSame(
            ['x-api-custom-403' => true, 'code' => 403, 'type' => 'warning', 'message' => DemoModeSubscriber::MESSAGE],
            json_decode((string) $reponse->getContent(), true)
        );
    }

    public function testEnTeteCustom403AFauxDonneUn403(): void
    {
        $this->logger->expects($this->once())->method('warning');
        $requete = Request::create('/api/secure/quality/profiles', 'POST', [], [], [], ['HTTP_X_API_CUSTOM_403' => 'false']);
        $evenement = $this->evenement($requete);

        (new DemoModeSubscriber(true, $this->logger, $this->routeur))->onKernelRequest($evenement);

        $this->assertSame(Response::HTTP_FORBIDDEN, $evenement->getResponse()?->getStatusCode());
    }

    public function testRequeteAjaxHorsListeBlancheRecoitUnJson(): void
    {
        $this->logger->expects($this->once())->method('warning');
        $requete = Request::create('/preferences/enregistrer', 'POST', [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $evenement = $this->evenement($requete);

        (new DemoModeSubscriber(true, $this->logger, $this->routeur))->onKernelRequest($evenement);

        $this->assertInstanceOf(JsonResponse::class, $evenement->getResponse());
    }

    public function testRequeteQuiAccepteJsonRecoitUnJson(): void
    {
        $this->logger->expects($this->once())->method('warning');
        $requete = Request::create('/mot-de-passe/mise-a-jour', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $evenement = $this->evenement($requete);

        (new DemoModeSubscriber(true, $this->logger, $this->routeur))->onKernelRequest($evenement);

        $this->assertInstanceOf(JsonResponse::class, $evenement->getResponse());
    }

    public function testSousRequeteNEstPasFiltree(): void
    {
        $this->logger->expects($this->never())->method('warning');
        $evenement = $this->evenement(Request::create('/admin', 'GET'), HttpKernelInterface::SUB_REQUEST);

        (new DemoModeSubscriber(true, $this->logger, $this->routeur))->onKernelRequest($evenement);

        $this->assertNull($evenement->getResponse());
    }
}
