<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Component\HttpClient\HttpClient;

/**
 * Trait à utiliser dans les tests Functional dont le contrôleur testé
 * effectue un appel HTTP réel vers SonarQube.
 *
 * Sans serveur SonarQube joignable, ces tests hangent au lieu d'échouer
 * proprement (timeout HTTP par défaut long, boucle sur pagination, etc.).
 *
 * Usage :
 *   protected function setUp(): void
 *   {
 *       $this->skipIfSonarQubeUnreachable();
 *       parent::setUp();
 *   }
 *
 * Résolution de l'URL : variable d'environnement SONAR_URL
 * (le paramètre Symfony sonar.url y est lié via %env(SONAR_URL)%).
 *
 * Le résultat du ping est mis en cache statique pour ne pas payer
 * le timeout sur chaque test de la suite.
 *
 * @phpstan-ignore trait.unused (trait de support planifié pour les futurs tests fonctionnels SonarQube)
 */
trait SonarQubeReachableTrait
{
    private static ?bool $sonarQubeCachedReachable = null;
    private static ?string $sonarQubeSkipReason = null;

    protected function skipIfSonarQubeUnreachable(): void
    {
        if (self::$sonarQubeCachedReachable === true) {
            return;
        }

        if (self::$sonarQubeCachedReachable === false) {
            self::markTestSkipped(self::$sonarQubeSkipReason ?? '[SonarQubeReachable] ⚠️ SonarQube injoignable.');
        }

        $baseUrl = self::resolveSonarQubeBaseUrl();

        if ($baseUrl === null) {
            self::$sonarQubeCachedReachable = false;
            self::$sonarQubeSkipReason = '[SonarQubeReachable] ⚠️ SONAR_URL non défini — test ignoré.';
            self::markTestSkipped(self::$sonarQubeSkipReason);
        }

        try {
            $client = HttpClient::create([
                'timeout' => 2,
                'max_duration' => 2,
                'verify_peer' => false,
                'verify_host' => false,
            ]);
            $response = $client->request('GET', rtrim($baseUrl, '/') . '/api/system/status');
            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 500) {
                self::$sonarQubeCachedReachable = true;
                return;
            }
            self::$sonarQubeSkipReason = sprintf(
                '[SonarQubeReachable] ⚠️ SonarQube a répondu %d sur /api/system/status — test ignoré.',
                $status
            );
        } catch (\Throwable $e) {
            self::$sonarQubeSkipReason = sprintf(
                '[SonarQubeReachable] ⚠️ SonarQube injoignable (%s) — test ignoré.',
                $e->getMessage()
            );
        }

        self::$sonarQubeCachedReachable = false;
        self::markTestSkipped(self::$sonarQubeSkipReason);
    }

    private static function resolveSonarQubeBaseUrl(): ?string
    {
        $candidates = [
            $_ENV['SONAR_URL'] ?? null,
            $_SERVER['SONAR_URL'] ?? null,
            getenv('SONAR_URL') ?: null,
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
