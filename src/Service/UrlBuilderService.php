<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2025.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Service;

use Psr\Log\LoggerInterface;

/**
 * [Description UrlBuilderService]
 */
class UrlBuilderService
{

    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for build]
     *
     * @param string $baseUrl
     * @param string $path
     * @param array $queryParams
     *
     * @return string
     *
     * Created at: 13/07/2025 11:44:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function build(string $baseUrl, string $path, array $queryParams = []): string
    {
        // Construction propre de l'URL
        $url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');

        // Ajout des paramètres GET
        if (!empty($queryParams)) {
            $query = http_build_query($queryParams);
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . $query;
        }

        // Validation de l'URL générée
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->logger->error('❌ [URLBuilder] URL invalide générée', [
                'baseUrl' => $baseUrl,
                'path' => $path,
                'queryParams' => $queryParams,
                'fullUrl' => $url,
            ]);

            throw new \InvalidArgumentException("URL invalide générée : " . $url);
        }

        // Log debug en cas de succès
        $this->logger->debug('URLBuilder: URL construite avec succès', [
            'baseUrl' => $baseUrl,
            'path' => $path,
            'queryParams' => $queryParams,
            'fullUrl' => $url,
        ]);

        return $url;
    }
}
