<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\DataFixtures\UserAgentEventFixtures;
use App\Entity\UserAgentEvent;
use App\Repository\UserAgentEventRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/* MODIF 2026-06-09 : tests Integration UserAgentEventRepository.
 * Couvre insertUserAgentEvent, selectPendingEvents, updateProcessingStatus
 * avec vraie base PostgreSQL (schéma ma_moulinette).
 */
class UserAgentEventRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $em = null;

    protected function setUp(): void
    {
        self::bootKernel();
        /** @var EntityManagerInterface $em */
        $em       = self::getContainer()->get(EntityManagerInterface::class);
        $this->em = $em;
        $this->purgeDatabase();
        $this->loadFixtures();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em?->close();
        $this->em = null;
    }

    private function purgeDatabase(): void
    {
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.user_agent_event');
    }

    private function loadFixtures(): void
    {
        $fixture = new UserAgentEventFixtures();
        $fixture->load($this->em);
    }

    private function getRepository(): UserAgentEventRepository
    {
        return $this->em->getRepository(UserAgentEvent::class);
    }

    /**
     * @return array{
     *     event_type: string,
     *     url: string,
     *     user_agent: string,
     *     session_id: string,
     *     user_id: int,
     *     visitor_id: string,
     *     auth_state: string,
     *     processing_status: string,
     *     ip_hash: string,
     *     created_at: \DateTimeImmutable,
     * }
     */
    private function buildEventMap(): array
    {
        return [
            'event_type'        => 'ACCUEIL',
            'url'               => '/accueil',
            'user_agent'        => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'session_id'        => 'integration-session-test',
            'user_id'           => 3,
            'visitor_id'        => '99999999-ffff-4444-bbbb-000000000001',
            'auth_state'        => 'AUTHENTICATED',
            'processing_status' => 'PENDING',
            'ip_hash'           => hash('sha256', '172.16.0.1test-integration'),
            'created_at'        => new \DateTimeImmutable('2026-06-09 12:00:00'),
        ];
    }

    // ──── insertUserAgentEvent ────────────────────────────────────────────────

    public function testInsertUserAgentEvent_ReturnsSuccess(): void
    {
        $repo   = $this->getRepository();
        $result = $repo->insertUserAgentEvent($this->buildEventMap());

        $this->assertSame(200, $result['code']);
        $this->assertSame('', $result['erreur']);
    }

    public function testInsertUserAgentEvent_EventIsPersistedInDatabase(): void
    {
        $repo = $this->getRepository();
        $repo->insertUserAgentEvent($this->buildEventMap());

        // Vérifie en lisant via selectPendingEvents : on a les 2 fixtures + 1 nouveau = 3
        $select = $repo->selectPendingEvents(100);
        $this->assertSame(200, $select['code']);
        $this->assertCount(3, $select['liste']);
    }

    // ──── selectPendingEvents ─────────────────────────────────────────────────

    public function testSelectPendingEvents_ReturnsTwoPendingFromFixtures(): void
    {
        $repo   = $this->getRepository();
        $result = $repo->selectPendingEvents(20);

        $this->assertSame(200, $result['code']);
        $this->assertCount(2, $result['liste']);
        foreach ($result['liste'] as $row) {
            $this->assertSame('PENDING', $row['processing_status']);
        }
    }

    public function testSelectPendingEvents_RespectsLimit(): void
    {
        $repo   = $this->getRepository();
        $result = $repo->selectPendingEvents(1);

        $this->assertSame(200, $result['code']);
        $this->assertCount(1, $result['liste']);
    }

    public function testSelectPendingEvents_DoesNotReturnDoneEvents(): void
    {
        $repo   = $this->getRepository();
        $result = $repo->selectPendingEvents(100);

        $this->assertSame(200, $result['code']);
        foreach ($result['liste'] as $row) {
            $this->assertNotSame('DONE', $row['processing_status']);
        }
    }

    // ──── updateProcessingStatus ──────────────────────────────────────────────

    public function testUpdateProcessingStatus_SwitchesToDone(): void
    {
        $repo = $this->getRepository();

        // Récupère un événement PENDING depuis les fixtures
        $pending = $repo->selectPendingEvents(1);
        $this->assertSame(200, $pending['code']);
        $this->assertNotEmpty($pending['liste']);
        $id = (int) $pending['liste'][0]['id'];

        $processedAt = new \DateTimeImmutable('2026-06-09 10:01:00');
        $result      = $repo->updateProcessingStatus($id, 'DONE', $processedAt);

        $this->assertSame(200, $result['code']);
        $this->assertSame('', $result['erreur']);

        // Vérifie que le statut a bien changé : il ne reste plus qu'1 PENDING
        $afterUpdate = $repo->selectPendingEvents(100);
        $this->assertCount(1, $afterUpdate['liste']);
    }

    public function testUpdateProcessingStatus_WithNullProcessedAt(): void
    {
        $repo    = $this->getRepository();
        $pending = $repo->selectPendingEvents(1);
        $id      = (int) $pending['liste'][0]['id'];

        $result = $repo->updateProcessingStatus($id, 'ERROR', null);

        $this->assertSame(200, $result['code']);
    }
}
