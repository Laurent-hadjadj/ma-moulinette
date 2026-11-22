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

namespace App\Controller\NpmAudit;

use App\Entity\NpmProcessingQueue;
use App\Exception\UnexpectedExecutionPathException;
use App\Repository\NpmProcessingQueueRepository;
use App\Service\NpmAudit\{NpmAuditPayloadDecodeException, NpmAuditPayloadDecoder};
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;

/**
 * Endpoint de réception des rapports npm audit — même pipeline que
 * ApiDependencyCheckUploadController (décodage anti-abus, idempotence par
 * sha256, insertion en queue, worker asynchrone), avec une différence
 * structurelle : npm audit --json ne porte aucune information projet
 * (contrairement à projectInfo côté DC), donc les 3 headers
 * X-Project-Group/Artifact/Version sont REQUIS, pas optionnels.
 */
class ApiNpmAuditUploadController extends AbstractController
{
    private const HEADER_GROUP    = 'X-Project-Group';
    private const HEADER_ARTIFACT = 'X-Project-Artifact';
    private const HEADER_VERSION  = 'X-Project-Version';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NpmProcessingQueueRepository $queueRepository,
        private readonly NpmAuditPayloadDecoder $payloadDecoder,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route(path: '/api/secure/npm-audit/upload', name: 'api_npm_audit_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $this->logger->info('[API] 📥 Requête reçue sur /api/secure/npm-audit/upload');

        // ------- 1. Headers projet requis -----------------------------------
        $group    = $this->trimHeader($request->headers->get(self::HEADER_GROUP), 255);
        $artifact = $this->trimHeader($request->headers->get(self::HEADER_ARTIFACT), 255);
        $version  = $this->trimHeader($request->headers->get(self::HEADER_VERSION), 64);

        $missing = array_filter([
            self::HEADER_GROUP    => $group,
            self::HEADER_ARTIFACT => $artifact,
            self::HEADER_VERSION  => $version,
        ], static fn($v) => $v === null);
        if ($missing !== []) {
            $this->logger->warning('[NpmAudit-Ingest] ⚠️ Header(s) projet manquant(s).', ['headers' => array_keys($missing)]);
            return new JsonResponse(
                [
                    'code'    => 400,
                    'type'    => 'error',
                    'message' => sprintf('Header(s) requis manquant(s) : %s.', implode(', ', array_keys($missing))),
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        // ------- 2. Décodage + validation 7 étapes --------------------------
        try {
            $decoded = $this->payloadDecoder->decode(
                $request->getContent(),
                $request->headers->get('Content-Type'),
            );
        } catch (NpmAuditPayloadDecodeException $e) {
            $this->logger->warning('[NpmAudit-Ingest] ⚠️ Payload rejeté.', [
                'http_status' => $e->httpStatus,
                'reason'      => $e->getMessage(),
            ]);
            return new JsonResponse(
                [
                    'code'    => $e->httpStatus,
                    'type'    => $this->httpStatusToType($e->httpStatus),
                    'message' => $e->getMessage(),
                ],
                $e->httpStatus
            );
        }

        // ------- 3. Idempotence : sha256 déjà vu ? --------------------------
        $existing = $this->queueRepository->findByPayloadSha256($decoded->sha256);
        if ($existing !== null) {
            $this->logger->info('[NpmAudit-Ingest] ℹ️ Payload déjà reçu (idempotence).', [
                'ulid'   => $existing->getUlid(),
                'sha256' => $decoded->sha256,
                'status' => $existing->getStatus(),
            ]);
            return new JsonResponse(
                [
                    'code'    => 200,
                    'type'    => 'warning',
                    'message' => 'Rapport déjà reçu, traitement non re-déclenché.',
                    'ulid'    => $existing->getUlid(),
                    'status'  => $existing->getStatus(),
                    'sha256'  => $decoded->sha256,
                ],
                Response::HTTP_OK
            );
        }

        // ------- 4. Insertion en queue --------------------------------------
        $payloadGz = gzencode($decoded->jsonString, 6);
        if ($payloadGz === false) {
            throw new UnexpectedExecutionPathException('Échec de la compression gzip du payload.');
        }

        $entry = (new NpmProcessingQueue())
            ->setUlid((new Ulid())->toBase32())
            ->setPayloadGz($payloadGz)
            ->setPayloadSha256($decoded->sha256)
            ->setPayloadSize($decoded->decodedSize)
            ->setContentType($decoded->contentType)
            ->setProjectGroup((string) $group)
            ->setProjectArtifact((string) $artifact)
            ->setProjectVersion((string) $version)
            ->setStatus(NpmProcessingQueue::STATUS_QUEUED);

        try {
            $this->em->persist($entry);
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            // Race condition : 2 uploads simultanés du même sha256.
            $this->em->clear();
            $existing = $this->queueRepository->findByPayloadSha256($decoded->sha256);
            if ($existing !== null) {
                return new JsonResponse(
                    [
                        'code'    => 200,
                        'type'    => 'warning',
                        'message' => 'Rapport déjà reçu (race), traitement non re-déclenché.',
                        'ulid'    => $existing->getUlid(),
                        'status'  => $existing->getStatus(),
                        'sha256'  => $decoded->sha256,
                    ],
                    Response::HTTP_OK
                );
            }
            return new JsonResponse(
                ['code' => 500, 'type' => 'critical', 'message' => 'Conflit unique non résolu.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Throwable $e) {
            $this->logger->critical('[NpmAudit-Ingest] ❌ Erreur insertion queue.', ['error' => $e->getMessage()]);
            return new JsonResponse(
                ['code' => 500, 'type' => 'critical', 'message' => 'Erreur insertion en base.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $this->logger->info('[NpmAudit-Ingest] ℹ️ Payload accepté et mis en queue.', [
            'ulid'    => $entry->getUlid(),
            'sha256'  => $decoded->sha256,
            'project' => sprintf('%s:%s:%s', $group, $artifact, $version),
            'size'    => $decoded->decodedSize,
            'format'  => $decoded->contentType,
        ]);

        return new JsonResponse(
            [
                'code'    => 202,
                'type'    => 'info',
                'message' => 'Rapport accepté, traitement asynchrone en attente.',
                'ulid'    => $entry->getUlid(),
                'status'  => NpmProcessingQueue::STATUS_QUEUED,
                'sha256'  => $decoded->sha256,
            ],
            Response::HTTP_ACCEPTED
        );
    }

    #[Route(
        path: '/api/secure/npm-audit/status/{ulid}',
        name: 'api_npm_audit_status',
        methods: ['GET'],
        requirements: ['ulid' => '[A-HJKMNP-TV-Z0-9]{26}']
    )]
    public function status(string $ulid): JsonResponse
    {
        $this->logger->info('[API] 📥 Requête reçue sur /api/secure/npm-audit/status/{ulid}');

        $entry = $this->queueRepository->findByUlid($ulid);

        if ($entry === null) {
            $this->logger->info('[NpmAudit-Ingest] ℹ️ Polling status sur ulid inconnu.', ['ulid' => $ulid]);
            return new JsonResponse(
                ['code' => 404, 'type' => 'warning', 'message' => 'Ulid inconnu.', 'ulid' => $ulid],
                Response::HTTP_NOT_FOUND
            );
        }

        $payload = [
            'code'         => 200,
            'type'         => 'info',
            'ulid'         => $entry->getUlid(),
            'status'       => $entry->getStatus(),
            'sha256'       => $entry->getPayloadSha256(),
            'project'      => sprintf(
                '%s:%s:%s',
                $entry->getProjectGroup(),
                $entry->getProjectArtifact(),
                $entry->getProjectVersion()
            ),
            'content_type' => $entry->getContentType(),
            'payload_size' => $entry->getPayloadSize(),
            'attempts'     => $entry->getAttempts(),
            'created_at'   => $entry->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];

        if ($entry->getStartedAt() !== null) {
            $payload['started_at'] = $entry->getStartedAt()->format(\DateTimeInterface::ATOM);
        }
        if ($entry->getProcessedAt() !== null) {
            $payload['processed_at'] = $entry->getProcessedAt()->format(\DateTimeInterface::ATOM);
        }
        if ($entry->getErrorMessage() !== null) {
            $payload['error_message'] = $entry->getErrorMessage();
        }

        return new JsonResponse($payload, Response::HTTP_OK);
    }

    private function httpStatusToType(int $status): string
    {
        if ($status >= 500) {
            return 'critical';
        }
        if ($status >= 400) {
            return 'error';
        }
        return 'warning';
    }

    private function trimHeader(?string $raw, int $maxLength): ?string
    {
        if ($raw === null) {
            return null;
        }
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }
        return substr($trimmed, 0, $maxLength);
    }
}
