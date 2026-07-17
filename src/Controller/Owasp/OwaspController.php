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

namespace App\Controller\Owasp;

use App\Controller\Traits\{AppUserAware, ProjetPerimetreGuard};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\{Request, Response, ResponseHeaderBag};
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\{OwaspTop10, Owasp, HotspotOwasp, HotspotDetails, Historique};
use App\Repository\DcScanRepository;
use App\Service\{PdfExportService};
use App\Service\UserAgent\UserAgentTrackingFacade;

/**
 * [Description OwaspController]
 */
class OwaspController extends AbstractController
{
    use AppUserAware;
    use ProjetPerimetreGuard;

    private static string $page = "owasp/index.html.twig";
    private static string $erreur404 = "⚠️ Les informations concernant les référentiels OWASP n'ont pas été trouvés.";
    private static string $erreur400 = '❌ La requête est incorrecte (Erreur 400).';
    private static string $noData = 'Pas de données';

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    /**
     * [Description for __construct]
     *
     * Created at: 13/02/2023, 08:57:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        ParameterBagInterface $params,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private PdfExportService $pdfExportService,
        private DcScanRepository $dcScanRepository,
        private UserAgentTrackingFacade $tracking)
    {
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
     * @return array<int|string, mixed>
     *
     * Created at: 30/10/2024 08:21:04 (Europe/Paris)
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
     * [Description for addFlashAndRender]
     *
     * @param string $type
     * @param string $message
     * @param string|null $trace
     * @param array $render
     *
     * @return Response
     *
     * Created at: 10/05/2026 11:41:25 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function addFlashAndRender(string $type, string $message, string|null $trace, array $render): Response
    {
        $this->logger->info("[OWASP] ℹ️ Ajout d’un message flash de type '{$type}'");
        $this->logger->debug("Contenu du message flash", [
            'message' => $message,
            'trace' => $trace ?? 'Pas de traces',
            'render_keys' => array_keys($render),
        ]);

        $this->addFlash('notice', [
            'type' => $type,
            'message' => $message,
            'trace' => $trace ?? null,
        ]);

        return $this->render(self::$page, $render);
    }

    /**
     * [Description for decodeToken]
     *
     * @param string $token
     *
     * @return string|null
     *
     * Created at: 10/05/2026 10:55:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function decodeToken(string $token): ?string
    {
        //token=BGR2ZQL5ZQLjA3kzpv5gLF1go3IfnJ5yqUEyBzkyYJAbLKD=
        //1 - b64=OTE2MDY5MDYwN3xmci5tYS1tb3VsaW5ldHRlOmxlLWNoYXQ=
        //2 - rot13=BGR2ZQL5ZQLjA3kzpv5gLF1go3IfnJ5yqUEyBzkyYJAbLKD=
        $this->logger->debug("[OWASP] 🛠️ Tentative de décodage du token", ['token_brut' => $token]);

        $string = str_rot13($token);
        $decoded = base64_decode($string, true); // `true` => return false si invalide
        if ($decoded === false) {
            $this->logger->warning("[OWASP] ⚠️ Échec du décodage base64 du token", ['token_rot13' => $string]);
            return null;
        }

        $parts = preg_split("/[|]+/", $decoded);

        if (count($parts) !== 2) {
            $this->logger->warning("[OWASP] ⚠️ Format de token invalide après décodage", ['decoded' => $decoded]);
            return null;
        }

        /* MODIF 2026-07-18 : ne plus forcer la casse en minuscules — une
         * clé Maven comme "fr.ma-moulinette:Ma-Moulinette" ne matchait
         * plus jamais l'entrée `liste_projet`/`historique` (comparaison stricte
         * dans ProjetPerimetreGuard/selectOwaspVersion), faussement rejetée en
         * 406. Aligné sur SuiviController/CosuiController::decodeToken(), qui
         * préservent déjà la casse. */
        $result = $parts[1];
        $this->logger->info("[OWASP] ℹ️ Token décodé avec succès", ['valeur_extraite' => $result]);

