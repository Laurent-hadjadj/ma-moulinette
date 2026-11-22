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

namespace App\Tests\Integration\Controller\NpmAudit;

use App\Entity\NpmProcessingQueue;
use App\Repository\NpmProcessingQueueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test Integration end-to-end de l'endpoint POST /api/secure/npm-audit/upload
 * — même structure que ApiDependencyCheckUploadControllerTest, avec 2
 * différences : 3 headers projet REQUIS (npm audit --json n'a pas de
 * projectInfo) et le schéma validé est l'enveloppe {"full": {...}}.
 *
 * Token utilisé : test-npmaudit-ingest-token-32char (cf .env.test).
 */
class ApiNpmAuditUploadControllerTest extends WebTestCase
{
    private const URL   = '/api/secure/npm-audit/upload';
    private const TOKEN = 'test-npmaudit-ingest-token-32char';
    private const VALID_JSON = '{"full":{"auditReportVersion":2,"vulnerabilities":{},"metadata":{"vulnerabilities":{"info":0,"low":0,"moderate":0,"high":0,"critical":0,"total":0},"dependencies":{"prod":1,"dev":1,"optional":0,"peer":0,"peerOptional":0,"total":2}}}}';
    private const CONTENT_TYPE = 'application/json';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private NpmProcessingQueueRepository $repo;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);
        $this->repo   = static::getContainer()->get(NpmProcessingQueueRepository::class);

        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_processing_queue');
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
        $this->assertStringContainsString('X-NpmAudit-Token', $body['message']);
    }

    public function testReturns401WithInvalidToken(): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE'           => self::CONTENT_TYPE,
            'HTTP_X_NPMAUDIT_TOKEN'  => 'wrong-token',
        ], self::VALID_JSON);

        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('error', $body['type']);
        $this->assertStringContainsString('invalide', $body['message']);
    }

    // ════════════════════ Headers projet requis ════════════════════════════

    public function testReturns400WhenProjectHeadersMissing(): void
    {
        $this->postWithToken(self::VALID_JSON, self::CONTENT_TYPE);

        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('X-Project-Group', $body['message']);
    }

    public function testReturns400WhenOnlySomeProjectHeadersPresent(): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE'          => self::CONTENT_TYPE,
            'HTTP_X_NPMAUDIT_TOKEN' => self::TOKEN,
            'HTTP_X_PROJECT_GROUP'  => 'fr.ma-moulinette',
        ], self::VALID_JSON);

        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('X-Project-Artifact', $body['message']);
        $this->assertStringContainsString('X-Project-Version', $body['message']);
    }

    // ════════════════════ Decode (4xx) ════════════════════════════════════════

    public function testReturns415OnUnknownMagicBytes(): void
    {
        $this->postWithProjectHeaders('garbage non identifiable', self::CONTENT_TYPE);
        $this->assertSame(415, $this->client->getResponse()->getStatusCode());
    }

    public function testReturns415OnContentTypeMismatch(): void
    {
        $this->postWithProjectHeaders(self::VALID_JSON, 'application/zip');
        $this->assertSame(415, $this->client->getResponse()->getStatusCode());
    }

    public function testReturns422OnInvalidSchema(): void
    {
        $body = '{"foo":"bar"}';
        $this->postWithProjectHeaders($body, self::CONTENT_TYPE);
        $this->assertSame(422, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('"full"', $payload['message']);
    }

    public function testReturns400OnMalformedJson(): void
    {
        $this->postWithProjectHeaders('{"full":', self::CONTENT_TYPE);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testReturns400OnEmptyBody(): void
    {
        $this->postWithProjectHeaders('', self::CONTENT_TYPE);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    // ════════════════════ Happy path 202 + idempotence 200 ════════════════════

    public function testReturns202OnValidJson(): void
    {
        $this->postWithProjectHeaders(self::VALID_JSON, self::CONTENT_TYPE);

        $this->assertSame(202, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('info', $payload['type']);
        $this->assertSame(NpmProcessingQueue::STATUS_QUEUED, $payload['status']);
        $this->assertNotEmpty($payload['ulid']);
        $this->assertSame(64, strlen($payload['sha256']));

        $entity = $this->repo->findByUlid($payload['ulid']);
        $this->assertNotNull($entity);
        $this->assertSame('fr.ma-moulinette', $entity->getProjectGroup());
        $this->assertSame('angular-demo', $entity->getProjectArtifact());
        $this->assertSame('1.0.0', $entity->getProjectVersion());
        $this->assertSame(NpmProcessingQueue::CONTENT_JSON, $entity->getContentType());
        $this->assertSame(NpmProcessingQueue::STATUS_QUEUED, $entity->getStatus());
    }

    public function testReturns202OnValidGzip(): void
    {
        $compressed = gzencode(self::VALID_JSON, 6);
        self::assertNotFalse($compressed);
        $this->postWithProjectHeaders($compressed, 'application/gzip');

        $this->assertSame(202, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);

        $entity = $this->repo->findByUlid($payload['ulid']);
        $this->assertSame(NpmProcessingQueue::CONTENT_GZIP, $entity->getContentType());
    }

    public function testIdempotenceReturns200OnDuplicateSha(): void
    {
        $this->postWithProjectHeaders(self::VALID_JSON, self::CONTENT_TYPE);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());
        $first = json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->postWithProjectHeaders(self::VALID_JSON, self::CONTENT_TYPE);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $second = json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertSame('warning', $second['type']);
        $this->assertSame($first['ulid'], $second['ulid']);
        $this->assertSame($first['sha256'], $second['sha256']);
        $this->assertCount(1, $this->repo->findAll());
    }

    // ════════════════════ Polling status ═══════════════════════════════════

    public function testStatusReturns401WithoutToken(): void
    {
        $this->client->request('GET', '/api/secure/npm-audit/status/01ABCDEFGHJKMNPQRSTVWXYZ12');
        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testStatusReturns404ForUnknownUlid(): void
    {
        $this->client->request('GET', '/api/secure/npm-audit/status/01ABCDEFGHJKMNPQRSTVWXYZ12', [], [], [
            'HTTP_X_NPMAUDIT_TOKEN' => self::TOKEN,
        ]);
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('warning', $payload['type']);
        $this->assertSame('01ABCDEFGHJKMNPQRSTVWXYZ12', $payload['ulid']);
    }

    public function testStatusReturnsPayloadForKnownUlid(): void
    {
        $this->postWithProjectHeaders(self::VALID_JSON, self::CONTENT_TYPE);
        $uploadPayload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $ulid = $uploadPayload['ulid'];

        $this->client->request('GET', '/api/secure/npm-audit/status/' . $ulid, [], [], [
            'HTTP_X_NPMAUDIT_TOKEN' => self::TOKEN,
        ]);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame($ulid, $payload['ulid']);
        $this->assertSame('queued', $payload['status']);
        $this->assertSame($uploadPayload['sha256'], $payload['sha256']);
        $this->assertSame('fr.ma-moulinette:angular-demo:1.0.0', $payload['project']);
        $this->assertSame('json', $payload['content_type']);
        $this->assertSame(0, $payload['attempts']);
        $this->assertArrayHasKey('created_at', $payload);
        $this->assertArrayNotHasKey('started_at', $payload);
        $this->assertArrayNotHasKey('processed_at', $payload);
        $this->assertArrayNotHasKey('error_message', $payload);
    }

    public function testStatusRejectsBadlyFormedUlid(): void
    {
        $this->client->request('GET', '/api/secure/npm-audit/status/not-a-valid-ulid', [], [], [
            'HTTP_X_NPMAUDIT_TOKEN' => self::TOKEN,
        ]);
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    // ════════════════════ Helpers ═════════════════════════════════════════════

    private function postWithToken(string $body, string $contentType): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE'          => $contentType,
            'HTTP_X_NPMAUDIT_TOKEN' => self::TOKEN,
        ], $body);
    }

    private function postWithProjectHeaders(string $body, string $contentType): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE'            => $contentType,
            'HTTP_X_NPMAUDIT_TOKEN'   => self::TOKEN,
            'HTTP_X_PROJECT_GROUP'    => 'fr.ma-moulinette',
            'HTTP_X_PROJECT_ARTIFACT' => 'angular-demo',
            'HTTP_X_PROJECT_VERSION'  => '1.0.0',
        ], $body);
    }
}
