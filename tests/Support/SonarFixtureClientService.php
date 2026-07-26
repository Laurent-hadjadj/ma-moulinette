<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Service\ClientService;

/**
 * Double de test qui remplace {@see ClientService::httpSonarQube()} par une lecture
 * de fixtures JSON capturees depuis un SonarQube reel (projet `tetris:TetrisGame`).
 *
 * Active uniquement dans l'env `test` via `config/services_test.yaml`.
 *
 * Les fixtures sont dans `tests/fixtures/sonarqube/` et ont ete generees par
 * `bin/e2e/capture-sonar-fixtures.ps1`. Pour les regenerer (ex: SonarQube monte de
 * version, l'API a evolue), relancer le script.
 *
 * Convention du mapping : on matche le `path` de l'URL SonarQube sur une regex
 * et on renvoie le JSON correspondant dans le meme format que le vrai
 * `httpSonarQube` (cle `code`, `message`, `json`, `erreur`).
 *
 * Si une URL n'est pas mappee, on renvoie code=404 pour que le code appelant
 * gere une reponse absente (comportement explicite plutot qu'une exception).
 */
class SonarFixtureClientService extends ClientService
{
    /** @var array<string, string> regex path => chemin du fichier fixture */
    private const URL_MAP = [
        '#/api/issues/search\b#'              => 'issues/search.json',
        '#/api/hotspots/search\b#'            => 'hotspots/search.json',
        '#/api/hotspots/show\b#'              => 'hotspots/show.json',
        '#/api/project_analyses/search\b#'    => 'project_analyses/search-page1.json',
        '#/api/components/search_projects\b#' => 'components/search_projects.json',
        '#/api/components/app\b#'             => 'components/app.json',
        '#/api/measures/component\b#'         => 'measures/component.json',
        '#/api/qualityprofiles/search\b#'     => 'qualityprofiles/search.json',
        '#/api/server/version\b#'             => 'server/version.json',
    ];

    public function httpSonarQube(string $url): array
    {
        $fixturesDir = dirname(__DIR__) . '/fixtures/sonarqube';

        foreach (self::URL_MAP as $pattern => $relativePath) {
            if (preg_match($pattern, $url) !== 1) {
                continue;
            }

            $fixturePath = $fixturesDir . '/' . $relativePath;

            if (!is_file($fixturePath)) {
                return [
                    'code' => 500,
                    'message' => "[GET] - 500 - fixture missing: $relativePath",
                    'json' => null,
                    'erreur' => "Fixture SonarQube introuvable : $relativePath",
                ];
            }

            $raw = file_get_contents($fixturePath);
            // Les fixtures générées via PowerShell 5.1 `Set-Content -Encoding UTF8`
            // portent un BOM UTF-8 (EF BB BF) qui casse json_decode.
            if (str_starts_with($raw, "\xEF\xBB\xBF")) {
                $raw = substr($raw, 3);
            }
            $json = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

            return [
                'code' => 200,
                'message' => "[GET] - 200 - fixture:$relativePath",
                'json' => $json,
                'erreur' => null,
            ];
        }

        return [
            'code' => 404,
            'message' => "[GET] - 404 - unmapped url",
            'json' => null,
            'erreur' => "URL SonarQube non mappée dans les fixtures : $url",
        ];
    }
}
