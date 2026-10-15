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

use App\Controller\Traits\{AppUserAware, ProjetPerimetreGuard};
use App\Entity\{OwaspDast, ZapScan};
use App\Service\UserAgent\UserAgentTrackingFacade;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * [Description ZapPageController]
 * MODIF 2026-10-13 : page de restitution de l'indicateur ZAP (DAST).
 * Une seule page, centrée sur un projet : KPI, graphiques et
 * historique, scopé au projet du token.
 *
 * Accès : même mécanisme de token que OwaspController (ROT13+Base64,
 * `salt|maven_key`) — pas de nouvel encodage côté client, le lien vient de
 * la page OWASP qui repasse le token déjà décodé.
 */
#[IsGranted('ROLE_SECURITY')]
class ZapPageController extends AbstractController
{
    use AppUserAware;
    use ProjetPerimetreGuard;

    private static string $page = 'zap/index.html.twig';
    private static string $erreur400 = '❌ La requête est incorrecte (Erreur 400).';
    private static string $infoAucunProjet = 'ℹ️ Sélectionnez un projet depuis la page Projet pour accéder à cette vue.';

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    /**
     * [Description for __construct]
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        ParameterBagInterface $params,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private readonly UserAgentTrackingFacade $tracking,
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
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
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
     * [Description for addFlashAndRender]
     *
     * @param string $type
     * @param string $message
     * @param array<string, mixed> $render
     *
     * @return Response
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function addFlashAndRender(string $type, string $message, array $render): Response
    {
        $this->addFlash('notice', ['type' => $type, 'message' => $message]);
        return $this->render(self::$page, $render);
    }

    /**
     * [Description for decodeToken]
     * Même format que OwaspController::decodeToken() (`salt|maven_key`,
     * ROT13 + Base64) — copie privée, cohérente avec la duplication déjà
     * existante entre Owasp/Suivi/CoSUI/CleanCode/Répartition.
     *
     * @param string $token
     *
     * @return string|null
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function decodeToken(string $token): ?string
    {
        $string = str_rot13($token);
        $decoded = base64_decode($string, true);
        if ($decoded === false) {
            $this->logger->warning('[ZAP] ⚠️ Échec du décodage base64 du token.');
            return null;
        }

        $parts = preg_split('/[|]+/', $decoded);
        if (count($parts) !== 2) {
            $this->logger->warning('[ZAP] ⚠️ Format de token invalide après décodage.');
            return null;
        }

        return $parts[1];
    }

    /**
     * [Description for index]
     * Page web ZAP (DAST) — centrée sur un seul projet.
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/zap', name: 'zap', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->tracking->track('ZAP_PAGE');

        $render = $this->genericRender();
        $render['scan'] = null;
        $render['history'] = [];
        $render['owaspByReferential'] = [];
        $render['categoryLabels'] = self::categoryLabels();
        $render['zapChartData'] = ['categories' => [], 'history' => []];

        $token = $request->query->get('token');
        if (empty($token)) {
            $this->logger->info('[ZAP] ℹ️ Token absent, aucun projet sélectionné.');
            return $this->addFlashAndRender('info', self::$infoAucunProjet, $render);
        }

        $maven_key = $this->decodeToken($token);
        if (null === $maven_key) {
            $this->logger->error('[ZAP] ❌ Échec du décodage du token.');
            return $this->addFlashAndRender('error', self::$erreur400, $render);
        }

        $perimetre = $this->verifierPerimetreProjet($maven_key, '[ZAP]');
        if ($perimetre['code'] !== 200) {
            $message = $perimetre['message'] ?? "Une erreur est survenue lors de la vérification du périmètre (Erreur {$perimetre['code']}).";
            return $this->addFlashAndRender('warning', $message, $render);
        }

        $scanRepository = $this->em->getRepository(ZapScan::class);
        $scan = $scanRepository->findLatestByMavenKey($maven_key);
        if ($scan === null) {
            return $this->render(self::$page, $render);
        }

        $history = $scanRepository->listHistoryByMavenKey($maven_key);
        $owaspByReferential = $this->em->getRepository(OwaspDast::class)->findBy(['scan' => $scan]);

        $render['scan'] = $scan;
        $render['history'] = $history;
        $render['owaspByReferential'] = $owaspByReferential;
        $render['zapChartData'] = [
            'categories' => $this->buildCategoryChartData($owaspByReferential),
            'history' => $this->buildHistoryChartData($history),
        ];

        return $this->render(self::$page, $render);
    }

    /**
     * [Description for buildCategoryChartData]
     * Un jeu de données par référentiel présent, pour le bar chart « Alertes
     * par catégorie » (dernier scan uniquement).
     *
     * @param OwaspDast[] $owaspByReferential
     *
     * @return array<int, array{referential: int, categories: array<int, array{n: int, label: string, count: int}>}>
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function buildCategoryChartData(array $owaspByReferential): array
    {
        $labels = self::categoryLabels();
        $result = [];
        foreach ($owaspByReferential as $owaspDast) {
            $referential = $owaspDast->getReferentialOwasp();
            $categories = [];
            for ($i = 1; $i <= 10; $i++) {
                $categories[] = [
                    'n' => $i,
                    'label' => $labels[$referential][$i] ?? "A{$i}",
                    'count' => $owaspDast->getCategoryCount($i),
                ];
            }
            $result[] = ['referential' => $referential, 'categories' => $categories];
        }
        return $result;
    }

    /**
     * [Description for buildHistoryChartData]
     * Un point par scan, en ordre chronologique croissant (contrairement à
     * $history qui reste décroissant pour le tableau Historique), pour le
     * line chart « Évolution du risque ».
     *
     * @param ZapScan[] $history
     *
     * @return array<int, array{date: string, high: int, medium: int, low: int, informational: int}>
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function buildHistoryChartData(array $history): array
    {
        $chronological = array_reverse($history);
        return array_map(static fn(ZapScan $scan) => [
            'date' => $scan->getGeneratedAt()->format('Y-m-d H:i'),
            'high' => $scan->getRiskCountHigh(),
            'medium' => $scan->getRiskCountMedium(),
            'low' => $scan->getRiskCountLow(),
            'informational' => $scan->getRiskCountInformational(),
        ], $chronological);
    }

    /**
     * [Description for categoryLabels]
     * Libellés des catégories OWASP par référentiel — copie des libellés
     * 2017/2021 de OwaspController::owaspLabels(). 2025 omis : ZAP ne tague pas
     * ce référentiel (cf. exploration-owasp-api-security.md).
     *
     * @return array<int, array<int, string>>
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function categoryLabels(): array
    {
        return [
            2017 => [
                1 => "Attaques d'injection",
                2 => "Authentification défaillante",
                3 => "Fuites de données sensibles",
                4 => "Entités externes XML (XXE)",
                5 => "Contrôle d'accès défaillant",
                6 => "Configurations défaillantes",
                7 => "Attaques cross-site scripting (XSS)",
                8 => "Désérialisation sans validation",
                9 => "Composants tiers vulnérables",
                10 => "Journalisation et surveillance insuffisantes",
            ],
            2021 => [
                1 => "Contrôle d'accès défaillant",
                2 => "Défauts cryptographiques",
                3 => "Injections",
                4 => "Conception non sécurisée",
                5 => "Mauvaise configuration de sécurité",
                6 => "Composants vulnérables et obsolètes",
                7 => "Identification et authentification défaillantes",
                8 => "Manque d'intégrité des données et du logiciel",
                9 => "Carences des systèmes de contrôle et de journalisation",
                10 => "Falsification de requête côté serveur (SSRF)",
            ],
        ];
    }
}
