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

use App\DataFixtures\UserAgentAnalysisFixtures;
use App\Entity\UserAgentAnalysis;
use App\Repository\UserAgentAnalysisRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/* MODIF 2026-06-09 : tests Integration UserAgentAnalysisRepository.
 * Couvre insertUserAgentAnalysis avec vraie base PostgreSQL (schéma ma_moulinette).
 */
class UserAgentAnalysisRepositoryTest extends KernelTestCase
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
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.user_agent_analysis');
    }

    private function loadFixtures(): void
    {
        $fixture = new UserAgentAnalysisFixtures();
        $fixture->load($this->em);
    }

    private function getRepository(): UserAgentAnalysisRepository
    {
        return $this->em->getRepository(UserAgentAnalysis::class);
    }

    /**
     * @return array{
     *     event_type: string,
     *     url: string,
     *     session_id: string,
     *     user_id: int,
     *     visitor_id: string,
     *     device_type: string,
     *     os_name: string,
     *     os_version: string,
     *     browser_name: string,
     *     browser_version: string,
     *     is_bot: bool,
     *     detector_version: string,
     *     created_at: \DateTimeImmutable
     * }
     */
    private function buildAnalysisMap(bool $isBot = false): array
    {
        return [
            'event_type'       => 'ACCUEIL',
            'url'              => '/accueil',
            'session_id'       => 'integration-session-analysis-01',
            'user_id'          => 5,
            'visitor_id'       => '99999999-ffff-4444-bbbb-000000000002',
            'device_type'      => 'desktop',
            'os_name'          => 'Linux',
            'os_version'       => 'Ubuntu 22.04',
            'browser_name'     => 'Firefox',
            'browser_version'  => '126.0',
            'is_bot'           => $isBot,
            'detector_version' => '6.5.0',
            'created_at'       => new \DateTimeImmutable('2026-06-09 14:00:00'),
        ];
    }

    // ──── insertUserAgentAnalysis ─────────────────────────────────────────────

    public function testInsertUserAgentAnalysis_ReturnsSuccess(): void
    {
        $repo   = $this->getRepository();
        $result = $repo->insertUserAgentAnalysis($this->buildAnalysisMap());

        $this->assertSame(200, $result['code']);
        $this->assertSame('', $result['erreur']);
    }

    public function testInsertUserAgentAnalysis_AnalysisIsPersistedInDatabase(): void
    {
        $repo = $this->getRepository();
        $repo->insertUserAgentAnalysis($this->buildAnalysisMap());

        // Fixtures ont inséré 2 analyses ; après le nouvel insert on en a 3
        $all = $repo->findAll();
        $this->assertCount(3, $all);
    }

    public function testInsertUserAgentAnalysis_WithBotFlagTrue(): void
    {
        $repo   = $this->getRepository();
        $result = $repo->insertUserAgentAnalysis($this->buildAnalysisMap(true));

        $this->assertSame(200, $result['code']);
    }

    public function testInsertUserAgentAnalysis_WithAnonymousUser(): void
    {
        $repo = $this->getRepository();
        $map  = $this->buildAnalysisMap();
        $map['user_id']    = null;
        $map['session_id'] = null;
        $map['event_type'] = 'LOGIN_PAGE_VIEW';

        $result = $repo->insertUserAgentAnalysis($map);

        $this->assertSame(200, $result['code']);
    }

    public function testInsertUserAgentAnalysis_FixturesAreLoadedCorrectly(): void
    {
        $repo = $this->getRepository();
        $all  = $repo->findAll();

        // Les fixtures créent 2 analyses
        $this->assertCount(2, $all);
        $deviceTypes = array_map(fn (UserAgentAnalysis $a) => $a->getDeviceType(), $all);
        $this->assertContains('desktop', $deviceTypes);
        $this->assertContains('smartphone', $deviceTypes);
    }
}
