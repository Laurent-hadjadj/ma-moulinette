<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Controller\Suivi;

use App\Controller\Traits\AppUserAware;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use \Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{Historique, Utilisateur, InformationProjet};
use App\Service\ClientService;
use App\Service\CommandRebuildHistorique\{BuildMapHistoryService, SonarAnalysisFetcherService};
use Psr\Log\LoggerInterface;

/**
 * [Description ApiSuiviController]
 */
class ApiSuiviController extends AbstractController
{
    use AppUserAware;

    private static string $europeParis = "Europe/Paris";
    private static string $sonarUrl = "sonar.url";
    private static string $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static string $erreur403 = "Vous devez avoir le rôle SUIVI pour réaliser cette action (Erreur 403).";
    private static string $erreur500 = "L'accès au serveur SonarQube n'est pas possible (Erreur 500).";
    private static string $loggerE403 = "[Suivi-Grant] ⚠️ Vous devez avoir le rôle SUIVI pour réaliser cette action (Erreur 403).";
    private static string $noData = 'Pas de données';

    public function __construct(
        private EntityManagerInterface $em,
        private ClientService $client,
        private Security $security,
        private LoggerInterface $logger,
        private BuildMapHistoryService $buildMap,
        private SonarAnalysisFetcherService $analysisFetcher
    ) {
    }

    /**
     * [Description for listeVersion]
     * On récupère la liste des projets nom + clé pour le sélecteur de projet.
     * http://{url}}/api/liste/version
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 22:35:41 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/liste/v1.0/version', name: 'liste_v1_version', methods: ['POST'])]
    public function listeVersionV1(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/liste/v1.0/version");

        /** On instancie l'entityRepository */
        $informationProjetRepository = $this->em->getRepository(InformationProjet::class);

        $user = $this->appUser();
        $username = $user->getUserIdentifier();

