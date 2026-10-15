<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Controller\Zap;

use App\Entity\ZapProcessingQueue;
use App\Repository\ZapProcessingQueueRepository;
use App\Service\Zap\{ZapPayloadDecodeException, ZapPayloadDecoder};
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;

/**
 * [Description ApiZapUploadController]
 * MODIF 2026-10-13 : endpoint reception rapports ZAP (DAST).
 *
 * Flux :
 *   1. CI POST le rapport (JSON traditional-json brut, gzip, zip ou tgz)
 *   2. Le ZapPayloadDecoder valide en 7 étapes (cf service)
 *   3. Persist en BDD (gzippe en BYTEA) + sha256 unique
 *   4. Réponse 202 Accepted { ulid, status: queued, sha256 }
 *   5. Le worker app:zap:process traite ensuite
 *
 * Différence avec Dependency-Check : maven_key/project_version sont
 * obligatoires en en-têtes (X-Maven-Key, X-Project-Version) — le rapport
 * ZAP lui-même ne contient aucune identification projet Maven.
 */
class ApiZapUploadController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ZapProcessingQueueRepository $queueRepository,
        private readonly ZapPayloadDecoder $payloadDecoder,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * [Description for upload]
     * Téléchargement du rapport ZAP (json, tgz ou zip).
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route(path: '/api/secure/zap/upload', name: 'api_zap_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $this->logger->info('[API] 📥 Requête reçue sur /api/secure/zap/upload');

        $mavenKey = $this->trimHeader($request->headers->get('X-Maven-Key'), 511);
        $projectVersion = $this->trimHeader($request->headers->get('X-Project-Version'), 64);
        $targetUrl = $this->trimHeader($request->headers->get('X-Target-Url'), 2048);

        if ($mavenKey === null || $projectVersion === null) {
            $this->logger->warning('[ZAP-Ingest] ⚠️ En-têtes projet manquants.', [
                'maven_key'       => $mavenKey,
                'project_version' => $projectVersion,
            ]);
            return new JsonResponse(
                [
                    'code'    => 400,
                    'type'    => 'error',
                    'message' => 'Headers "X-Maven-Key" et "X-Project-Version" requis.',
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->logger->info('[ZAP-Ingest] ℹ️ Reception payload ZAP.', [
            'content_type'   => $request->headers->get('Content-Type'),
            'content_length' => $request->headers->get('Content-Length'),
            'maven_key'      => $mavenKey,
            'ip'             => $request->getClientIp(),
        ]);

        // ------- 1. Décodage + validation 7 étapes --------------------------
        try {
            $decoded = $this->payloadDecoder->decode(
                $request->getContent(),
                $request->headers->get('Content-Type'),
            );
        } catch (ZapPayloadDecodeException $e) {
            $this->logger->warning('[ZAP-Ingest] ⚠️ Payload rejeté.', [
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

        // ------- 2. Idempotence : sha256 deja vu ? --------------------------
        $existing = $this->queueRepository->findByPayloadSha256($decoded->sha256);
        if ($existing !== null) {
            $this->logger->info('[ZAP-Ingest] ℹ️ Payload déjà reçu (idempotence).', [
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

        // ------- 3. Insertion en queue --------------------------------------
        $entry = (new ZapProcessingQueue())
            ->setUlid((new Ulid())->toBase32())
            ->setPayloadGz(gzencode($decoded->jsonString, 6))
            ->setPayloadSha256($decoded->sha256)
            ->setPayloadSize($decoded->decodedSize)
            ->setContentType($decoded->contentType)
            ->setMavenKey($mavenKey)
            ->setProjectVersion($projectVersion)
            ->setTargetUrl($targetUrl)
            ->setStatus(ZapProcessingQueue::STATUS_QUEUED);

        try {
            $this->em->persist($entry);
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            // Race condition : 2 uploads simultanés du meme sha256.
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
            $this->logger->critical('[ZAP-Ingest] ❌ Erreur insertion queue.', ['error' => $e->getMessage()]);
            return new JsonResponse(
                ['code' => 500, 'type' => 'critical', 'message' => 'Erreur insertion en base.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $this->logger->info('[ZAP-Ingest] ℹ️ Payload accepte et mis en queue.', [
            'ulid'    => $entry->getUlid(),
            'sha256'  => $decoded->sha256,
            'project' => sprintf('%s:%s', $mavenKey, $projectVersion),
            'size'    => $decoded->decodedSize,
            'format'  => $decoded->contentType,
        ]);

        return new JsonResponse(
            [
                'code'    => 202,
                'type'    => 'info',
                'message' => 'Rapport accepte, traitement asynchrone en attente.',
                'ulid'    => $entry->getUlid(),
                'status'  => ZapProcessingQueue::STATUS_QUEUED,
                'sha256'  => $decoded->sha256,
            ],
            Response::HTTP_ACCEPTED
        );
    }

    /**
     * [Description for status]
     * Endpoint polling status.
     *
     * @param string $ulid
     *
     * @return JsonResponse
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route(
        path: '/api/secure/zap/status/{ulid}', name: 'api_zap_status', methods: ['GET'],
        requirements: ['ulid' => '[A-HJKMNP-TV-Z0-9]{26}']
    )]
    public function status(string $ulid): JsonResponse
    {
        $this->logger->info('[API] 📥 Requête reçue sur /api/secure/zap/status/{ulid}');

        $entry = $this->queueRepository->findByUlid($ulid);

        if ($entry === null) {
            $this->logger->info('[ZAP-Ingest] ℹ️ Polling status sur ulid inconnu.', ['ulid' => $ulid]);
            return new JsonResponse(
                [
                    'code'    => 404,
                    'type'    => 'warning',
                    'message' => 'Ulid inconnu.',
                    'ulid'    => $ulid,
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $payload = [
            'code'         => 200,
            'type'         => 'info',
            'ulid'         => $entry->getUlid(),
            'status'       => $entry->getStatus(),
            'sha256'       => $entry->getPayloadSha256(),
            'project'      => sprintf('%s:%s', $entry->getMavenKey(), $entry->getProjectVersion()),
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

    /**
     * [Description for httpStatusToType]
     * Mapping HTTP status -> type (convention common.css : info/warning/error/critical).
     *
     * @param int $status
     *
     * @return string
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
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

    /**
     * [Description for trimHeader]
     * Nettoie une valeur d'en-tête HTTP. Retourne null si absente ou vide
     * apres trim, tronque défensivement à la longueur cible.
     *
     * @param string|null $raw
     * @param int $maxLength
     *
     * @return string|null
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
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
