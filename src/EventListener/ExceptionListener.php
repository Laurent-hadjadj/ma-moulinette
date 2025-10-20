<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * [Description ExceptionListener]
 */
class ExceptionListener
{

    public function __construct(
        private LoggerInterface $logger
    ){}

    /**
     * [Description for onKernelException]
     *
     * @param ExceptionEvent $event
     *
     * @return void
     *
     * Created at: 17/09/2024 17:13:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Masque les données sensibles dans la trace
        if (!$exception instanceof HttpExceptionInterface || $exception->getStatusCode() >= 500) {
            $this->logger->error('Security Sensitive', [
                'exception_message' => $this->maskSensitive($exception->getMessage()),
                'exception_code' => $exception->getCode(),
                'exception_file' => $exception->getFile(),
                'exception_line' => $exception->getLine(),
                'trace' => $this->maskSensitive($exception->getTraceAsString()),
            ]);
        }
    }

    /**
     * [Description for maskSensitive]
     *
     * @param string $content
     *
     * @return string
     *
     * Created at: 20/10/2025 20:38:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function maskSensitive(string $content): string
    {
        // Expression régulière pour détecter des valeurs sensibles
        $patterns = [
            '/password\s*=\s*\S+/',
            '/secret\s*=\s*\S+/',
            '/token\s*=\s*\S+/',
            '/key\s*=\s*\S+/',
        ];

        foreach ($patterns as $pattern) {
            $content = preg_replace_callback($pattern, function ($matches) {
                // Remplacer la valeur par "***MASKED***"
                return preg_replace('/=\s*\S+/', ' ***MASKED***', $matches[0]);
            }, $content);
        }

        return $content;
    }

}
