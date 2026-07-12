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

namespace App\Controller\DependencyCheck;

use App\Entity\DcProcessingQueue;
use App\Repository\DcProcessingQueueRepository;
use App\Service\DependencyCheck\{PayloadDecodeException, PayloadDecoder};
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;

/**
* [Description ApiDependencyCheckUploadController]
* MODIF 2026-05-08  : endpoint reception rapports OWASP DependencyCheck.
 *
 * Flux :
 *   1. CI GitLab POST le rapport (JSON brut, gzip, zip ou tgz)
 *   2. Le PayloadDecoder valide en 7 étapes (cf service)
 *   3. Persist en BDD (gzippe en BYTEA) + sha256 unique
 *   4. Réponse 202 Accepted { ulid, status: queued, sha256 }
 *   5. Le worker app:dependency-check:process traite ensuite
 *
 */
class ApiDependencyCheckUploadController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DcProcessingQueueRepository $queueRepository,
        private readonly PayloadDecoder $payloadDecoder,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * [Description for upload]
     * Téléchargement des fichiers (json, tgz ou zip)
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 10/05/2026 23:23:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route(
        path: '/api/secure/dependency-check/upload', name: 'api_dc_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $this->logger->info('[DC-Ingest] ℹ️ Reception payload OWASP DependencyCheck.', [
            'content_type'   => $request->headers->get('Content-Type'),
            'content_length' => $request->headers->get('Content-Length'),
            'ip'             => $request->getClientIp(),
        ]);

        // ------- 1. Décodage + validation 7 étapes --------------------------
        try {
            $decoded = $this->payloadDecoder->decode(
                $request->getContent(),
                $request->headers->get('Content-Type'),
            );
        } catch (PayloadDecodeException $e) {
            $this->logger->warning('[DC-Ingest] ⚠️ Payload rejeté.', [
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
            $this->logger->info('[DC-Ingest] ℹ️ Payload déjà reçu (idempotence).', [
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

        /* MODIF 2026-05-12 : lecture des headers
         * optionnels d'archétype/parent envoyés par la CI. Toute valeur
         * absente ou vide reste NULL en base. Tronquage défensif aux
         * longueurs DB pour éviter une erreur SQL en cas de chaîne anormale. */
        $parentLabel = $this->trimHeader($request->headers->get('X-Parent-Label'), 128);
        $parentVersion = $this->trimHeader($request->headers->get('X-Parent-Version'), 64);
        $archetypeVersion = $this->trimHeader($request->headers->get('X-Archetype-Version'), 64);

        // ------- 3. Insertion en queue --------------------------------------
        $entry = (new DcProcessingQueue())
            ->setUlid((new Ulid())->toBase32())
            ->setPayloadGz(gzencode($decoded->jsonString, 6))
            ->setPayloadSha256($decoded->sha256)
            ->setPayloadSize($decoded->decodedSize)
            ->setContentType($decoded->contentType)
            ->setProjectGroup($decoded->projectGroup)
            ->setProjectArtifact($decoded->projectArtifact)
            ->setProjectVersion($decoded->projectVersion)
            ->setStatus(DcProcessingQueue::STATUS_QUEUED)
            ->setParentLabel($parentLabel)
            ->setParentVersion($parentVersion)
            ->setArchetypeVersion($archetypeVersion);

        try {
            $this->em->persist($entry);
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            // Race condition : 2 uploads simultanés du meme sha256.
            // L'autre a gagne, on renvoie le ulid existant.
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
            $this->logger->critical('[DC-Ingest] ❌ Erreur insertion queue.', ['error' => $e->getMessage()]);
            return new JsonResponse(
                [   'code' => 500,
                    'type' => 'critical',
                    'message' => 'Erreur insertion en base.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $this->logger->info('[DC-Ingest] ℹ️ Payload accepte et mis en queue.', [
            'ulid'    => $entry->getUlid(),
            'sha256'  => $decoded->sha256,
            'project' => sprintf('%s:%s:%s', $decoded->projectGroup, $decoded->projectArtifact, $decoded->projectVersion),
            'size'    => $decoded->decodedSize,
            'format'  => $decoded->contentType,
        ]);

        return new JsonResponse(
            [
                'code'    => 202,
                'type'    => 'info',
                'message' => 'Rapport accepte, traitement asynchrone en attente.',
                'ulid'    => $entry->getUlid(),
                'status'  => DcProcessingQueue::STATUS_QUEUED,
                'sha256'  => $decoded->sha256,
            ],
            Response::HTTP_ACCEPTED
        );
    }

    /**
     * [Description for status]
     * MODIF 2026-05-08 : endpoint polling status.
     *
     * La CI GitLab peut interroger cet endpoint avec l'ulid renvoyé par /upload
     * pour verifier l’état du traitement (queued / processing / done / failed).
     *
     * Auth : meme bearer token via DependencyCheckTokenSubscriber.
     *
     * Réponses :
     *   - 200 + payload complet (ulid, status, sha256, project, timestamps)
     *   - 404 si ulid inconnu
     *
     * @param string $ulid
     *
     * @return JsonResponse
     *
     * Created at: 10/05/2026 23:26:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route(path: '/api/secure/dependency-check/status/{ulid}', name: 'api_dc_status',methods: ['GET'], requirements: ['ulid' => '[A-HJKMNP-TV-Z0-9]{26}']
    )]
    public function status(string $ulid): JsonResponse
    {
        $entry = $this->queueRepository->findByUlid($ulid);

        if ($entry === null) {
            $this->logger->info('[DC-Ingest] ℹ️ Polling status sur ulid inconnu.', ['ulid' => $ulid]);
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
            'project'      => sprintf('%s:%s:%s',
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

    /**
     * [Description for httpStatusToType]
     * Mapping HTTP status -> type (convention common.css : info/warning/error/critical).
     * @param int $status
     *
     * @return string
     *
     * Created at: 10/05/2026 23:31:36 (Europe/Paris)
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
     * MODIF 2026-05-12 : nettoie une valeur
     * d'en-tête HTTP optionnelle. Retourne null si vide apres trim, tronque
     * défensivement a la longueur cible pour éviter une SQL truncation error
     * en cas de chaîne anormale envoyée par la CI.
     *
     * @param string|null $raw
     * @param int $maxLength
     *
     * @return string|null
     *
     * Created at: 15/05/2026 08:49:37 (Europe/Paris)
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
