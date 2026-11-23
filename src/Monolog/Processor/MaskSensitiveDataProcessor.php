<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Monolog\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * [Description MaskSensitiveDataProcessor]
 */
class MaskSensitiveDataProcessor implements ProcessorInterface
{

    /**
     * [Description for __invoke]
     *
     * @param LogRecord $record
     *
     * @return LogRecord
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        // Regex pour détecter les valeurs sensibles
        $patterns = [
            '/password\s*=\s*\S+/i',
            '/secret\s*=\s*\S+/i',
            '/token\s*=\s*\S+/i',
            '/key\s*=\s*\S+/i',
        ];

        // Masquer les informations sensibles dans le message
        $maskedMessage = $this->maskSensitiveData($record->message, $patterns);

        // Masquer les informations sensibles dans le contexte et extra
        $maskedContext = $this->maskSensitiveDataArray($record->context, $patterns);
        $maskedExtra = $this->maskSensitiveDataArray($record->extra, $patterns);

        // Si le message a été masqué, videz le contexte et extra
        if (strpos($maskedMessage, ' ***MASKED***') !== false) {
            $maskedContext = [];
            $maskedExtra = [];
        }

        // Retourner un nouvel objet LogRecord avec les données modifiées
        return $record->with(
            message: $maskedMessage,
            context: $maskedContext,
            extra: $maskedExtra
        );
    }

    /**
     * [Description for maskSensitiveData]
     *
     * @param string $data
     * @param array<int, string> $patterns
     *
     * @return string
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    private function maskSensitiveData(string $data, array $patterns): string
    {
        foreach ($patterns as $pattern) {
            $data = preg_replace_callback($pattern, function (array $matches): string {
                return preg_replace('/=\s*\S+/', ' ***MASKED***', $matches[0]) ?? $matches[0];
            }, $data);
        }
        return $data;
    }

    /**
     * [Description for maskSensitiveDataArray]
     *
     * @param array<string, mixed> $data
     * @param array<int, string> $patterns
     *
     * @return array<string, mixed>
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    private function maskSensitiveDataArray(array $data, array $patterns): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->maskSensitiveData($value, $patterns);
            }
        }
        return $data;
    }
}
