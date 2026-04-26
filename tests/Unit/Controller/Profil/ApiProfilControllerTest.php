<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Profil;

use App\Controller\Profil\ApiProfilController;
use App\Entity\Profiles;
use App\Entity\ProfilesHistorique;
use App\Entity\Properties;
use App\Repository\ProfilesHistoriqueRepository;
use App\Repository\ProfilesRepository;
use App\Repository\PropertiesRepository;
use App\Service\ClientService;
use App\Service\UrlBuilderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class ApiProfilControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */          private MockObject $em;
    /** @var ClientService&MockObject */                   private MockObject $client;
    /** @var Security&MockObject */                        private MockObject $security;
    /** @var ParameterBagInterface&MockObject */           private MockObject $params;
    /** @var LoggerInterface&MockObject */                 private MockObject $logger;
    /** @var UrlBuilderService&MockObject */               private MockObject $urlBuilder;
    /** @var ProfilesRepository&MockObject */              private MockObject $profilesRepo;
    /** @var PropertiesRepository&MockObject */            private MockObject $propertiesRepo;
    /** @var ProfilesHistoriqueRepository&MockObject */    private MockObject $historiqueRepo;

    private ApiProfilController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->security = $this->createMock(Security::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->profilesRepo = $this->createMock(ProfilesRepository::class);
        $this->propertiesRepo = $this->createMock(PropertiesRepository::class);
        $this->historiqueRepo = $this->createMock(ProfilesHistoriqueRepository::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'test'],
            ['version', '2.0.0'],
            ['sonar.url', 'https://sonar.example.com'],
        ]);

        $this->em->method('getRepository')->willReturnMap([
            [Profiles::class, $this->profilesRepo],
            [Properties::class, $this->propertiesRepo],
            [ProfilesHistorique::class, $this->historiqueRepo],
        ]);

        $this->urlBuilder->method('build')->willReturn('https://sonar/api/...');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([['parameter_bag', true]]);
        $container->method('get')->willReturnMap([['parameter_bag', 1, $this->params]]);

        $this->controller = new ApiProfilController(
            $this->em,
            $this->client,
            $this->security,
            $this->params,
            $this->logger,
            $this->urlBuilder
        );
        $this->controller->setContainer($container);
    }

    /* ============ listeQualityProfiles ============ */

    public function testListeQualityProfilesReturns403WithoutRole(): void
    {
        $this->security->method('isGranted')->willReturn(false);

        $response = $this->controller->listeQualityProfiles(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testListeQualityProfilesReturnsErrorWhenSonarFails(): void
    {
        $this->security->method('isGranted')->willReturn(true);
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 503, 'erreur' => 'sonar down']);

        $response = $this->controller->listeQualityProfiles(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(503, $data['code']);
    }

    public function testListeQualityProfilesReturns404WhenNoProfiles(): void
    {
        $this->security->method('isGranted')->willReturn(true);
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['profiles' => []],
        ]);

        $response = $this->controller->listeQualityProfiles(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(404, $data['code']);
    }

    public function testListeQualityProfilesReturnsErrorWhenDeleteFails(): void
    {
        $this->security->method('isGranted')->willReturn(true);
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['profiles' => [['key' => 'p1']]],
        ]);
        $this->profilesRepo->expects($this->once())
            ->method('deleteProfiles')
            ->willReturn(['code' => 500, 'erreur' => 'delete fail']);

        $response = $this->controller->listeQualityProfiles(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('delete fail', $data['trace']);
    }

    public function testListeQualityProfilesHappyPath(): void
    {
        $this->security->method('isGranted')->willReturn(true);
        $profiles = [['key' => 'p1', 'language' => 'java']];
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200, 'json' => ['profiles' => $profiles],
        ]);
        $this->profilesRepo->method('deleteProfiles')->willReturn(['code' => 200]);
        $this->profilesRepo->method('insertProfiles')->willReturn(['code' => 200, 'nombre' => 1]);
        $this->profilesRepo->method('selectProfiles')->willReturn(['code' => 200, 'liste' => $profiles]);
        $this->propertiesRepo->method('updatePropertiesProfiles')->willReturn(['code' => 200]);

        $response = $this->controller->listeQualityProfiles(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame($profiles, $data['liste_profil']);
    }

    /* ============ listeQualityLangage ============ */

    public function testListeQualityLangageReturnsErrorWhenLanguageRepoFails(): void
    {
        $this->profilesRepo->expects($this->once())
            ->method('selectProfilesLanguage')
            ->willReturn(['code' => 500, 'erreur' => 'fail']);

        $response = $this->controller->listeQualityLangage(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testListeQualityLangageReturnsErrorWhenRuleCountFails(): void
    {
        $this->profilesRepo->method('selectProfilesLanguage')->willReturn([
            'code' => 200, 'labels' => [['profile' => 'java']],
        ]);
        $this->profilesRepo->expects($this->once())
            ->method('selectProfilesRuleCount')
            ->willReturn(['code' => 500, 'erreur' => 'fail', 'data-set' => []]);

        $response = $this->controller->listeQualityLangage(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testListeQualityLangageHappyPath(): void
    {
        $this->profilesRepo->method('selectProfilesLanguage')->willReturn([
            'code' => 200,
            'labels' => [
                ['profile' => 'java'],
                ['profile' => 'php'],
            ],
        ]);
        $this->profilesRepo->method('selectProfilesRuleCount')->willReturn([
            'code' => 200,
            'data-set' => [
                ['total' => 200],
                ['total' => 150],
            ],
        ]);

        $response = $this->controller->listeQualityLangage(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(['java', 'php'], $data['label']);
        $this->assertSame([200, 150], $data['dataset']);
    }

    /* ============ listeQualityOff ============ */

    public function testListeQualityOffReturns400OnInvalidJson(): void
    {
        $response = $this->controller->listeQualityOff($this->jsonRequest('garbage'));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testListeQualityOffReturns400WhenLangageMissing(): void
    {
        $response = $this->controller->listeQualityOff($this->jsonRequest(['other' => 'x']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testListeQualityOffReturnsProfilesForLanguage(): void
    {
        $this->profilesRepo->expects($this->once())
            ->method('selectProfiles')
            ->with('false', 'java')
            ->willReturn(['code' => 200, 'liste' => [['name' => 'Custom Java']]]);
        $this->profilesRepo->expects($this->once())
            ->method('countProfiles')
            ->with('false', 'java')
            ->willReturn(['request' => [['total' => 1]]]);

        $response = $this->controller->listeQualityOff($this->jsonRequest(['langage' => 'java']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame('Custom Java', $data['listeProfil'][0]['name']);
    }

    public function testListeQualityOffReturns500OnException(): void
    {
        $this->profilesRepo->method('selectProfiles')->willThrowException(new \RuntimeException('boom'));

        $response = $this->controller->listeQualityOff($this->jsonRequest(['langage' => 'php']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('boom', $data['trace']);
    }

    /* ============ profilDetails ============ */

    public function testProfilDetailsRendersNCWithoutToken(): void
    {
        $twig = $this->createMock(\Twig\Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('profil/details.html.twig', ['profil' => 'NC'])
            ->willReturn('<html>no-token</html>');

        $this->setTwigContainer($twig);

        $response = $this->controller->profilDetails(new Request());

        $this->assertSame('<html>no-token</html>', $response->getContent());
    }

    public function testProfilDetailsFlashesWhenTokenIsInvalidFormat(): void
    {
        // Token that decodes to only 2 parts (no language|profil)
        $token = $this->buildToken('saltonly');

        $twig = $this->createMock(\Twig\Environment::class);
        $twig->method('render')->willReturn('<html>invalid</html>');
        $flashBag = $this->createMock(FlashBag::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => is_array($v) && str_contains($v['message'], 'invalide')));

        $this->setTwigContainer($twig, $flashBag);

        $response = $this->controller->profilDetails(new Request(['token' => $token]));

        $this->assertSame('<html>invalid</html>', $response->getContent());
    }

    public function testProfilDetailsFlashesWhenLanguageUnsupported(): void
    {
        $token = $this->buildToken('salt|klingon|profil');

        $twig = $this->createMock(\Twig\Environment::class);
        $twig->method('render')->willReturn('<html>unsupported</html>');
        $flashBag = $this->createMock(FlashBag::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => is_array($v) && str_contains($v['message'], 'supporté')));

        $this->setTwigContainer($twig, $flashBag);

        $response = $this->controller->profilDetails(new Request(['token' => $token]));

        $this->assertSame('<html>unsupported</html>', $response->getContent());
    }

    public function testProfilDetailsFlashesWhenSonarQubeFails(): void
    {
        $token = $this->buildToken('salt|java|profil');

        $this->client->method('httpSonarQube')->willReturn([
            'code' => 503, 'erreur' => 'timeout',
        ]);

        $twig = $this->createMock(\Twig\Environment::class);
        $twig->method('render')->willReturn('<html>sonar-fail</html>');
        $flashBag = $this->createMock(FlashBag::class);
        $flashBag->expects($this->once())->method('add');

        $this->setTwigContainer($twig, $flashBag);

        $response = $this->controller->profilDetails(new Request(['token' => $token]));

        $this->assertSame('<html>sonar-fail</html>', $response->getContent());
    }

    public function testProfilDetailsRendersHappyPathWithEvents(): void
    {
        $token = $this->buildToken('salt|java|my-profil');

        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'events' => [
                    ['date' => '2026-04-20T10:00:00+0200', 'action' => 'ACTIVATED',
                     'authorName' => 'alice', 'ruleKey' => 'R1', 'ruleName' => 'Rule 1', 'params' => []],
                    ['date' => '2026-04-21T10:00:00+0200', 'action' => 'UPDATED',
                     'ruleKey' => 'R2', 'ruleName' => 'Rule 2', 'params' => []],
                ],
                'total' => 2,
            ],
        ]);

        // Historique repo stubs
        $this->historiqueRepo->method('selectProfilesHistoriqueAction')->willReturn(['nombre' => [['nombre' => 5]]]);
        $this->historiqueRepo->method('selectProfilesHistoriqueDateTri')->willReturn(['liste' => [['date' => '2026-04-20']]]);
        $this->historiqueRepo->method('selectProfilesHistoriqueDateCourteGroupeBy')
            ->willReturn(['liste' => [['date_courte' => '2026-04-21']]]);
        $this->historiqueRepo->method('selectProfilesHistoriqueLangageDateCourte')
            ->willReturn(['liste' => [
                ['date' => '2026-04-21T10:00:00+0200', 'action' => 'UPDATED',
                 'auteur' => 'alice', 'rule' => 'R2', 'description' => 'Rule 2', 'detail' => '{}'],
                ['date' => '2026-04-21T11:00:00+0200', 'action' => 'ACTIVATED',
                 'auteur' => 'bob', 'rule' => 'R3', 'description' => 'Rule 3', 'detail' => '{}'],
            ]]);

        $twig = $this->createMock(\Twig\Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('profil/details.html.twig', $this->callback(fn($ctx) =>
                $ctx['profil'] === 'my-profil' && $ctx['langage'] === 'java'
            ))
            ->willReturn('<html>ok</html>');

        $this->setTwigContainer($twig);

        $response = $this->controller->profilDetails(new Request(['token' => $token]));

        $this->assertSame('<html>ok</html>', $response->getContent());
    }

    /* ============ helper ============ */

    private function jsonRequest(array|string $body): Request
    {
        $content = is_string($body) ? $body : json_encode($body, JSON_FORCE_OBJECT);
        return new Request([], [], [], [], [], [], $content);
    }

    /** Token format: str_rot13(base64_encode("salt|language|profil")) */
    private function buildToken(string $plaintext): string
    {
        return str_rot13(base64_encode($plaintext));
    }

    private function setTwigContainer(\Twig\Environment|MockObject $twig, ?FlashBag $flashBag = null): void
    {
        $container = $this->createMock(ContainerInterface::class);

        if ($flashBag !== null) {
            $session = $this->createMock(Session::class);
            $session->method('getFlashBag')->willReturn($flashBag);
            $requestStack = $this->createMock(RequestStack::class);
            $requestStack->method('getSession')->willReturn($session);

            $container->method('has')->willReturnMap([
                ['twig', true], ['parameter_bag', true], ['request_stack', true],
            ]);
            $container->method('get')->willReturnMap([
                ['twig', 1, $twig],
                ['parameter_bag', 1, $this->params],
                ['request_stack', 1, $requestStack],
            ]);
        } else {
            $container->method('has')->willReturnMap([
                ['twig', true], ['parameter_bag', true],
            ]);
            $container->method('get')->willReturnMap([
                ['twig', 1, $twig],
                ['parameter_bag', 1, $this->params],
            ]);
        }

        $this->controller->setContainer($container);
    }
}
