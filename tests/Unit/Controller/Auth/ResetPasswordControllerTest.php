<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Auth;

use App\Controller\Auth\ResetPasswordController;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\UserAgentTrackingFacade;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class ResetPasswordControllerTest extends TestCase
{
    /** @var UtilisateurRepository&MockObject */           private MockObject $utilisateurRepo;
    /** @var EntityManagerInterface&MockObject */          private MockObject $em;
    /** @var ParameterBagInterface&MockObject */           private MockObject $params;
    /** @var LoggerInterface&MockObject */                 private MockObject $logger;
    /** @var UserPasswordHasherInterface&MockObject */     private MockObject $passwordHasher;
    /** @var UserAgentTrackingFacade&MockObject */         private MockObject $tracking;
    /** @var TokenStorageInterface&MockObject */           private MockObject $tokenStorage;

    private ResetPasswordController $controller;

    protected function setUp(): void
    {
        $this->utilisateurRepo = $this->createMock(UtilisateurRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'test'],
            ['version', '2.0.0'],
        ]);

        $this->em->method('getRepository')->willReturn($this->utilisateurRepo);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, [
                'security.token_storage', 'parameter_bag',
            ], true)
        );
        $container->method('get')->willReturnMap([
            ['security.token_storage', 1, $this->tokenStorage],
            ['parameter_bag', 1, $this->params],
        ]);

        $this->controller = new ResetPasswordController(
            $this->utilisateurRepo, $this->em, $this->params, $this->logger, $this->passwordHasher, $this->tracking
        );
        $this->controller->setContainer($container);
    }

    /* ============ resetMotDePasse ============ */

    public function testResetMotDePasseThrowsWhenTokenUserIsNotUtilisateur(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $this->controller->resetMotDePasse(new Request(), $token);
    }

    /* ============ apiResetMotDePasse ============ */

    public function testApiResetMotDePasseReturns400OnInvalidJson(): void
    {
        $response = $this->controller->apiResetMotDePasse($this->jsonRequest('garbage'));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testApiResetMotDePasseReturns400WhenResetPasswordKeyMissing(): void
    {
        $response = $this->controller->apiResetMotDePasse($this->jsonRequest(['other' => 'x']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testApiResetMotDePasseReturnsErrorWhenUpdateFails(): void
    {
        $user = $this->makeUser();
        $this->stubTokenStorageUser($user);

        $this->utilisateurRepo->expects($this->once())
            ->method('updateUtilisateurResetPassword')
            ->willReturn(['code' => 500, 'erreur' => 'db fail']);

        $response = $this->controller->apiResetMotDePasse($this->jsonRequest(['reset_password' => true]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('db fail', $data['trace']);
    }

    public function testApiResetMotDePasseHappyPath(): void
    {
        $user = $this->makeUser();
        $this->stubTokenStorageUser($user);

        $capturedMap = null;
        $this->utilisateurRepo->expects($this->once())
            ->method('updateUtilisateurResetPassword')
            ->with($this->callback(function ($map) use (&$capturedMap) {
                $capturedMap = $map;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $response = $this->controller->apiResetMotDePasse($this->jsonRequest(['reset_password' => true]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertTrue($capturedMap['reset_password']);
        $this->assertSame('u@example.com', $capturedMap['courriel']);
    }

    public function testResetMotDePasseRendersFormWhenNotSubmitted(): void
    {
        $user = $this->makeUser();
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);

        $this->tracking->expects($this->once())->method('track')->with('RESET_PASSWORD');

        // Form factory : create a form that's not submitted → renders template
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn(new FormView());

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('auth/reset.html.twig', $this->callback(fn($ctx) =>
                isset($ctx['resetPasswordForm']) && $ctx['courriel'] === 'u@example.com'
            ))
            ->willReturn('<html>reset</html>');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, [
                'security.token_storage', 'parameter_bag', 'form.factory', 'twig',
            ], true)
        );
        $container->method('get')->willReturnMap([
            ['security.token_storage', 1, $this->tokenStorage],
            ['parameter_bag', 1, $this->params],
            ['form.factory', 1, $formFactory],
            ['twig', 1, $twig],
        ]);
        $this->controller->setContainer($container);

        $response = $this->controller->resetMotDePasse(new Request(), $token);

        $this->assertSame('<html>reset</html>', $response->getContent());
    }

    /* ============ helpers ============ */

    private function jsonRequest(array|string $body): Request
    {
        if (is_string($body)) {
            $content = $body;
        } elseif ($body === []) {
            $content = '{}';
        } else {
            $content = json_encode($body);
        }
        return new Request([], [], [], [], [], [], $content);
    }

    private function makeUser(): Utilisateur
    {
        $u = new Utilisateur();
        $u->setCourriel('u@example.com');
        return $u;
    }

    private function stubTokenStorageUser(Utilisateur $user): void
    {
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);
        $this->tokenStorage->method('getToken')->willReturn($token);
    }
}
