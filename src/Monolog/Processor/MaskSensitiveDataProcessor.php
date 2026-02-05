<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2025..
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
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
     * Created at: 18/09/2024 08:01:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
     * @param array $patterns
     *
     * @return string
     *
     * Created at: 05/02/2026 14:56:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function maskSensitiveData(string $data, array $patterns): string
    {
        foreach ($patterns as $pattern) {
            $data = preg_replace_callback($pattern, function ($matches) {
                return preg_replace('/=\s*\S+/', ' ***MASKED***', $matches[0]);
            }, $data);
        }
        return $data;
    }

    /**
     * [Description for maskSensitiveDataArray]
     *
     * @param array $data
     * @param array $patterns
     *
     * @return array
     *
     * Created at: 05/02/2026 14:56:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
