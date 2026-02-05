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
 * [Description MaskSensitiveEnvVariablesProcessor]
 */
class MaskSensitiveEnvVariablesProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        // Liste des préfixes des variables d'environnement sensibles
        $sensitivePrefixes = [
            'API_KEY_',
            'SECRET_',
        ];

        // Masquer les valeurs sensibles dans le message
        $maskedMessage = $this->maskSensitiveMessage($record->message, $sensitivePrefixes);

        // Masquer les valeurs sensibles dans le contexte
        $maskedContext = $this->maskSensitiveData($record->context, $sensitivePrefixes);

        // Masquer les valeurs sensibles dans les extras
        $maskedExtra = $this->maskSensitiveData($record->extra, $sensitivePrefixes);
        $maskedExtra['masked_message'] = $maskedMessage;

        // Créer un nouveau LogRecord avec les informations masquées
        return new LogRecord(
            $record->datetime,
            $record->channel,
            $record->level,
            $maskedMessage,
            $maskedContext,
            $maskedExtra,
        );
    }

    /**
     * [Description for maskSensitiveData]
     *
     * @param array $data
     * @param array $sensitivePrefixes
     *
     * @return array
     *
     * Created at: 05/02/2026 14:56:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function maskSensitiveData(array $data, array $sensitivePrefixes): array
    {
        foreach ($data as $key => $value) {
            foreach ($sensitivePrefixes as $prefix) {
                if (strpos($key, $prefix) === 0 && $value) {
                    $data[$key] = ' ***MASKED***';
                }
            }
        }
        return $data;
    }

    /**
     * [Description for maskSensitiveMessage]
     *
     * @param string $message
     * @param array $sensitivePrefixes
     *
     * @return string
     *
     * Created at: 05/02/2026 14:56:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function maskSensitiveMessage(string $message, array $sensitivePrefixes): string
    {
        foreach ($_ENV as $envVar => $value) {
            foreach ($sensitivePrefixes as $prefix) {
                if (strpos($envVar, $prefix) === 0 && $value) {
                    $message = str_replace($value, ' ***MASKED***', $message);
                }
            }
        }
        return $message;
    }
}
