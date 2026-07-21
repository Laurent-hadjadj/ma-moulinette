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

namespace App\Controller\Admin;

use App\Repository\UserRoleLogRepository;
use App\Service\PdfExportService;
use App\Service\UserAgent\UserAgentTrackingFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{Response, Request, JsonResponse};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

/**
 * Consultation, archivage (export CSV), suppression et export PDF du
 * journal des changements de rôle (table user_role_log, alimentée par
 * UserRoleLoggerService à chaque UtilisateurCrudController::updateEntity()).
 *
 * Ce journal était jusqu'ici écrit mais jamais consultable depuis
 * l'application — voir back-office/utilisateur.md.
 */
#[IsGranted('ROLE_INTERNAL', message: 'Vous ne disposez pas des droits suffisants pour accéder à cette page.', statusCode: 403)]
class UserRoleLogController extends AbstractController
{
    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;
    private const ERREUR_400 = 'Aucune ligne sélectionnée (Erreur 400).';


    public function __construct(
        ParameterBagInterface $params,
        private LoggerInterface $logger,
        private UserRoleLogRepository $repository,
        private PdfExportService $pdfExport,
        private UserAgentTrackingFacade $tracking,
    ) {
        $this->logoEntreprise = $params->get('logo.entreprise');
        $this->marqueEntrepriseShort = $params->get('marque.entreprise.short');
        $this->marqueEntrepriseLong = $params->get('marque.entreprise.long');
        $this->environnement = $params->get('environnement');
        $this->version = $params->get('version');
        $this->dateCopyright = \date('Y');
    }


