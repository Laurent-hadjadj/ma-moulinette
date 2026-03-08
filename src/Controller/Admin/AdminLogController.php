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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;
use App\Service\LogArchiveService;
use App\Service\UserAgentTrackingFacade;

/**
 * [Description AdminLogController]
 */
#[IsGranted('ROLE_INTERNAL', message: 'Vous ne disposez pas des droits suffisants pour accéder à cette page.', statusCode: 403)]
class AdminLogController extends AbstractController
{

    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

    /**
     * [Description for __construct]
     *
     * @param  private
     * @param  private
     * @param  private
     * @param  private
     *
     * Created at: 13/12/2025 20:25:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
        private LogArchiveService $logArchive,
        private UserAgentTrackingFacade $tracking
    ) {
        $this->params = $params;
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
     * @return array
     *
     * Created at: 13/12/2025 20:26:07 (Europe/Paris)
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
            'date_copyright' => $this->dateCopyright
        ];
    }

    /**
     * [Description for index]
     *
     * @return Response
     *
     * Created at: 13/12/2025 20:26:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/admin/logs', name: 'admin_logs_index')]
    public function index(): Response
    {
        $this->tracking->track('ADMIN_LOGS');

        $logDir = $this->getParameter('kernel.logs_dir');
        $files = [];

        foreach (scandir($logDir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $logDir . '/' . $file;

            if (is_file($path)) {
                $files[] = [
                    'name' => $file,
                    'size' => filesize($path),
                    'modified' => filemtime($path),
                ];
            }
        }

        $render = static::genericRender();
        $render['files'] = $files;

        return $this->render('admin/admin_log.html.twig', $render);
    }

    /**
     * [Description for list]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 13/12/2025 22:55:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/admin/logs/api/list', name: 'admin_logs_api_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $env = $request->query->get('env');

        // Valeur par défaut = environnement courant
        if (!$env) {
            $env = $this->getParameter('kernel.environment');
        }

        $types = $request->query->all('types');

        $logs = $this->logArchive->listLogs(
            $env,
            $types ?: null,
        );

        $data_logs = array_map(static fn ($log) => [
                'name'  => $log['name'],
                'env'   => $log['env'],
                'type'  => $log['type'],
                'size'  => $log['size'],
                'mtime' => date('Y-m-d H:i:s', $log['mtime']),
            ], $logs);

        $this->logger->debug('[Admin-Log] 🛠️ Listing des logs demandé', [
            'env' => $env,
            'types' => $types,
        ]);

        return new JsonResponse([
            'code' => 200,
            'count' => count($logs),
            'logs' => $data_logs
        ], Response::HTTP_OK);
    }

    /**
     * [Description for downloadSelection]
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 13/12/2025 22:55:21 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/admin/logs/download-selection', name: 'admin_logs_download_selection', methods: ['POST'])]
    public function downloadSelection(Request $request): Response
    {
        $files = $request->request->all('files');

        $this->logger->info('[Admin-Log-Download] ℹ️ Demande un ZIP de logs sélectionnés', [
            'count' => count($files),
            'user' => $this->getUser()?->getUserIdentifier(),
        ]);

        if (empty($files)) {
            $this->logger->warning('[Admin-Log-Download] ⚠️Téléchargement de logs sans sélection');

            return new JsonResponse([
                'code' => 400,
                'type' => 'warning',
                'message' => 'Aucun fichier sélectionné (Erreur 400).'
            ], Response::HTTP_OK);
        }

        try {
            $zipPath = $this->logArchive->createZipFromFilenames($files);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la création du ZIP de logs', [
                'exception' => $e,
            ]);

            return new JsonResponse([
                'code' => 500,
                'type' => 'critical',
                'message' => 'Erreur lors de la génération de l’archive (Erreur 500).'
            ], Response::HTTP_OK);
        }

        return new BinaryFileResponse($zipPath, 200, [
            'Content-Type' => 'application/zip',
        ]);
    }

}