        return $result;
    }

    /**
     * [Description for index]
     * Page web OWASP
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:14:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/owasp', name: 'owasp')]
    public function index(Request $request): Response
    {
        $this->tracking->track('OWASP');

        /** On charge le template du render */
        $render = $this->genericRender();

        /* MODIF 2026-07-18 : le template référence sonar_version/application/
         * application_version/has_dc_scan sans garde `is defined` (breadcrumb,
         * activation des boutons de référentiel, bouton Dependency-Check). Ces
         * clés n'étaient historiquement affectées qu'en toute fin de chemin
         * heureux — tout retour anticipé (token, périmètre, échec de lecture
         * d'un référentiel) rendait la page avec un render incomplet et
         * plantait Twig ("Variable ... does not exist"). Valeurs par défaut
         * posées une fois pour toutes, écrasées plus bas si le chemin
         * est atteint. */
        $render['sonar_version'] = (int) $this->getParameter('sonar.version');
        $render['application'] = null;
        $render['application_group'] = null;
        $render['application_version'] = null;
        $render['has_dc_scan'] = false;

        $token = $request->query->get('token');
        if (empty($token)) {
            $this->logger->warning('[OWASP] ⚠️ Token manquant dans la requête');
            return $this->addFlashAndRender('error', self::$erreur400, 'token', $render);
        }

        $maven_key = $this->decodeToken($token);
        if (null === $maven_key) {
            $this->logger->error('[OWASP] ❌ Échec du décodage du token.');
            return $this->addFlashAndRender('error', self::$erreur400, 'Problème de décodage du token.', $render);
        }

        /* MODIF 2026-07-17 : ajout du filtrage par groupe fonctionnel, aligné sur
         * SuiviController::suivi()/CosuiController::projetCosui() — jusqu'ici seul
         * le pare-feu (ROLE_UTILISATEUR) protégeait cette page, sans vérification
         * de périmètre. */
        $perimetre = $this->verifierPerimetreProjet($maven_key, '[OWASP]');
        if ($perimetre['code'] !== 200) {
            $message = $perimetre['message']
                ?? "Une erreur est survenue lors de la vérification du périmètre (Erreur {$perimetre['code']}).";
            return $this->addFlashAndRender('warning', $message, null, $render);
        }

        /** On instancie l'entityRepos */
        $owaspTop10Repos = $this->em->getRepository(OwaspTop10::class);
        $owaspRepos = $this->em->getRepository(Owasp::class);

        /** On récupère les informations du projet de la table historique */
        $map = ['referential_version' => 2017];
        $owasp_2017 = $owaspTop10Repos->selectOwaspTop10Referential($map);

        if ($owasp_2017['code'] != 200) {
            $this->logger->error('[Owasp] ❌ Échec selectOwaspTop10Referential 2017.', [
                'code' => $owasp_2017['code'],
                'erreur' => $owasp_2017['erreur'] ?? self::$noData
            ]);
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => '❌' . ($owasp_2017['erreur'] ?? self::$noData)
            ]);
            return $this->render(self::$page, $render);
        }

        $map = ['referential_version' => 2021];
        $owasp_2021 = $owaspTop10Repos->selectOwaspTop10Referential($map);

        if ($owasp_2021['code'] != 200) {
            $this->logger->error('[Owasp] ❌ Échec selectOwaspTop10Referential 2021.', [
                'code' => $owasp_2021['code'],
                'erreur' => $owasp_2021['erreur'] ?? self::$noData
            ]);
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => '❌' . ($owasp_2021['erreur'] ?? self::$noData)
            ]);
            return $this->render(self::$page, $render);
        }

        $map = ['referential_version' => 2025];
        $owasp_2025 = $owaspTop10Repos->selectOwaspTop10Referential($map);

        if ($owasp_2025['code'] != 200) {
            $this->logger->error('[Owasp] ❌ Échec selectOwaspTop10Referential 2025.', [
                'code' => $owasp_2025['code'],
                'erreur' => $owasp_2025['erreur'] ?? self::$noData
            ]);
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => '❌' . ($owasp_2025['erreur'] ?? self::$noData)
            ]);
            return $this->render(self::$page, $render);
        }

        if (count($owasp_2017['liste']) === 0 && count($owasp_2021['liste']) === 0 && count($owasp_2025['liste']) === 0) {
            $this->logger->warning('[Owasp] ⚠️ Référentiels OWASP 2017, 2021 et 2025 vides.');
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => self::$erreur404
            ]);
        }

        $breadCrumb = $owaspRepos->selectOwaspVersion($maven_key);
        if (($breadCrumb['code'] ?? 500) !== 200) {
            $this->logger->error('[OWASP] ❌ Échec de la requête selectOwaspVersion.', [
                'code'   => $breadCrumb['code'] ?? 500,
                'erreur' => $breadCrumb['erreur'] ?? '',
            ]);
            $this->addFlash('notice', [
                'type'    => 'error',
                'message' => sprintf(
                    'La récupération de la version du projet a échoué (Erreur %s). %s',
                    $breadCrumb['code'] ?? 500,
                    $breadCrumb['erreur'] ?? ''
                ),
            ]);
        }

        /* MODIF 2026-05-10 : split robuste de la maven_key
         * (group:artifact). On expose `application_group` en plus pour que
         * le template puisse construire le lien dc_projet. Garde-fou null :
         * `breadCrumb['application']` peut être null si selectOwaspVersion
         * a échoué (code != 200) ou n'a trouve aucune ligne (no-data). */
        $mavenKeyComplete = $breadCrumb['application'] ?? null;
        $parts = is_string($mavenKeyComplete) ? explode(':', $mavenKeyComplete) : [];

        $render['serveur'] = $this->getParameter('sonar.url');
        $render['owasp_2017'] = $owasp_2017;
        $render['owasp_2021'] = $owasp_2021;
        $render['owasp_2025'] = $owasp_2025;
        $render['application_group']   = $parts[0] ?? null;
        $render['application']         = $parts[1] ?? null;
        $render['application_version'] = $breadCrumb['version'] ?? null;

        /* MODIF 2026-05-11 : le bouton DC est totalement
         * découplé du breadcrumb OWASP Sonar (qui peut être vide pour des projets
         * scannes DC mais sans analyse OWASP récente). On cherche le dernier scan
         * DC pour le maven_key decode du token et on expose `dc_link_*` depuis ce
         * scan (toutes versions confondues). Le bouton apparaît dès qu'un scan DC
         * existe, peu importe l'état de la table `owasp`. */
        $render['has_dc_scan']      = false;
        $render['dc_link_group']    = null;
        $render['dc_link_artifact'] = null;
        $render['dc_link_version']  = null;
        $latestDcScan = $this->dcScanRepository->findLatestByMavenKey($maven_key);
        if ($latestDcScan !== null) {
            $render['has_dc_scan']      = true;
            $render['dc_link_group']    = $latestDcScan->getProjectGroup();
            $render['dc_link_artifact'] = $latestDcScan->getProjectArtifact();
            $render['dc_link_version']  = $latestDcScan->getProjectVersion();
        }
        return $this->render(self::$page, $render);
    }

    /**
     * [Description for details]
     *
     * @param int $id
     *
     * @return Response
     *
     * Created at: 21/11/2024 20:16:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/owasp/detail/{id}', name: 'owasp_detail', condition: "params['id'] < 31", methods: ['GET', 'HEAD'])]
    public function details(int $id): Response
    {
        $this->tracking->track('OWASP_DETAILS');

        /** On charge le template du render */
        $render = $this->genericRender();

        /** On instancie l'entityRepository */
        $owaspTop10Repos = $this->em->getRepository(OwaspTop10::class);

        /** On récupère les informations du projet de la table historique */
        $map = ['menace' => $id];
        $liste = $owaspTop10Repos->selectOwaspTop10Details($map);
        if ($liste['code'] != 200) {
            $this->logger->error('[Owasp] ❌ Échec selectOwaspTop10Details.', [
                'code' => $liste['code'],
                'erreur' => $liste['erreur'] ?? self::$noData,
                'menace' => $id
            ]);
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => '❌' . ($liste['erreur'] ?? self::$noData)
            ]);
            return $this->render(self::$page, $render);
        }
        $render['menace'] = $id;
        $render['owasp'] = $liste['details'][0];
        return $this->render('owasp/detail.html.twig', $render);
    }

    /**
     * [Description for owaspLabels]
     * Libellés des catégories OWASP par référentiel.
     *
     * MODIF 2026-05-15 : clés du référentiel passées de string
     * `'2017'` / `'2021'` à int `2017` / `2021`. PHP convertit automatiquement
     * les clés string numériques en int → l'array était de facto `array<int, …>`
     * mais le @return déclarait `array<string, …>` + ligne 536 accédait via
     * string `['2021']`, déclenchant offsetAccess.notFound sous PHPStan.
     * Le code fonctionnait en runtime (PHP cast l'accès string→int) mais
     * l'incohérence trompait l'analyse statique.
     *
     * @return array<int, array<int, string>>
     *
     * Created at: 03/05/2026 16:57:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function owaspLabels(): array
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
            2025 => [
                1 => "Contrôle d’accès défaillant",
                2 => "Mauvaise configuration de sécurité",
                3 => "Défaillances de la chaîne d’approvisionnement des logiciels",
                4 => "Défaillances cryptographiques",
                5 => "Injection",
                6 => "Conception non sécurisée",
                7 => "Défauts d’authentification",
                8 => "Défauts d’intégrité des logiciels ou des données",
                9 => "Défaillances en matière de journalisation et d’alerte",
                10 => "Mauvaise gestion des conditions exceptionnelles",
            ],
        ];
    }

    /**
     * [Description for rapportPdf]
     * Export PDF du rapport OWASP : information projet, synthèse vulnérabilités
     * + hotspots, liste détaillée des menaces, graphique de répartition par
     * catégorie A1..A10. Agrège côté serveur via les repos Owasp / HotspotOwasp
     * / HotspotDetails (mêmes que les endpoints API utilisés par la page web)
     * puis délègue le rendu PDF à PdfExportService::generateOwaspPdf.
     *
     * @param Request $request
     *
     * Created at: 2026-05-03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/owasp/rapport/pdf', name: 'owasp_rapport_pdf', methods: ['GET'])]
    public function rapportPdf(Request $request): Response
    {
        $mavenKey = (string) $request->query->get('maven_key', '');
        /** Cast int : la colonne `referential_owasp` est INTEGER côté DB.
         *  Postgres accepte le cast string→int silencieusement mais on est
         *  explicite pour éviter toute ambiguïté sur la comparaison du WHERE. */
        $referentialOwasp = (int) ($request->query->get('referential_owasp', 2021));
        if (!in_array($referentialOwasp, [2017, 2021], true)) {
            $referentialOwasp = 2021;
        }
        $download = (bool) $request->query->get('download', false);

        if ($mavenKey === '') {
            throw $this->createNotFoundException('Clé Maven manquante.');
        }

        /* MODIF 2026-07-17 : le rapport PDF ne vérifiait aucune appartenance au
         * groupe fonctionnel de l'utilisateur connecté, contrairement à la page
         * web /owasp — un utilisateur connaissant la clé Maven exacte d'un projet
         * hors de son périmètre pouvait en générer le PDF. Alignement sur
         * SuiviController::rapportPdf(). */
        $perimetre = $this->verifierPerimetreProjet($mavenKey, '[OWASP]');
        if ($perimetre['code'] !== 200) {
            throw $this->createNotFoundException($perimetre['message'] ?? self::$erreur404);
        }

        /** Repos */
        $owaspRepo = $this->em->getRepository(Owasp::class);
        $hotspotOwaspRepo = $this->em->getRepository(HotspotOwasp::class);
        $hotspotDetailsRepo = $this->em->getRepository(HotspotDetails::class);

        /** Vulnérabilités OWASP par catégorie + sévérité (mêmes données que peintureOwaspListe) */
        $owaspResult = $owaspRepo->selectOwaspOrderByDateEnregistrement([
            'maven_key' => $mavenKey,
            'referential_owasp' => $referentialOwasp,
        ]);

        $vuln = ['total' => 0, 'blocker' => 0, 'critical' => 0, 'major' => 0, 'minor' => 0];
        $version = null;
        $dateVersion = null;
        $categoriesFaille = array_fill(1, 10, 0);
        /** Severity breakdown par catégorie pour calculer le rating A-E à la
         *  manière de la page (cf. JS remplissageOwaspInfo) :
         *   blocker > 1 → E ; critical > 1 → D ; major > 1 → C ;
         *   minor > 1 → B ; sinon (tout 0) → A. */
        $sevBreakdown = array_fill(1, 10, ['blocker' => 0, 'critical' => 0, 'major' => 0, 'minor' => 0]);

        if ($owaspResult['code'] === 200 && !empty($owaspResult['liste'])) {
            $ligne = $owaspResult['liste'][0];
            $version = $ligne['version'] ?? null;
            $dateVersion = $ligne['date_version'] ?? null;
            for ($i = 1; $i <= 10; $i++) {
                $categoriesFaille[$i] = (int) ($ligne["a{$i}"] ?? 0);
                $sevBreakdown[$i] = [
                    'blocker'  => (int) ($ligne["a{$i}_blocker"] ?? 0),
                    'critical' => (int) ($ligne["a{$i}_critical"] ?? 0),
                    'major'    => (int) ($ligne["a{$i}_major"] ?? 0),
                    'minor'    => (int) ($ligne["a{$i}_minor"] ?? 0),
                ];
                $vuln['total']    += $categoriesFaille[$i];
                $vuln['blocker']  += $sevBreakdown[$i]['blocker'];
                $vuln['critical'] += $sevBreakdown[$i]['critical'];
                $vuln['major']    += $sevBreakdown[$i]['major'];
                $vuln['minor']    += $sevBreakdown[$i]['minor'];
            }
        }

        /** Fallback : si Owasp n'a pas de ligne pour le couple (maven_key,
         *  referential_owasp), on récupère version + date_version depuis la
         *  dernière entrée Historique. Couvre le cas d'un projet qui n'a que
         *  des données 2017 mais dont le PDF est demandé en 2021 (ou inverse). */
        if ($version === null || $dateVersion === null) {
            $historiqueRepo = $this->em->getRepository(Historique::class);
            $last = $historiqueRepo->selectHistoriqueProjetLast(['maven_key' => $mavenKey]);
            if (($last['code'] ?? null) === 200 && !empty($last['infos'])) {
                $version ??= $last['infos'][0]['version'] ?? null;
                $dateVersion ??= $last['infos'][0]['date_version'] ?? null;
            }
        }

        /** Hotspots : reviewed/to_review/probability + per-catégorie */
        $reviewed = $hotspotOwaspRepo->countHotspotOwaspStatus(['maven_key' => $mavenKey, 'status' => 'REVIEWED']);
        $toReview = $hotspotOwaspRepo->countHotspotOwaspStatus(['maven_key' => $mavenKey, 'status' => 'TO_REVIEW']);
        $probability = $hotspotOwaspRepo->countHotspotOwaspProbability(['maven_key' => $mavenKey]);
        $menaces = $hotspotOwaspRepo->countHotspotOwaspMenaces(['maven_key' => $mavenKey]);

        $hotspots = [
            'reviewed'  => (int) ($reviewed['request'][0]['nombre'] ?? 0),
            'to_review' => (int) ($toReview['request'][0]['nombre'] ?? 0),
            'high'      => 0,
            'medium'    => 0,
            'low'       => 0,
            'note'      => '--',
            'taux'      => 0.0,
        ];
        $hotspots['total'] = $hotspots['reviewed'] + $hotspots['to_review'];

        if (($probability['code'] ?? null) === 200) {
            foreach ($probability['nombre'] ?? [] as $row) {
                $level = strtolower((string) ($row['probability'] ?? ''));
                if (isset($hotspots[$level])) {
                    $hotspots[$level] = (int) ($row['total'] ?? 0);
                }
            }
        }

        /** Note hotspot global = note A-E selon le taux de review.
         *  Reproduit la logique JS remplissageHotspotInfo + calculNoteHotspot.
         *  taux = 1 - (toReview / total) = pourcentage examiné.
         *  Cas spéciaux affichés en `--` côté UI (pas de hotspot ou aucun review). */
        if ($hotspots['total'] > 0) {
            $hotspots['taux'] = 1 - ($hotspots['to_review'] / $hotspots['total']);
            $hotspots['note'] = $hotspots['taux'] > 0
                ? self::computeHotspotNoteFromTaux($hotspots['taux'])
                : '--';
        }

        $categoriesHotspot = array_fill(1, 10, 0);
        if (($menaces['code'] ?? null) === 200) {
            foreach ($menaces['menaces'] ?? [] as $row) {
                $idx = (int) substr((string) ($row['menace'] ?? 'A0'), 1);
                if ($idx >= 1 && $idx <= 10) {
                    $categoriesHotspot[$idx] = (int) ($row['total'] ?? 0);
                }
            }
        }

        /** Liste détaillée des menaces + répartition module calculée à partir des flags.
         *  Colonnes hotspot_details : rule_key (clé technique SonarQube),
         *  rule_name (libellé), file_path (chemin complet du fichier), line. */
        $detailsResult = $hotspotDetailsRepo->selectHotspotDetailsByStatus(['maven_key' => $mavenKey]);
        $repartition = ['frontend' => 0, 'backend' => 0, 'autre' => 0, 'inconnu' => 0];
        $menacesListe = [];
        if (($detailsResult['code'] ?? null) === 200) {
            foreach ($detailsResult['menaces'] ?? [] as $row) {
                $repartition['frontend'] += (int) ($row['frontend'] ?? 0);
                $repartition['backend']  += (int) ($row['backend']  ?? 0);
                $repartition['autre']    += (int) ($row['autre']    ?? 0);
                $repartition['inconnu']  += (int) ($row['inconnu']  ?? 0);
                $menacesListe[] = [
                    'rule'      => $row['rule_key'] ?? $row['rule_name'] ?? '—',
                    'severity'  => $row['severity'] ?? '',
                    'component' => $row['file_path'] ?? $row['file_name'] ?? '—',
                    'ligne'     => $row['line'] ?? '—',
                    'message'   => $row['message'] ?? '—',
                    'status'    => $row['status'] ?? '—',
                ];
            }
        }

        /** Construction des catégories A1..A10 avec libellés selon le référentiel
         *  + ratings A-E vulnérabilité (severity breakdown) et hotspot (taux
         *  = 1 - share_de_la_categorie). Reproduit la logique JS de la page
         *  pour cohérence d'affichage. */
        $labels = self::owaspLabels()[$referentialOwasp] ?? self::owaspLabels()[2021];
        $totalHotspots = array_sum($categoriesHotspot);
        $categories = [];
        for ($i = 1; $i <= 10; $i++) {
            $categories[] = [
                'id'             => $i,
                'label'          => $labels[$i] ?? "A{$i}",
                'faille'         => $categoriesFaille[$i],
                'faille_rating'  => self::computeFailleRating($sevBreakdown[$i]),
                'hotspot'        => $categoriesHotspot[$i],
                'hotspot_rating' => self::computeHotspotRating($categoriesHotspot[$i], $totalHotspots),
                'hotspot_taux'   => $totalHotspots > 0 ? (1 - ($categoriesHotspot[$i] / $totalHotspots)) : 1,
            ];
        }

        /** Assemblage final pour le service */
        $rapport = [
            'maven_key'         => $mavenKey,
            'project_name'      => self::extractProjectName($mavenKey),
            'version'           => $version,
            'date_version'      => $dateVersion,
            'referential_owasp' => $referentialOwasp,
            'vulnerabilities'   => $vuln,
            'hotspots'          => $hotspots,
            'repartition'       => $repartition,
            'categories'        => $categories,
            'menaces'           => $menacesListe,
        ];

        $pdfContent = $this->pdfExportService->generateOwaspPdf($rapport, 'Document Interne');

        $disposition = $download ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE;
        $filename = sprintf('rapport_owasp_%s_%s.pdf',
            preg_replace('/[^a-z0-9_-]/i', '_', $mavenKey),
            date('Y-m-d_H-i')
        );

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename=' . $filename,
        ]);
    }

    /**
     * [Description for extractProjectName]
     * Dernier segment de la maven_key (ex: "fr.acme:app" → "app").
     *
     * @param string $mavenKey
     *
     * @return string
     *
     * Created at: 12/07/2026 09:17:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function extractProjectName(string $mavenKey): string
    {
        $parts = explode(':', $mavenKey);
        return end($parts) ?: $mavenKey;
    }

    /**
     * [Description for computeFailleRating]
     * Rating A-E d'une catégorie OWASP basé sur la severity breakdown.
     * Reproduit la logique du JS remplissageOwaspInfo (seuil > 1 par sévérité).
     *
     * @param array $sev
     *
     * @return string
     *
     * Created at: 12/07/2026 09:19:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function computeFailleRating(array $sev): string
    {
        if ($sev['blocker'] > 1) {
            return 'E';
        }
        if ($sev['critical'] > 1) {
            return 'D';
        }
        if ($sev['major'] > 1) {
            return 'C';
        }
        if ($sev['minor'] > 1) {
            return 'B';
        }
        return 'A';
    }

    /**
     * [Description for computeHotspotRating]
     * Rating A-E hotspot d'une catégorie : taux = 1 - (count_categorie / total).
     * Plus le taux est proche de 1, mieux c'est (cf. JS calculNoteHotspot).
     *
     * @param int $count
     * @param int $total
     *
     * @return string
     *
     * Created at: 12/07/2026 09:19:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function computeHotspotRating(int $count, int $total): string
    {
        if ($total === 0) {
            return 'A';
        }
        $taux = 1 - ($count / $total);
        return self::computeHotspotNoteFromTaux($taux);
    }

    /**
     * [Description for computeHotspotNoteFromTaux]
     * Conversion taux → lettre A-E (mêmes seuils que JS calculNoteHotspot).
     *
     * @param float $taux
     *
     * @return string
     *
     * Created at: 12/07/2026 09:18:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function computeHotspotNoteFromTaux(float $taux): string
    {
        if ($taux > 0.79) {
            return 'A';
        }
        if ($taux > 0.71) {
            return 'B';
        }
        if ($taux > 0.51) {
            return 'C';
        }
        if ($taux > 0.31) {
            return 'D';
        }
        return 'E';
    }
}