    /**
     * [Description for genericRender]
     *
     * @return array<string, mixed>
     *
     * Created at: 21/07/2026 19:12:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function genericRender(): array
    {
        return [
            'type_footer' => null,
            'logo_entreprise' => $this->logoEntreprise,
            'marque_entreprise_short' => $this->marqueEntrepriseShort,
            'marque_entreprise_long' => $this->marqueEntrepriseLong,
            'env' => $this->environnement,
            'version' => $this->version,
            'date_copyright' => $this->dateCopyright,
        ];
    }


    /**
     * [Description for index]
     *
     * @return Response
     *
     * Created at: 21/07/2026 19:12:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/admin/journal-roles', name: 'admin_user_role_log_index')]
    public function index(): Response
    {
        $this->tracking->track('JOURNAL_ROLES');

        return $this->render('admin/user_role_log.html.twig', $this->genericRender());
    }

    /**
     * [Description for formatRow]
     * Décode les colonnes JSON brutes (DBAL ne fait pas la conversion sur du
     * SQL brut) et formate la date pour l'affichage.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     *
     * Created at: 21/07/2026 19:13:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function formatRow(array $row): array
    {
        $createdAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', (string) $row['created_at'])
            ?: \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string) $row['created_at']);

        return [
            'id' => (int) $row['id'],
            'userEmail' => $row['user_email'],
            'editorEmail' => $row['editor_email'],
            'oldRoles' => json_decode((string) $row['old_roles'], true) ?? [],
            'newRoles' => json_decode((string) $row['new_roles'], true) ?? [],
            'oldActive' => (bool) $row['old_active'],
            'newActive' => (bool) $row['new_active'],
            'alerts' => json_decode((string) ($row['alerts'] ?? '[]'), true) ?? [],
            'date' => $createdAt ? $createdAt->format('d/m/Y H:i:s') : (string) $row['created_at'],
        ];
    }

    /**
     * [Description for buildFilters]
     *
     * @param Request $request
     *
     * @return array<string, mixed>
     *
     * Created at: 21/07/2026 19:13:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function buildFilters(Request $request): array
    {
        $filters = [];

        $courriel = trim((string) $request->query->get('courriel', ''));
        if ($courriel !== '') {
            $filters['courriel'] = $courriel;
        }

        $start = $request->query->get('start');
        if ($start) {
            $filters['start'] = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $start) ?: null;
        }

        $end = $request->query->get('end');
        if ($end) {
            $filters['end'] = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $end) ?: null;
        }

        return $filters;
    }

    /**
     * [Description for list]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 21/07/2026 19:14:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/admin/journal-roles/list', name: 'admin_user_role_log_api_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $result = $this->repository->findFiltered($this->buildFilters($request));

        if ((int) $result['code'] !== 200) {
            $this->logger->error('[Journal-Roles] 🔴 Erreur de lecture', ['erreur' => $result['erreur'] ?? null]);

            return new JsonResponse([
                'code' => 500,
                'type' => 'critical',
                'message' => 'Une erreur est survenue lors de la lecture du journal (Erreur 500).',
            ], Response::HTTP_OK);
        }

        $lignes = array_map(fn (array $row) => $this->formatRow($row), $result['liste']);

        return new JsonResponse([
            'code' => 200,
            'count' => count($lignes),
            'lignes' => $lignes,
        ], Response::HTTP_OK);
    }

    /**
     * [Description for requestedIds]
     *
     * @param Request $request
     *
     * @return int[]
     *
     * Created at: 21/07/2026 19:14:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function requestedIds(Request $request): array
    {
        return array_values(array_filter(array_map(
            static fn ($id) => (int) $id,
            $request->request->all('ids')
        )));
    }

    /**
     * [Description for archive]
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 21/07/2026 19:14:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/admin/journal-roles/archive', name: 'admin_user_role_log_archive', methods: ['POST'])]
    public function archive(Request $request): Response
    {
        $ids = $this->requestedIds($request);

        if ($ids === []) {
            return new JsonResponse([
                'code' => 400,
                'type' => 'warning',
                'message' => self::ERREUR_400,
            ], Response::HTTP_OK);
        }

        $result = $this->repository->findByIds($ids);
        if ((int) $result['code'] !== 200) {
            return new JsonResponse([
                'code' => 500,
                'type' => 'critical',
                'message' => 'Une erreur est survenue lors de l’archivage (Erreur 500).',
            ], Response::HTTP_OK);
        }

        $lignes = array_map(fn (array $row) => $this->formatRow($row), $result['liste']);

        $this->logger->info('[Journal-Roles-Archive] ℹ️ Export CSV de la sélection', [
            'count' => count($lignes),
            'user' => $this->getUser()?->getUserIdentifier(),
        ]);

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['Date', 'Compte modifié', 'Éditeur', 'Rôles avant', 'Rôles après', 'Actif avant', 'Actif après', 'Alertes'], ';', '"', '\\');
        foreach ($lignes as $ligne) {
            fputcsv($stream, [
                $ligne['date'],
                $ligne['userEmail'],
                $ligne['editorEmail'],
                implode(', ', $ligne['oldRoles']),
                implode(', ', $ligne['newRoles']),
                $ligne['oldActive'] ? 'Oui' : 'Non',
                $ligne['newActive'] ? 'Oui' : 'Non',
                implode(', ', $ligne['alerts']),
            ], ';', '"', '\\');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        $filename = 'journal_roles_' . date('Ymd_His') . '.csv';

        // BOM UTF-8 pour Excel
        return new Response("\xEF\xBB\xBF" . $csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * [Description for delete]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 21/07/2026 19:16:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/admin/journal-roles/delete', name: 'admin_user_role_log_delete', methods: ['POST'])]
    public function delete(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('journal_roles_delete', (string) $request->request->get('_token'))) {
            return new JsonResponse([
                'code' => 403,
                'type' => 'error',
                'message' => 'Jeton de sécurité invalide (Erreur 403).',
            ], Response::HTTP_OK);
        }

        $ids = $this->requestedIds($request);

        if ($ids === []) {
            return new JsonResponse([
                'code' => 400,
                'type' => 'warning',
                'message' => self::ERREUR_400,
            ], Response::HTTP_OK);
        }

        $result = $this->repository->deleteByIds($ids);

        if ((int) $result['code'] !== 200) {
            $this->logger->error('[Journal-Roles-Delete] 🔴 Erreur de suppression', ['erreur' => $result['erreur'] ?? null]);

            return new JsonResponse([
                'code' => 500,
                'type' => 'critical',
                'message' => 'Une erreur est survenue lors de la suppression (Erreur 500).',
            ], Response::HTTP_OK);
        }

        $this->logger->warning('[Journal-Roles-Delete] ⚠️ Suppression de lignes du journal des rôles', [
            'count' => $result['supprime'],
            'user' => $this->getUser()?->getUserIdentifier(),
        ]);

        return new JsonResponse([
            'code' => 200,
            'type' => 'success',
            'message' => sprintf('%d ligne(s) supprimée(s).', $result['supprime']),
            'supprime' => $result['supprime'],
        ], Response::HTTP_OK);
    }

    /**
     * [Description for pdf]
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 21/07/2026 19:16:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/admin/journal-roles/pdf', name: 'admin_user_role_log_pdf', methods: ['POST'])]
    public function pdf(Request $request): Response
    {
        $ids = $this->requestedIds($request);

        if ($ids === []) {
            return new JsonResponse([
                'code' => 400,
                'type' => 'warning',
                'message' => self::ERREUR_400,
            ], Response::HTTP_OK);
        }

        $result = $this->repository->findByIds($ids);
        if ((int) $result['code'] !== 200) {
            return new JsonResponse([
                'code' => 500,
                'type' => 'critical',
                'message' => 'Une erreur est survenue lors de la génération du rapport (Erreur 500).',
            ], Response::HTTP_OK);
        }

        $lignes = array_map(fn (array $row) => $this->formatRow($row), $result['liste']);

        $pdf = $this->pdfExport->generateUserRoleLogPdf($lignes, count($lignes) . ' ligne(s) sélectionnée(s)');

        $filename = 'journal_roles_' . date('Ymd_His') . '.pdf';

        return new Response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
