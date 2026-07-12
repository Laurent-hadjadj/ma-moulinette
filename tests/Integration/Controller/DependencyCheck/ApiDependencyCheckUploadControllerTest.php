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

namespace App\Tests\Integration\Controller\DependencyCheck;

use App\Entity\DcProcessingQueue;
use App\Repository\DcProcessingQueueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * MODIF 2026-05-08: test Integration end-to-end de l'endpoint
 * POST /api/secure/dependency-check/upload.
 *
 * Couvre :
 *  - auth bearer (401 sans token, 401 mauvais, 202 bon)
 *  - decode (415 magic bytes, 415 mismatch CT, 422 schema)
 *  - persistence + idempotence (sha256 unique)
 *
 * Contrainte : la table dc_processing_queue doit être présente dans la BDD test
 * (cf migrations/initialisation/20_tables/dc_processing_queue.sql).
 *
 * Token utilise : test-dc-ingest-token-32chars-aaaa (cf .env.test).
 */
class ApiDependencyCheckUploadControllerTest extends WebTestCase
{
    private const URL   = '/api/secure/dependency-check/upload';
    private const TOKEN = 'test-dc-ingest-token-32chars-aaaa';
    private const VALID_JSON = '{"reportSchema":"1.1","projectInfo":{"groupID":"fr.ma-moulinette","artifactID":"ma-moulinette","version":"1.0"},"dependencies":[]}';
    private const CONTENT_TYPE = 'application/json';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private DcProcessingQueueRepository $repo;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);
        $this->repo   = static::getContainer()->get(DcProcessingQueueRepository::class);

        // Nettoyage table avant chaque test (isolation)
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.dc_processing_queue');
    }

    // ════════════════════ Auth ═══════════════════════════════════════════════

    public function testReturns401WithoutToken(): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE' => self::CONTENT_TYPE,
        ], self::VALID_JSON);

        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('error', $body['type']);
        $this->assertStringContainsString('X-DependencyCheck-Token', $body['message']);
    }

    public function testReturns401WithInvalidToken(): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE'                  => self::CONTENT_TYPE,
            'HTTP_X_DEPENDENCYCHECK_TOKEN'  => 'wrong-token',
        ], self::VALID_JSON);

        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('error', $body['type']);
        $this->assertStringContainsString('invalide', $body['message']);
    }

    // ════════════════════ Decode (4xx) ════════════════════════════════════════

    public function testReturns415OnUnknownMagicBytes(): void
    {
        $this->postWithToken('garbage non identifiable', self::CONTENT_TYPE);
        $this->assertSame(415, $this->client->getResponse()->getStatusCode());
    }

    public function testReturns415OnContentTypeMismatch(): void
    {
        // JSON valide mais Content-Type declare zip
        $this->postWithToken(self::VALID_JSON, 'application/zip');
        $this->assertSame(415, $this->client->getResponse()->getStatusCode());
    }

    public function testReturns422OnInvalidSchema(): void
    {
        // JSON valide mais sans reportSchema
        $body = '{"foo":"bar"}';
        $this->postWithToken($body, self::CONTENT_TYPE);
        $this->assertSame(422, $this->client->getResponse()->getStatusCode());
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('reportSchema', $payload['message']);
    }

    public function testReturns400OnMalformedJson(): void
    {
        $this->postWithToken('{"reportSchema":', self::CONTENT_TYPE);
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
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('info', $payload['type']);
        $this->assertSame(DcProcessingQueue::STATUS_QUEUED, $payload['status']);
        $this->assertNotEmpty($payload['ulid']);
        $this->assertSame(64, strlen($payload['sha256']));

        // Verif persistance
        $entity = $this->repo->findByUlid($payload['ulid']);
        $this->assertNotNull($entity);
        $this->assertSame('fr.ma-moulinette', $entity->getProjectGroup());
        $this->assertSame('ma-moulinette', $entity->getProjectArtifact());
        $this->assertSame('1.0', $entity->getProjectVersion());
        $this->assertSame(DcProcessingQueue::CONTENT_JSON, $entity->getContentType());
        $this->assertSame(DcProcessingQueue::STATUS_QUEUED, $entity->getStatus());
    }

    public function testReturns202OnValidGzip(): void
    {
        $compressed = gzencode(self::VALID_JSON, 6);
        $this->postWithToken($compressed, 'application/gzip');

        $this->assertSame(202, $this->client->getResponse()->getStatusCode());
        $payload = json_decode($this->client->getResponse()->getContent(), true);

        $entity = $this->repo->findByUlid($payload['ulid']);
        $this->assertSame(DcProcessingQueue::CONTENT_GZIP, $entity->getContentType());
    }

    public function testIdempotenceReturns200OnDuplicateSha(): void
    {
        // 1er post : 202
        $this->postWithToken(self::VALID_JSON, self::CONTENT_TYPE);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());
        $first = json_decode($this->client->getResponse()->getContent(), true);

        // 2eme post : meme payload -> 200 idempotence
        $this->postWithToken(self::VALID_JSON, self::CONTENT_TYPE);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $second = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('warning', $second['type']);
        $this->assertSame($first['ulid'], $second['ulid']);
        $this->assertSame($first['sha256'], $second['sha256']);

        // Une seule row en BDD
        $this->assertCount(1, $this->repo->findAll());
    }

    public function testIdempotenceCrossFormat(): void
    {
        // Post JSON brut
        $this->postWithToken(self::VALID_JSON, self::CONTENT_TYPE);
        $first = json_decode($this->client->getResponse()->getContent(), true);

        // Post meme JSON gzippe -> sha256 identique (calcul sur décompresse)
        // -> idempotence reconnue
        $compressed = gzencode(self::VALID_JSON, 6);
        $this->postWithToken($compressed, 'application/gzip');
        $second = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame($first['ulid'], $second['ulid']);
        $this->assertSame($first['sha256'], $second['sha256']);
        $this->assertCount(1, $this->repo->findAll());
    }

    // ════════════════════ Polling status (lot 2.7) ═══════════════════════════

    public function testStatusReturns401WithoutToken(): void
    {
        $this->client->request('GET', '/api/secure/dependency-check/status/01ABCDEFGHJKMNPQRSTVWXYZ12');
        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testStatusReturns404ForUnknownUlid(): void
    {
        // 26 chars Crockford base32 valide mais inexistant en BDD
        $this->client->request('GET', '/api/secure/dependency-check/status/01ABCDEFGHJKMNPQRSTVWXYZ12', [], [], [
            'HTTP_X_DEPENDENCYCHECK_TOKEN' => self::TOKEN,
        ]);
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('warning', $payload['type']);
        $this->assertSame('01ABCDEFGHJKMNPQRSTVWXYZ12', $payload['ulid']);
    }

    public function testStatusReturnsPayloadForKnownUlid(): void
    {
        // 1. Poste un payload valide pour créer une entree en queue
        $this->postWithToken(self::VALID_JSON, self::CONTENT_TYPE);
        $uploadPayload = json_decode($this->client->getResponse()->getContent(), true);
        $ulid = $uploadPayload['ulid'];

        // 2. Polling status sur ce ulid
        $this->client->request('GET', '/api/secure/dependency-check/status/' . $ulid, [], [], [
            'HTTP_X_DEPENDENCYCHECK_TOKEN' => self::TOKEN,
        ]);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($ulid, $payload['ulid']);
        $this->assertSame('queued', $payload['status']);
        $this->assertSame($uploadPayload['sha256'], $payload['sha256']);
        $this->assertSame('fr.ma-moulinette:ma-moulinette:1.0', $payload['project']);
        $this->assertSame('json', $payload['content_type']);
        $this->assertSame(0, $payload['attempts']);
        $this->assertArrayHasKey('created_at', $payload);
        $this->assertArrayNotHasKey('started_at', $payload, 'pas de started_at en queued');
        $this->assertArrayNotHasKey('processed_at', $payload, 'pas de processed_at en queued');
        $this->assertArrayNotHasKey('error_message', $payload);
    }

    public function testStatusRejectsBadlyFormedUlid(): void
    {
        // ULID format invalide (caractères interdits, trop court) -> 404 routing
        $this->client->request('GET', '/api/secure/dependency-check/status/not-a-valid-ulid', [], [], [
            'HTTP_X_DEPENDENCYCHECK_TOKEN' => self::TOKEN,
        ]);
        // Le requirement regex empêche le match -> 404 du router (route non trouvée)
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    // ════════════════════ Helpers ═════════════════════════════════════════════

    private function postWithToken(string $body, string $contentType): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE'                  => $contentType,
            'HTTP_X_DEPENDENCYCHECK_TOKEN'  => self::TOKEN,
        ], $body);
    }
}
