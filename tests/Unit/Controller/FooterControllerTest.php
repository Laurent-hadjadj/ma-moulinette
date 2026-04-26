<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\FooterController;
use App\Service\UserAgentTrackingFacade;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class FooterControllerTest extends TestCase
{
    /** @var ParameterBagInterface&MockObject */
    private MockObject $params;

    /** @var UserAgentTrackingFacade&MockObject */
    private MockObject $tracking;

    /** @var Environment&MockObject */
    private MockObject $twig;

    private FooterController $controller;

    protected function setUp(): void
    {
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->twig = $this->createMock(Environment::class);

        // Params injectés dans le constructeur + getParameter()
        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'PROD'],
            ['version', '2.1.0'],
            ['cgu.editeur', 'Editeur SAS'],
            ['cgu.siret', '123'],
            ['cgu.siren', '456'],
            ['cgu.numero.siret', '001'],
            ['cgu.numero.siren', '002'],
            ['cgu.url.site', 'https://ma-moulinette.example.com'],
        ]);

        // AbstractController::render() et getParameter() passent par le container
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['twig', true],
            ['parameter_bag', true],
        ]);
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['parameter_bag', 1, $this->params],
        ]);

        $this->controller = new FooterController($this->params, $this->tracking);
        $this->controller->setContainer($container);
    }

    #[DataProvider('routeProvider')]
    public function testEachFooterRouteTracksAndRendersExpectedTemplate(
        string $method,
        string $eventName,
        string $template
    ): void {
        $this->tracking->expects($this->once())
            ->method('track')
            ->with($eventName);

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                $template,
                $this->callback(function (array $context) {
                    // Vérifie que les paramètres génériques sont injectés
                    return $context['logo_entreprise'] === 'logo.png'
                        && $context['marque_entreprise_short'] === 'MM'
                        && $context['env'] === 'PROD'
                        && $context['version'] === '2.1.0'
                        && $context['date_copyright'] === date('Y');
                })
            )
            ->willReturn('<html>' . $template . '</html>');

        $response = $this->controller->{$method}();

        $this->assertSame('<html>' . $template . '</html>', $response->getContent());
    }

    public static function routeProvider(): array
    {
        return [
            'plan du site'        => ['planDuSite',       'PLAN_DU_SITE',  'footer/plan-du-site.html.twig'],
            'mention legal'       => ['mentionLegal',     'MENTION_LEGALE', 'footer/mention-legal.html.twig'],
            'donnees personnelles' => ['donneesPersonnelles', 'CGU',         'footer/donnees-personnelles.html.twig'],
        ];
    }

    public function testMentionLegalEnrichesRenderWithCguParameters(): void
    {
        $capturedCtx = null;
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'footer/mention-legal.html.twig',
                $this->callback(function (array $context) use (&$capturedCtx) {
                    $capturedCtx = $context;
                    return true;
                })
            )
            ->willReturn('<html>ml</html>');

        $this->controller->mentionLegal();

        $this->assertSame('Editeur SAS', $capturedCtx['editeur']);
        $this->assertSame('123', $capturedCtx['siret']);
        $this->assertSame('456', $capturedCtx['siren']);
        $this->assertSame('001', $capturedCtx['numSiret']);
        $this->assertSame('002', $capturedCtx['numSiren']);
        $this->assertSame('https://ma-moulinette.example.com', $capturedCtx['urlSite']);
    }

    public function testDonneesPersonnellesIncludesUrlSiteInContext(): void
    {
        $capturedCtx = null;
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'footer/donnees-personnelles.html.twig',
                $this->callback(function (array $context) use (&$capturedCtx) {
                    $capturedCtx = $context;
                    return true;
                })
            )
            ->willReturn('<html>dp</html>');

        $this->controller->donneesPersonnelles();

        $this->assertSame('https://ma-moulinette.example.com', $capturedCtx['urlSite']);
    }
}