        /** On récupère le job et le type (manuel ou automatique) */
        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'maven_key') || !is_string($data->maven_key)) {
            $this->logger->error("[Suivi-ListeV1] ❌ Requête invalide : clé 'maven_key' manquante ou JSON mal formé.", [
                'utilisateur' => $username,
                'payload' => $data ?? self::$noData
            ]);
            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** On vérifie  */
        $map = ['maven_key' => $data->maven_key];
        $result = $informationProjetRepository->selectInformationProjetVersion($map);
        if ($result['code'] !== 200) {
            $this->logger->error('[Suivi-VersionV1] ❌ Échec de la requête selectInformationProjetVersion.', [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? self::$noData
            ]);
            return new JsonResponse([
                'code' => $result['code'],
                'type' => 'error',
                'message' => 'Une erreur est survenue lors de la récupération des versions du projet.',
                'trace' => $result['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        $liste = [];
        $id = 0;
        /** objet = { id: clé, text: "blablabla" }; */
        foreach ($result['versions'] as $version) {
            $ts = new \DateTime($version['date'], new \DateTimeZone(self::$europeParis));
            $cc = $ts->format("d-m-Y H:i:s");
            $objet = [
                'id' => $id,
                'text' => $version['version'] . " (" . $cc . ")"
            ];
            array_push($liste, $objet);
            $id++;
        }

        return new JsonResponse([
            'code' => 200,
            'liste' => $liste
        ], Response::HTTP_OK);
    }

    /**
     * [Description for listeVersionV2]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 12/07/2026 21:34:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/liste/v2.0/version', name: 'liste_v2_version', methods: ['POST'])]
    public function listeVersionV2(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/liste/v2.0/version");

        $user = $this->appUser();
        $username = $user->getUserIdentifier();

        /** On récupère le job et le type (manuel ou automatique) */
        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'maven_key') || !is_string($data->maven_key)) {
            $this->logger->error("[Suivi-ListeV1] ❌ Requête invalide : clé 'maven_key' manquante ou JSON mal formé.", [
                'utilisateur' => $username,
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** On construit l'URL */
        $tempoUrl = rtrim($this->getParameter(self::$sonarUrl), '/');

        /** Appelle le client HTTP (limité à 100 analyses/page — suffisant pour
         *  la modale Ajouter ; les gros projets utiliseront SonarAnalysisFetcher
         *  via le batch RebuildHistoriqueCommand). */
        $queryParams = ['project' => $data->maven_key, 'p' => 1, 'ps' => 100];
        $result = $this->client->httpSonarQube("$tempoUrl/api/project_analyses/search?" . http_build_query($queryParams));
        if (in_array($result['code'] ?? -1, [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error("[Suivi-ListeV2] ❌ Une erreur lors de l'appel de l'API SonarQube est survenue.", [
                'code' => $result['code'],
                'utilisateur' => $username,
                'url' => "$tempoUrl/api/project_analyses/search?" . http_build_query($queryParams),
                'error' => $result['erreur'] ?? self::$noData
            ]);
            return new JsonResponse([
                'code' => $result['code'],
                'type' => 'error',
                'message' => self::$erreur500,
                'trace' => $result['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        /** On adapte la réponse SonarQube au format attendu par
         *  SonarAnalysisFetcherService::computeVersionCounters (clés
         *  analysisKey/date/version). Le service calcule les compteurs
         *  cumulés version_release/snapshot/autre à la date de chaque
         *  analyse (métrique custom ma-moulinette). */
        $adapted = array_map(static fn (array $a): array => [
            'analysisKey' => $a['key'] ?? null,
            'date' => $a['date'] ?? null,
            'version' => $a['projectVersion'] ?? 'unknown',
            'events' => $a['events'] ?? [],
        ], $result['json']['analyses'] ?? []);
        $adapted = $this->analysisFetcher->computeVersionCounters($adapted);

        /** Tri pour le sélecteur : version desc (natural sort, "1.0.10" > "1.0.9")
         *  puis date desc en tiebreaker.
         *  MODIF 2026-05-06 : retour au tri version d'abord (revert du fix-suivi-modale-tri-date)
         *  car le tri par date mélangeait des numéros de version (1.3.2 puis 0.0.0)
         *  difficiles a lire. La date reste affichée dans le `text` du select2 pour
         *  permettre a l'utilisateur de repérer une éventuelle incoherence date/version. */
        usort($adapted, static function (array $a, array $b): int {
            $cmp = strnatcmp($b['version'] ?? '', $a['version'] ?? '');
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp($b['date'] ?? '', $a['date'] ?? '');
        });

        $liste = [];
        $id = 0;
        /** objet select2 = { id, text, ...metadata cachée } — analyse_key et
         *  les compteurs version_* sont attachés à l'option et récupérés via
         *  $('#liste-version').select2('data')[0] au moment du change. */
        foreach ($adapted as $analysis) {
            $ts = new \DateTime($analysis['date'], new \DateTimeZone(self::$europeParis));
            $cc = $ts->format('d-m-Y H:i:sO');
            $liste[] = [
                'id' => $id,
                'text' => ($analysis['version'] ?? 'unknown') . ' (' . $cc . ')',
                'analyse_key' => $analysis['analysisKey'] ?? null,
                'version_release' => $analysis['version_release'] ?? null,
                'version_snapshot' => $analysis['version_snapshot'] ?? null,
                'version_autre' => $analysis['version_autre'] ?? null,
            ];
            $id++;
        }

        return new JsonResponse([
            'code' => 200,
            'liste' => $liste
        ], Response::HTTP_OK);
    }

    /**
     * [Description for getVersion]
     * On récupère les données disponibles pour une version donnée (maven_key + date) depuis l'historique des mesures SonarQube
     * http://{url}}/api/get/version
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 22:36:17 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/get/version', name: 'get_version', methods: ['POST'])]
    public function getVersion(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/get/version");

        $user = $this->appUser();
        $username = $user->getUserIdentifier();

        /** On récupère la maven_Key */
        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'maven_key') || !is_string($data->maven_key) ||
            !property_exists($data, 'date') || !is_string($data->date)) {
            $this->logger->error("[Suivi-Version] ❌ Requête invalide : clé 'maven_key', 'date' manquante ou JSON mal formé.", [
                'utilisateur' => $username,
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** On a pas besoin d'encoder la date */
        // 28-06-2024 20:48:20 → 2024-06-28T20:48:20+0200
        $date_convert = new \Datetime($data->date);
        $date_format = $date_convert->format('Y-m-d\TH:i:sO');
        $url = rtrim($this->getParameter(self::$sonarUrl), '/');

        /** Catalogue version-aware des métriques (Core SonarQube 8/9 + LTA 10
         *  + LTA 24/26 selon la version configurée). Source unique partagée
         *  avec BatchCollecteMesureController et RebuildHistoriqueCommand. */
        $sonarVersion = (int) trim((string) $this->getParameter('sonar.version'));
        $metricsKeys = (string) $this->buildMap->metricsKey($sonarVersion);
        // dd($metricsKeys);  // 🔍 debug temporaire — décommente pour voir le catalogue version-aware

        /** Un seul appel API au lieu de 6 — search_history accepte une liste
         *  de métriques séparées par virgules et renvoie naturellement les
         *  métriques absentes hors du tableau (pas de fallback nécessaire). */
        $params = [
            'component' => $data->maven_key,
            'metrics' => $metricsKeys,
            'from' => $date_format,
            'to' => $date_format,
        ];
        $apiResult = $this->client->httpSonarQube("$url/api/measures/search_history?" . http_build_query($params));

        if (in_array($apiResult['code'] ?? -1, [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error('[Suivi-Version] ❌ Erreur SonarQube lors de la recherche des métriques.', [
                'code' => $apiResult['code'],
                'erreur' => $apiResult['erreur'] ?? self::$noData,
            ]);
            return new JsonResponse([
                'code' => $apiResult['code'],
                'type' => 'error',
                'message' => "Une erreur est survenue lors de la recherche des métriques dans l'historique du projet.",
                'trace' => $apiResult['erreur'] ?? self::$noData,
            ], Response::HTTP_OK);
        }

        /** On aplatit la réponse en [metric => raw_value]. Une entrée history
         *  peut ne pas avoir de 'value' (métrique non calculée pour cette
         *  analyse) — on skippe silencieusement (régression 2026-05-03). */
        $rawMetrics = [];
        foreach ($apiResult['json']['measures'] ?? [] as $measure) {
            foreach ($measure['history'] ?? [] as $history) {
                if (!array_key_exists('value', $history)) {
                    continue;
                }
                $rawMetrics[$measure['metric']] = $history['value'];
            }
        }

        /** On délègue la reconstruction au service partagé BuildMapHistoryService.
         *  En plus du typage int/float/string, metricsRebuild :
         *   - convertit les ratings 1-5 en lettre A-E (sqale, reliability,
         *     security, security_review),
         *   - calcule complexity_ratio = ncloc/complexity et idem cognitive,
         *   - dérive coverage_rating, duplicated_lines_rating, comment_lines_rating,
         *     complexity_rating, cognitive_complexity_rating selon les seuils.
         *  Cohérent avec BatchCollecteMesureController + RebuildHistoriqueCommand. */
        $analysis = [
            'analysisKey' => null,
            'version' => null,
            'date' => $data->date,
        ];
        $rebuilt = $this->buildMap->metricsRebuild($rawMetrics, $analysis, $data->maven_key, 'measures');

        return new JsonResponse(['code' => 200, 'data' => $rebuilt], Response::HTTP_OK);
    }

    /**
     * [Description for suiviMiseAJour]
     * Enregistre une version reconstituée dans la table historique
     * http://{url}}/api/suivi/mise-a-jour
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 22:37:32 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/suivi/mise-a-jour', name: 'suivi_mise_a_jour', methods: ['PUT'])]
    public function suiviMiseAJour(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/suivi/mise-a-jour");

        /** On instancie l'entityRepository */
        $historiqueRepository = $this->em->getRepository(Historique::class);

        $user = $this->appUser();
        $username = $user->getUserIdentifier();

        /** On décode le body */
        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'maven_key') || !is_string($data->maven_key)) {
            $this->logger->error("[Suivi-Update] ❌ Requête invalide : clé 'maven_key' ou JSON mal formé.", [
                'utilisateur' => $username,
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** On créé objet date */
        $dateEnregistrement = new \DateTimeImmutable('now', new \DateTimeZone(self::$europeParis));

        /** Map = toutes les propriétés du payload JS (déjà aligné SonarQube
         *  via populerMetriques + metricsRebuild) + injection des méta de
         *  collecte. Le repo `insertHistoriqueAjoutProjet` utilise sa
         *  whitelist de colonnes : les champs absents du payload deviennent
         *  null (colonnes nullable), les champs en trop sont ignorés. */
        $map = (array) $data;
        $map['analyse_key'] = $data->analyse_key ?? '-';
        /* MODIF 2026-05-06 : tag 'REBUILD' au lieu de
         * 'COLLECTE' — cette route ajoute une version reconstituée depuis SonarQube
         * search_history, donc avec des indicateurs PARTIELS (pas de comment_lines_rating,
         * complexity_rating, cognitive_complexity_rating, etc.). 'COLLECTE' reste
         * reserve aux vraies collectes a chaud (BatchCollecteMesureController). */
        $map['mode_collecte'] = 'REBUILD';
        $map['utilisateur_collecte'] = $this->appUser()->getCourriel() ?? self::$noData;
        $map['date_enregistrement'] = $dateEnregistrement;

        /** Normalise les valeurs string 'null' / '' / null en vrai null PHP pour
         *  les champs numériques (Postgres refuse 'null' string sur INT/FLOAT)
         *  ET pour les ratings (varchar nullable — on ne veut pas y stocker
         *  le littéral 'null'). populerMetriques côté JS écrit null dans
         *  dataset.* → DOM coerce en string "null" → on le retraduit.
         *  Whitelist limitée aux vraies clés textuelles métier. */
        $stringKeys = ['maven_key', 'analyse_key', 'version', 'date_version',
                        'project_name', 'mode_collecte', 'utilisateur_collecte',
                        'date_enregistrement'];
        foreach ($map as $k => $v) {
            if (in_array($k, $stringKeys, true)) {
                continue;
            }
            if ($v === null || $v === '' || $v === 'null') {
                $map[$k] = null;
            }
        }
        $json = [];
        /** On enregistre */
        $result = $historiqueRepository->insertHistoriqueAjoutProjet($map, $json);

        /** Doublon attendu (unique constraint) : on retourne un warning explicite. */
        if ($result['code'] === 23505) {
            $this->logger->warning('[Suivi-Update] ⚠️ Tentative d\'ajout d\'une version déjà présente dans l\'historique.', [
                'utilisateur' => $username,
                'maven_key' => $data->maven_key,
                'version' => $data->version ?? self::$noData
            ]);
            return new JsonResponse([
                'code' => 23505,
                'type' => 'warning',
                'message' => 'Cette version est déjà présente dans l\'historique.'
            ], Response::HTTP_OK);
        }

        if ($result['code'] != 200) {
            $this->logger->error('[Suivi-Update] ❌ Échec de la requête insertHistoriqueAjoutProjet.', [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => $result['code'],
                'type' => 'error',
                'message' => "Une erreur est survenue lors de la mise à jour des données dans l'historique.",
                'trace' => $result['erreur'] ?? self::$noData
                ], Response::HTTP_OK);
        }

        return new JsonResponse([
            'code' => 200,
            'type' => 'success',
            'message' => 'La version a été ajoutée à l\'historique.'
        ], Response::HTTP_OK);
    }

    /**
     * [Description for suiviVersionListe]
     * récupère la liste des projets nom + clé + favori + reference
     * http://{url}}/api/suivi/liste/version
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 22:38:29 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/suivi/version/liste', name: 'suivi_version_liste', methods: ['POST'])]
    public function suiviVersionListe(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/suivi/version/liste");

        /** On instancie l'entityRepository */
        $historiqueRepository = $this->em->getRepository(Historique::class);

        $user = $this->appUser();
        $username = $user->getUserIdentifier();

        /** On décode le body */
        $data = json_decode($request->getContent());

        if ($data === null || !property_exists($data, 'maven_key') || !is_string($data->maven_key)) {
            $this->logger->error("[Suivi-Liste] ❌ Requête invalide : clé 'maven_key' ou JSON mal formé.", [
                'utilisateur' => $username,
                'payload' => $data ?? self::$noData
            ]);
            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /**  On récupère les versions et la date pour la clé du projet. */
        $map = ['maven_key' => $data->maven_key];
        $result = $historiqueRepository->selectHistoriqueProjetByDate($map);

        if ($result['code'] != 200) {
            $this->logger->error('[Suivi-Update] ❌ Échec de la requête selectHistoriqueProjetByDate.', [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? self::$noData,
                'data' => $map
            ]);

            return new JsonResponse([
                'code' => $result['code'],
                'message' => "Une erreur est survenue lors de la récupération des données du projet.",
                'trace' => $result['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        /** On récupère les préférences de l'utilisateur */
        $preference = $this->appUser()->getPreference();
        /* ? l'utilisateur a activer la gestion des favoris */
        //"$preferenceStatutFavoriVersion=$preference['statut']['favori_version'] ?? false;"
        $preferenceFavoriVersion = $preference['favori_version'] ?? [];
        // Récupérer les versions favorites pour la maven_key
        $listeVersions = self::getFavoriVersions($preferenceFavoriVersion, $data->maven_key);

        // MODIF 2026-06-11 : récupère les versions suivi
        $preferenceSuiviVersion = $preference['suivi_version'] ?? [];
        $listeSuiviVersions = $preferenceSuiviVersion[$data->maven_key] ?? [];

        // Ajouter les clés 'favori' et 'suivi' aux versions correspondantes dans la liste
        foreach ($result['version'] as &$version) {
            if ($version['maven_key'] === $data->maven_key && in_array($version['version'], $listeVersions)) {
                $version['favori'] = true;
            } else {
                $version['favori'] = false;
            }
            $version['suivi'] = in_array($version['version'], $listeSuiviVersions, true);
        }
        unset($version);

        return new JsonResponse([
            'code' => 200,
            'type' => 'info',
            'message' => "La liste des versions a été chargée correctement.",
            'versions' => $result['version'],
            'preference_favori' => $preferenceFavoriVersion,
            'suivi_version_count' => count($listeSuiviVersions),
        ], Response::HTTP_OK);
    }

    /**
     * [Description for getFavoriVersions]
     * Fonction pour récupérer les versions d'une application spécifique
     *
     * @param array $version
     * @param string $app_key
     *
     * @return array<int|string, mixed>
     *
     * Created at: 26/07/2024 11:34:08 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function getFavoriVersions(array $version, string $app_key): array
    {
        foreach ($version as $entry) {
            if (isset($entry[$app_key])) {
                return $entry[$app_key];
            }
        }
        return [];
    }

    /**
     * [Description for suiviVersionFavori]
     * On ajoute ou on supprime la version favorite
     * http://{url}}/api/suivi/version/favori
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 22:39:11 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/suivi/version/favori', name: 'suivi_projet_favori', methods: ['PUT'])]
    public function suiviVersionFavori(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/suivi/version/favori");

        /** On instancie l'entityRepository */
        $utilisateurRepository = $this->em->getRepository(Utilisateur::class);

        /** on décode le body */
        $data = json_decode($request->getContent());

        /** On met à jour le favori et la version favorite */
        $preference = $this->appUser()->getPreference();
        $courriel = $this->appUser()->getCourriel();
        $username = $this->appUser()->getUserIdentifier();

        // 'courriel' n'est PAS requis dans le payload : on le récupère côté serveur via appUser()
        // (sécurité : empêche un client de cibler un autre utilisateur).
        if ($data === null || !property_exists($data, 'maven_key') ||
            !is_string($data->maven_key) ||
            !property_exists($data, 'favori') ||
            !property_exists($data, 'version') ||
            !property_exists($data, 'date_version')) {
            $this->logger->error("[Suivi-favori] ❌ Requête invalide : clé 'maven_key', 'favori', 'version', 'date_version' ou JSON mal formé.", [
                'utilisateur' => $username,
                'payload' => $data ?? self::$noData
            ]);
            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        $map = [
            'favori' => $data->favori,
            'courriel' => $courriel,
            'maven_key' => $data->maven_key,
            'version' => $data->version,
            'date_version' => $data->date_version
        ];

        $result = $utilisateurRepository->updateUtilisateurFavoriVersion($preference, $map);
        if ($result['code'] != 200) {

            $this->logger->error('[Suivi-Update] ❌ Échec de la requête updateUtilisateurFavoriVersion.', [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? self::$noData,
                'data' => $map,
                'preference' => $preference
            ]);

            return new JsonResponse([
                'code' => $result['code'],
                'message' =>  "Une erreur est survenue lors de la mise à jour du favori pour cette version.",
                'trace' => $result['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        /** Tout c'est bien passé */
        return new JsonResponse(['code' => 200], Response::HTTP_OK);
    }

    /**
     * [Description for suiviVersionReference]
     * On ajoute ou on supprime la version de reference
     * http://{url}}/api/suivi/version/reference
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 22:40:34 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/suivi/version/reference', name: 'suivi_version_reference', methods: ['PUT'])]
    public function suiviVersionReference(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/suivi/version/reference");

        /** On instancie l'entityRepository */
        $historiqueRepository = $this->em->getRepository(Historique::class);

        $username = $this->appUser()->getUserIdentifier();

        /** On décode le body */
        $data = json_decode($request->getContent());
        if (
            $data === null ||
            !property_exists($data, 'maven_key') ||
            !property_exists($data, 'initial') ||
            !property_exists($data, 'version') ||
            !property_exists($data, 'date_version')
        ) {
            $this->logger->error("[Suivi-Reference] ❌ Requête invalide : clé 'maven_key', 'initial', 'version', 'date_version' ou JSON mal formé.", [
                'utilisateur' => $username,
                'payload' => $data ?? self::$noData
            ]);
            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** si on a pas le role SUIVI on ne fait rien. */
        if (!$this->security->isGranted('ROLE_SUIVI')) {
            $this->logger->error(self::$loggerE403, [
                'utilisateur' => $username,
            ]);
            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403
            ], Response::HTTP_OK);
        }

        /** On créé la map pour la requête de mise à jour */
        $map = ['initial' => $data->initial, 'maven_key' => $data->maven_key, 'version' => $data->version, 'date_version' => $data->date_version];
        $result = $historiqueRepository->updateHistoriqueReference($map);
        if ($result['code'] != 200) {
            return new JsonResponse([
                'code' => $result['code'],
                'message' => "Une erreur est survenue lors de la mise à jour de la version de référence.",
                'trace' => $result['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        /** Tout c'est bien passé */
        return new JsonResponse(['code' => 200], Response::HTTP_OK);
    }

    /**
     * [Description for suiviVersionSuivi]
     * Ajoute ou supprime une version de la liste de suivi personnalisée.
     * Max 15 versions par projet. La version de référence (initial=true) reste toujours affichée.
     * http://{url}}/api/suivi/version/suivi
     *
     * @param Request $request
     * @return JsonResponse
     *
     * Created at: 2026-06-11 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/suivi/version/suivi', name: 'suivi_version_suivi', methods: ['POST'])]
    public function suiviVersionSuivi(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/suivi/version/suivi");

        /** On instancie l'entityRepository */
        $utilisateurRepository = $this->em->getRepository(Utilisateur::class);

        $preference = $this->appUser()->getPreference();
        $courriel   = $this->appUser()->getCourriel();
        $username   = $this->appUser()->getUserIdentifier();

        $data = json_decode($request->getContent());

        if ($data === null ||
            !property_exists($data, 'maven_key') || !is_string($data->maven_key) ||
            !property_exists($data, 'version')   || !is_string($data->version)   ||
            !property_exists($data, 'suivi')
        ) {
            $this->logger->error("[Suivi-Suivi] ❌ Requête invalide : clé 'maven_key', 'version', 'suivi' manquante ou JSON mal formé.", [
                'utilisateur' => $username,
                'payload' => $data ?? self::$noData
            ]);
            return new JsonResponse([
                'code' => 400, 'type' => 'error', 'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        $map = [
            'courriel'  => $courriel,
            'maven_key' => $data->maven_key,
            'version'   => $data->version,
            'suivi'     => (int) $data->suivi,
        ];

        $result = $utilisateurRepository->updateUtilisateurSuiviVersion($preference, $map);

        if ($result['code'] !== 200) {
            $this->logger->error('[Suivi-Suivi] ❌ Échec de la requête updateUtilisateurSuiviVersion.', [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? self::$noData,
                'data' => $map,
            ]);
            return new JsonResponse([
                'code' => $result['code'], 'type' => 'error',
                'message' => $result['erreur'] ?? "Une erreur est survenue lors de la mise à jour du suivi.",
            ], Response::HTTP_OK);
        }

        $count = count($result['suivi_version'][$data->maven_key] ?? []);
        return new JsonResponse([
            'code' => 200, 'type' => 'success',
            'message' => "Mise à jour du suivi effectuée.",
            'suivi_version_count' => $count,
        ], Response::HTTP_OK);
    }

    /**
     * [Description for suiviVersionPoubelle]
     * On supprime la version de historique
     * On fait PUT pour un DELETE. (i.e on bloque la méthode DELETE)
     * http://{url}}/api/suivi/version/poubelle
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 22:41:09 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/suivi/version/poubelle', name: 'suivi_version_poubelle', methods: ['PUT'])]
    public function suiviVersionPoubelle(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/suivi/version/poubelle");

        /** On instancie l'entityRepository */
        $historiqueRepository = $this->em->getRepository(Historique::class);
        $utilisateurRepository = $this->em->getRepository(Utilisateur::class);

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $this->appUser()->getPreference();
        $username = $this->appUser()->getUserIdentifier();
        $courriel = $this->appUser()->getCourriel();

        /** on décode le body */
        $data = json_decode($request->getContent());

        /** On regarde si $data est valide */
        if (
            $data === null ||
            !property_exists($data, 'maven_key') ||
            !property_exists($data, 'version') ||
            !property_exists($data, 'date_version')
        ) {
            $this->logger->error("[Suivi-Reference] ❌ Requête invalide : clé 'maven_key', 'version', 'date_version' ou JSON mal formé.", [
                'utilisateur' => $username,
                'payload' => $data ?? self::$noData
            ]);
            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** si on a pas le role SUIVI on ne fait rien. */
        if (!$this->security->isGranted('ROLE_SUIVI')) {
            $this->logger->error(self::$loggerE403, [
                'utilisateur' => $username,
            ]);
            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403
            ], Response::HTTP_OK);
        }

        /** On supprime la version du projet */
        $map = [
                    'maven_key' => $data->maven_key,
                    'version' => $data->version,
                    'date_version' => $data->date_version
                ];
        $result = $historiqueRepository->deleteHistoriqueProjet($map);
        if ($result['code'] != 200) {
            $this->logger->error('[Suivi-Update] ❌ Échec de la requête deleteHistoriqueProjet.', [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? self::$noData,
                'data' => $map,
            ]);
            return new JsonResponse([
                'code' => $result['code'],
                'message' => "Une erreur est survenue lors de la suppression du projet",
                'trace' => $result['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        /**
         * On regarde si le projet est un favori.
         * favori_version est une liste d'entrées [maven_key => [versions, ...]].
         */
        $favoriVersion = $preference['favori_version'] ?? [];
        $estFavori = false;
        if (is_array($favoriVersion)) {
            foreach ($favoriVersion as $entry) {
                if (is_array($entry) && array_key_exists($data->maven_key, $entry)) {
                    $estFavori = true;
                    break;
                }
            }
        }

        if ($estFavori) {
            $map = [
                    'favori' => 0,
                    'courriel' => $courriel,
                    'maven_key' => $data->maven_key,
                    'version' => $data->version,
                    'date_version' => $data->date_version
            ];

            $result = $utilisateurRepository->updateUtilisateurFavoriVersion($preference, $map);
            if ($result['code'] != 200) {
                $this->logger->error('[Suivi-Update] ❌ Échec de la requête updateUtilisateurFavoriVersion.', [
                    'code' => $result['code'],
                    'erreur' => $result['erreur'] ?? self::$noData,
                    'data' => $map,
                    'preference' => $preference
                ]);
                return new JsonResponse([
                'code' => $result['code'],
                'message' => "Une erreur est survenue lors de la mise à jour des favoris",
                'trace' => $result['erreur'] ?? self::$noData
                ], Response::HTTP_OK);
            }
            // On rafraîchit $preference avec la version mise à jour (suivi_version préservée)
            $preference = $result['preference'] ?? $preference;
        }

        // MODIF 2026-06-11 : nettoyer suivi_version si la version supprimée y était.
        $suiviVersion = $preference['suivi_version'] ?? [];
        $estSuivi = is_array($suiviVersion)
            && isset($suiviVersion[$data->maven_key])
            && in_array($data->version, (array) $suiviVersion[$data->maven_key], true);

        if ($estSuivi) {
            $mapSuivi = [
                'courriel'  => $courriel,
                'maven_key' => $data->maven_key,
                'version'   => $data->version,
                'suivi'     => 0,
            ];
            $resultSuivi = $utilisateurRepository->updateUtilisateurSuiviVersion($preference, $mapSuivi);
            if ($resultSuivi['code'] !== 200) {
                $this->logger->error('[Suivi-Update] ❌ Échec de la requête updateUtilisateurSuiviVersion lors de la suppression.', [
                    'code' => $resultSuivi['code'],
                    'erreur' => $resultSuivi['erreur'] ?? self::$noData,
                ]);
            }
        }

        /** Tout c'est bien passé */
        return new JsonResponse([
            'code' => 200,
            'type' => 'success',
            'message' => 'Le projet a également été supprimé de vos préférences.'
        ], Response::HTTP_OK);
    }
}
