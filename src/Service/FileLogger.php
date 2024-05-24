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

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Cesargb\Log\Rotation;
use Cesargb\Log\Exceptions\RotationFailed;

/**
 * [Description FileLogger]
 */
class FileLogger
{
    private string $path;

    public function __construct(private ParameterBagInterface $params)
    {
        $this->params = $params;
        $this->path = $this->params->get('kernel.project_dir') . $this->params->get('path.audit');
    }


    /**
     * [Description for logrotate]
     * Journalisation des demandes de traitements différés.
     *
     * @return void
     *
     * Created at: 05/03/2023, 18:01:55 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function logrotate(): void
    {
        $filesystem = new Filesystem();

        if ($filesystem->exists($this->path)) {
            $rotation = new Rotation([
                'files' => 5,
                'compress' => true,
                'min-size' => 102400,
                'truncate' => false,
                'then' => function ($filenameTarget, $filenameRotated) {},
                'catch' => function (RotationFailed $exception) {},
                'finally' => function ($message, $filenameTarget) {},
            ]);

            $finder = new Finder();
            $finder->files()->in($this->path)->depth(0)->sortByName();

            foreach ($finder as $file) {
                $rotation->rotate($file->getPathname());
            }
        }
    }

    /**
     * [Description for log]
     * Ajoute un journal de traitements différés pour un portefeuille.
     *
     * @param string $portefeuille
     * @param string $log
     *
     * @return void
     *
     * Created at: 05/03/2023, 00:01:12 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function log(string $portefeuille, string $log): int
    {
        $filesystem = new Filesystem();

        if ($filesystem->exists($this->path)) {
            $name = preg_replace('/\s+/', '_', $portefeuille);
            $filePath = $this->path . "/manuel_{$name}.log";
            $filesystem->appendToFile($filePath, $log);
            return 200;
        } else {
            return 404;
        }
    }

}
