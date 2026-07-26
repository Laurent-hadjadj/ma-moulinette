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

namespace App\Tests\Unit\Controller;

use App\Controller\FooterController;
use App\Service\UserAgent\UserAgentTrackingFacade;
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

    /** @var Environment&MockObject */
    private MockObject $twig;

    /** @var UserAgentTrackingFacade&MockObject */
    private MockObject $tracking;

    private FooterController $controller;

    protected function setUp(): void
    {
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);

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
    public function testEachFooterRouteRendersExpectedTemplate(
        string $method,
        string $template
    ): void {
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

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function routeProvider(): array
    {
        return [
            'plan du site'        => ['planDuSite',       'footer/plan-du-site.html.twig'],
            'mention legal'       => ['mentionLegal',     'footer/mention-legal.html.twig'],
            'donnees personnelles' => ['donneesPersonnelles', 'footer/donnees-personnelles.html.twig'],
        ];
    }

    public function testMentionLegalEnrichesRenderWithCguParameters(): void
    {
        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedCtx = [];
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
        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedCtx = [];
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
