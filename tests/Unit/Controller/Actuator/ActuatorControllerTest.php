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

namespace App\Tests\Unit\Controller\Actuator;

use App\Controller\Actuator\ActuatorController;
use App\Entity\Actuator;
use App\Service\UserAgent\UserAgentTrackingFacade;
use App\Repository\ActuatorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class ActuatorControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */         private MockObject $em;
    /** @var PaginatorInterface&MockObject */             private MockObject $paginator;
    /** @var ParameterBagInterface&MockObject */          private MockObject $params;
    /** @var LoggerInterface&MockObject */                private MockObject $logger;
    /** @var ActuatorRepository&MockObject */             private MockObject $repo;
    /** @var AuthorizationCheckerInterface&MockObject */  private MockObject $authChecker;
    /** @var Environment&MockObject */                    private MockObject $twig;
    /** @var FlashBag&MockObject */                       private MockObject $flashBag;

    /** @var UserAgentTrackingFacade&MockObject */        private MockObject $tracking;

    private ActuatorController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->paginator = $this->createMock(PaginatorInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repo = $this->createMock(ActuatorRepository::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->flashBag = $this->createMock(FlashBag::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'test'],
            ['version', '2.0.0'],
        ]);

        $this->em->method('getRepository')->willReturn($this->repo);

        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['twig', true],
            ['security.authorization_checker', true],
            ['request_stack', true],
            ['parameter_bag', true],
        ]);
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['security.authorization_checker', 1, $this->authChecker],
            ['request_stack', 1, $requestStack],
            ['parameter_bag', 1, $this->params],
        ]);

        $this->controller = new ActuatorController(
            $this->em,
            $this->paginator,
            $this->params,
            $this->logger,
            $this->tracking
        );
        $this->controller->setContainer($container);
    }

    /* ============ actuator ============ */

    public function testActuatorRendersWithoutRoleAndFlashes403(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->repo->expects($this->never())->method('findActuatorOrderByDate');
        $this->paginator->expects($this->never())->method('paginate');

        $this->twig->expects($this->once())
            ->method('render')
            ->with('actuator/index.html.twig', $this->callback(
                fn($ctx) => $ctx['pagination'] === null && $ctx['marque_entreprise_short'] === 'MM'
            ))
            ->willReturn('<html>403</html>');

        $response = $this->controller->actuator(new Request());

        $this->assertSame('<html>403</html>', $response->getContent());
    }

    public function testActuatorFlashesWarningWhenRepoFails(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $this->repo->expects($this->once())
            ->method('findActuatorOrderByDate')
            ->with('DESC')
            ->willReturn(['code' => 500, 'erreur' => 'db down']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning' && str_contains($v['message'], 'db down')));

        $this->paginator->expects($this->never())->method('paginate');

        $this->twig->expects($this->once())
            ->method('render')
            ->with('actuator/index.html.twig', $this->anything())
            ->willReturn('<html>err</html>');

        $this->controller->actuator(new Request());
    }

    public function testActuatorHappyPathWithDateSort(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $query = new \stdClass(); // stand-in for query
        $this->repo->expects($this->once())
            ->method('findActuatorOrderByDate')
            ->with('ASC')
            ->willReturn(['code' => 200, 'paginator_query' => $query]);

        $pagination = $this->createMock(PaginationInterface::class);
        $this->paginator->expects($this->once())
            ->method('paginate')
            ->with($query, 2, 9)
            ->willReturn($pagination);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('actuator/index.html.twig', $this->callback(fn($ctx) => $ctx['pagination'] === $pagination))
            ->willReturn('<html>ok</html>');

        $request = new Request(['sort' => 'date_enregistrement', 'direction' => 'ASC', 'page' => 2]);
        $this->controller->actuator($request);
    }

    public function testActuatorHappyPathWithColumnSort(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $query = new \stdClass();
        $this->repo->expects($this->once())
            ->method('findActuatorOrderBy')
            ->with('nom_application', 'DESC')
            ->willReturn(['code' => 200, 'paginator_query' => $query]);

        $pagination = $this->createMock(PaginationInterface::class);
        $this->paginator->expects($this->once())->method('paginate')->willReturn($pagination);

        $this->twig->expects($this->once())->method('render')->willReturn('<html>ok</html>');

        $this->controller->actuator(new Request(['sort' => 'nom_application']));
    }

    /* ============ actuatorInfo ============ */

    public function testActuatorInfoRendersAjouterWithout403Role(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('actuator/ajouter.html.twig', $this->anything())
            ->willReturn('<html>403</html>');

        $response = $this->controller->actuatorInfo(new Request());

        $this->assertSame('<html>403</html>', $response->getContent());
    }
}
