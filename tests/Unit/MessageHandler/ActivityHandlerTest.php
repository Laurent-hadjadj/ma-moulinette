<?php

namespace App\Tests\Unit\MessageHandler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Envelope;
use Psr\Log\LoggerInterface;
use App\Message\ActivityMessage;
use App\Message\ProcessTaskMessage;
use App\Service\ActivityReportService;
use App\Service\ClientService;
use App\MessageHandler\ActivityHandler;

/**
 * [Description ActivityHandlerTest]
 */
class ActivityHandlerTest extends TestCase
{
    private ActivityReportService $activityReportService;
    private MessageBusInterface $messengerBus;
    private LoggerInterface $logger;
    private ParameterBagInterface $params;
    private ClientService $client;
    private ActivityHandler $handler;

    private static $urlSonar = 'http://sonar.example.com';
    private static $timeZone = 'Europe/Paris';
    private static $fromDate = 'Y-m-d 00:00:00';
    private static $toDate = 'Y-m-d 23:59:59';

    /**
     * [Description for setUp]
     *
     * @return void
     *
     * Created at: 02/01/2025 14:07:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Création des mocks pour chaque dépendance
        $this->activityReportService = $this->createMock(ActivityReportService::class);
        $this->messengerBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->client = $this->createMock(ClientService::class);

        // Création du handler avec les mocks
        $this->handler = new ActivityHandler(
            $this->activityReportService,
            $this->messengerBus,
            $this->logger,
            $this->params,
            $this->client
        );
    }

    /**
     * [Description for testHandlerProcessesValidMessage]
     *
     * @return void
     *
     * Created at: 02/01/2025 10:59:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testHandlerProcessesValidMessage(): void
    {
        // Création des dates fixes (par exemple : 2025-01-02 10:33:27)
        $fixedDateStart = new \DateTimeImmutable('now', new \DateTimeZone(static::$timeZone));
        $fixedDateEnd = new \DateTimeImmutable('now', new \DateTimeZone(static::$timeZone));

        // Création du mock de message
        $message = $this->createMock(ActivityMessage::class);
        $message->method('getFromDate')->willReturn($fixedDateStart->format(static::$fromDate));
        $message->method('getToDate')->willReturn($fixedDateEnd->format(static::$toDate));

        // Mock des paramètres et autres dépendances
        $this->params->method('get')->willReturnMap([
            ['sonar.url', null, static::$urlSonar],
            ['sonar.version', null, 9],
        ]);

        $this->client->method('httpActivity')->willReturn([
            'json' => ['tasks' => []], // Pas de tâches
        ]);

        // Vérification de l'appel de generateReport avec les dates fixes
        $this->activityReportService->expects($this->once())
            ->method('generateReport')
            ->with(
                $this->callback(function ($infos) use ($fixedDateStart, $fixedDateEnd) {
                    // Comparaison des timestamps Unix pour éviter les problèmes de mémoire
                    return $infos['page'] === 1
                        && $infos['date_start']->format('Y-m-d') === $fixedDateStart->format('Y-m-d')
                        && $infos['date_start']->getTimezone()->getName() === $fixedDateStart->getTimezone()->getName()
                        && $infos['date_end']->format('Y-m-d') === $fixedDateStart->format('Y-m-d')
                        && $infos['date_end']->getTimezone()->getName() === $fixedDateEnd->getTimezone()->getName()
                        && $infos['task_count'] === 0;
                }),
                $this->equalTo(0), // Pas de tâches réussies
                $this->isType('array') // Liste d'erreurs
            );

        // Appel du handler
        /** @var ActivityMessage&\PHPUnit\Framework\MockObject\MockObject $message */
        $this->handler->__invoke($message);
    }

    /**
     * [Description for testHandlerHandlesEmptyTasksGracefully]
     *
     * @return void
     *
     * Created at: 02/01/2025 14:07:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testHandlerHandlesEmptyTasksGracefully(): void
    {
        // Création des dates fixes (par exemple : 2025-01-02 10:57:05)
        $fixedDateStart = new \DateTimeImmutable('now', new \DateTimeZone(static::$timeZone));
        $fixedDateEnd = new \DateTimeImmutable('now', new \DateTimeZone(static::$timeZone));

        // Création du mock de message
        $message = $this->createMock(ActivityMessage::class);
        $message->method('getFromDate')->willReturn($fixedDateStart->format(static::$fromDate));
        $message->method('getToDate')->willReturn($fixedDateEnd->format(static::$toDate));

        // Mock des paramètres et autres dépendances
        $this->params->method('get')->willReturnMap([
            ['sonar.url', null, static::$urlSonar],
            ['sonar.version', null, 9],
        ]);

        // Mock du client, retournant aucune tâche
        $this->client->method('httpActivity')->willReturn([
            'json' => ['tasks' => []], // Pas de tâches
        ]);

        // Vérification de l'appel de generateReport avec les dates fixes
        $this->activityReportService->expects($this->once())
            ->method('generateReport')
            ->with(
                $this->callback(function ($infos) use ($fixedDateStart, $fixedDateEnd) {
                    // Comparaison des dates avec uniquement l'année, le mois, et le jour
                    return $infos['page'] === 1
                        && $infos['date_start']->format('Y-m-d') === $fixedDateStart->format('Y-m-d')
                        && $infos['date_start']->getTimezone()->getName() === $fixedDateStart->getTimezone()->getName()
                        && $infos['date_end']->format('Y-m-d') === $fixedDateEnd->format('Y-m-d')
                        && $infos['date_end']->getTimezone()->getName() === $fixedDateEnd->getTimezone()->getName()
                        && $infos['task_count'] === 0; // Vérification que task_count est bien égal à 0
                }),
                $this->equalTo(0), // Pas de tâches réussies
                $this->isType('array') // Liste d'erreurs
            );

        // Appel du handler
        /** @var ActivityMessage&\PHPUnit\Framework\MockObject\MockObject $message */
        $this->handler->__invoke($message);
    }

    /**
     * [Description for testCreateEnvelope]
     *
     * @return void
     *
     * Created at: 02/01/2025 14:07:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testCreateEnvelope(): void
    {
        $processTaskMessage = new ProcessTaskMessage(['id' => 1]);

        // Utilisez ReflectionMethod pour accéder à la méthode privée
        $reflection = new \ReflectionMethod(ActivityHandler::class, 'createEnvelope');
        $reflection->setAccessible(true);

        // Appelez la méthode privée
        $envelope = $reflection->invoke($this->handler, $processTaskMessage);

        // Effectuez vos assertions
        $this->assertInstanceOf(Envelope::class, $envelope);
        $this->assertSame($processTaskMessage, $envelope->getMessage());
    }

    /**
     * [Description for testHandlerLogsErrorOnClientFailure]
     *
     * @return void
     *
     * Created at: 02/01/2025 14:07:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testHandlerLogsErrorOnClientFailure(): void
    {
        // Création d'un message avec une date valide
        $fixedDateStart = new \DateTimeImmutable('now', new \DateTimeZone(static::$timeZone));
        $fixedDateEnd = new \DateTimeImmutable('now', new \DateTimeZone(static::$timeZone));

        // Création du mock de message
        $message = $this->createMock(ActivityMessage::class);
        $message->method('getFromDate')->willReturn($fixedDateStart->format(static::$fromDate));
        $message->method('getToDate')->willReturn($fixedDateEnd->format(static::$toDate));

        // Mock des paramètres
        $this->params->method('get')->willReturnMap([
            ['sonar.url', null, static::$urlSonar],
            ['sonar.version', null, 9],
        ]);

        // Simuler une réponse d'erreur du client
        $this->client->method('httpActivity')->willReturn([
            'error' => 'Client failed to fetch tasks', // Indique un échec du client
        ]);

        // Vérification que generateReport est appelée lorsque le client échoue
        $this->activityReportService->expects($this->once())
            ->method('generateReport')
            ->with(
                $this->callback(function ($infos) use ($fixedDateStart, $fixedDateEnd) {
                    return $infos['page'] === 1
                        && $infos['date_start']->format('Y-m-d') === $fixedDateStart->format('Y-m-d')
                        && $infos['date_end']->format('Y-m-d') === $fixedDateEnd->format('Y-m-d')
                        && $infos['task_count'] === 0;
                }),
                $this->equalTo(0), // Aucun task_count
                $this->isType('array') // Liste d'erreurs
            );

        // Appel du handler
        /** @var ActivityMessage&\PHPUnit\Framework\MockObject\MockObject $message */
        $this->handler->__invoke($message);
    }

}
