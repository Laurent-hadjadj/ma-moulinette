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

namespace App\Tests\Integration\Controller\Zap;

use App\Entity\ZapProcessingQueue;
use App\Repository\ZapProcessingQueueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * MODIF 2026-10-13 : test Integration end-to-end de l'endpoint
 * POST /api/secure/zap/upload, sur le gabarit
 * ApiDependencyCheckUploadControllerTest.
 *
 * Couvre :
 *  - auth bearer (401 sans token, 401 mauvais, 202 bon)
 *  - en-têtes projet obligatoires (400 si absents — différence avec DC, dont
 *    l'identification projet vient du payload lui-même)
 *  - decode (415 magic bytes, 415 mismatch CT, 422 schema)
 *  - persistence + idempotence (sha256 unique)
 *
 * Contrainte : la table zap_processing_queue doit être présente dans la BDD
 * test (cf migrations/POSTGRESQL/20_tables/zap_processing_queue.sql).
 *
 * Token utilise : test-zap-ingest-token-32chars-aaa (cf .env.test).
 */
class ApiZapUploadControllerTest extends WebTestCase
{
    private const URL   = '/api/secure/zap/upload';
    private const TOKEN = 'test-zap-ingest-token-32chars-aaa';
    private const VALID_JSON = '{"@version":"2.17.0","@generated":"Tue, 13 Oct 2026 12:00:00","site":[{"@name":"http://exemple.local/","alerts":[]}]}';
    private const CONTENT_TYPE = 'application/json';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private ZapProcessingQueueRepository $repo;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);
        $this->repo   = static::getContainer()->get(ZapProcessingQueueRepository::class);

        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.zap_processing_queue');
    }

    // ════════════════════ Auth ═══════════════════════════════════════════════

    public function testReturns401WithoutToken(): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE' => self::CONTENT_TYPE,
        ], self::VALID_JSON);

        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('error', $body['type']);
        $this->assertStringContainsString('X-Zap-Token', $body['message']);
    }

    public function testReturns401WithInvalidToken(): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE'        => self::CONTENT_TYPE,
            'HTTP_X_ZAP_TOKEN'    => 'wrong-token',
            'HTTP_X_MAVEN_KEY'    => 'fr.ma-moulinette:ma-moulinette',
            'HTTP_X_PROJECT_VERSION' => '1.0',
        ], self::VALID_JSON);

        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('error', $body['type']);
        $this->assertStringContainsString('invalide', $body['message']);
    }

    // ════════════════════ En-têtes projet obligatoires ═════════════════════════

    public function testReturns400WhenMavenKeyHeaderMissing(): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE'           => self::CONTENT_TYPE,
            'HTTP_X_ZAP_TOKEN'       => self::TOKEN,
            'HTTP_X_PROJECT_VERSION' => '1.0',
        ], self::VALID_JSON);

        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('X-Maven-Key', $payload['message']);
    }

    public function testReturns400WhenProjectVersionHeaderMissing(): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE'     => self::CONTENT_TYPE,
            'HTTP_X_ZAP_TOKEN' => self::TOKEN,
            'HTTP_X_MAVEN_KEY' => 'fr.ma-moulinette:ma-moulinette',
        ], self::VALID_JSON);

        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    // ════════════════════ Decode (4xx) ════════════════════════════════════════

    public function testReturns415OnUnknownMagicBytes(): void
    {
        $this->postWithToken('garbage non identifiable', self::CONTENT_TYPE);
        $this->assertSame(415, $this->client->getResponse()->getStatusCode());
    }

    public function testReturns415OnContentTypeMismatch(): void
    {
        $this->postWithToken(self::VALID_JSON, 'application/zip');
        $this->assertSame(415, $this->client->getResponse()->getStatusCode());
    }

    public function testReturns422OnInvalidSchema(): void
    {
        $body = '{"foo":"bar"}';
        $this->postWithToken($body, self::CONTENT_TYPE);
        $this->assertSame(422, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('@version', $payload['message']);
    }

    public function testReturns400OnMalformedJson(): void
    {
        $this->postWithToken('{"@version":', self::CONTENT_TYPE);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testReturns400OnEmptyBody(): void
    {
        $this->postWithToken('', self::CONTENT_TYPE);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    // ════════════════════ Happy path 202 + idempotence 200 ════════════════════

    public function testReturns202OnValidJson(): void
    {
        $this->postWithToken(self::VALID_JSON, self::CONTENT_TYPE);

        $this->assertSame(202, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('info', $payload['type']);
        $this->assertSame(ZapProcessingQueue::STATUS_QUEUED, $payload['status']);
        $this->assertNotEmpty($payload['ulid']);
        $this->assertSame(64, strlen($payload['sha256']));

        $entity = $this->repo->findByUlid($payload['ulid']);
        $this->assertNotNull($entity);
        $this->assertSame('fr.ma-moulinette:ma-moulinette', $entity->getMavenKey());
        $this->assertSame('1.0', $entity->getProjectVersion());
        $this->assertSame(ZapProcessingQueue::CONTENT_JSON, $entity->getContentType());
        $this->assertSame(ZapProcessingQueue::STATUS_QUEUED, $entity->getStatus());
    }

    public function testReturns202OnValidGzip(): void
    {
        $compressed = gzencode(self::VALID_JSON, 6);
        self::assertNotFalse($compressed);
        $this->postWithToken($compressed, 'application/gzip');

        $this->assertSame(202, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);

        $entity = $this->repo->findByUlid($payload['ulid']);
        $this->assertSame(ZapProcessingQueue::CONTENT_GZIP, $entity->getContentType());
    }

    public function testIdempotenceReturns200OnDuplicateSha(): void
    {
        $this->postWithToken(self::VALID_JSON, self::CONTENT_TYPE);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());
        $first = json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->postWithToken(self::VALID_JSON, self::CONTENT_TYPE);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $second = json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertSame('warning', $second['type']);
        $this->assertSame($first['ulid'], $second['ulid']);
        $this->assertSame($first['sha256'], $second['sha256']);
        $this->assertCount(1, $this->repo->findAll());
    }

    // ════════════════════ Polling status ═══════════════════════════════════════

    public function testStatusReturns401WithoutToken(): void
    {
        $this->client->request('GET', '/api/secure/zap/status/01ABCDEFGHJKMNPQRSTVWXYZ12');
        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testStatusReturns404ForUnknownUlid(): void
    {
        $this->client->request('GET', '/api/secure/zap/status/01ABCDEFGHJKMNPQRSTVWXYZ12', [], [], [
            'HTTP_X_ZAP_TOKEN' => self::TOKEN,
        ]);
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('warning', $payload['type']);
        $this->assertSame('01ABCDEFGHJKMNPQRSTVWXYZ12', $payload['ulid']);
    }

    public function testStatusReturnsPayloadForKnownUlid(): void
    {
        $this->postWithToken(self::VALID_JSON, self::CONTENT_TYPE);
        $uploadPayload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $ulid = $uploadPayload['ulid'];

        $this->client->request('GET', '/api/secure/zap/status/' . $ulid, [], [], [
            'HTTP_X_ZAP_TOKEN' => self::TOKEN,
        ]);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame($ulid, $payload['ulid']);
        $this->assertSame('queued', $payload['status']);
        $this->assertSame($uploadPayload['sha256'], $payload['sha256']);
        $this->assertSame('fr.ma-moulinette:ma-moulinette:1.0', $payload['project']);
        $this->assertSame('json', $payload['content_type']);
        $this->assertSame(0, $payload['attempts']);
        $this->assertArrayHasKey('created_at', $payload);
        $this->assertArrayNotHasKey('started_at', $payload);
        $this->assertArrayNotHasKey('processed_at', $payload);
        $this->assertArrayNotHasKey('error_message', $payload);
    }

    public function testStatusRejectsBadlyFormedUlid(): void
    {
        $this->client->request('GET', '/api/secure/zap/status/not-a-valid-ulid', [], [], [
            'HTTP_X_ZAP_TOKEN' => self::TOKEN,
        ]);
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    private function postWithToken(string $body, string $contentType): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE'           => $contentType,
            'HTTP_X_ZAP_TOKEN'       => self::TOKEN,
            'HTTP_X_MAVEN_KEY'       => 'fr.ma-moulinette:ma-moulinette',
            'HTTP_X_PROJECT_VERSION' => '1.0',
        ], $body);
    }
}
