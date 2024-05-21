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

/** Gestion du journal d'activité */
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/** Rotation des logs */
use Cesargb\Log\Rotation;
use Cesargb\Log\Exceptions\RotationFailed;

/**
 * [Description IsValideMavenKey]
 */
class FileLogger
{
    /**
     * [Description for logrotate]
     * Journalisation des demandes de traitements différés.
     *
     * @param string $path
     *
     * @return void
     *
     * Created at: 05/03/2023, 18:01:55 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function logrotate($path): void
    {
        /* On initialise le journal des traces */
        $filesystem = new Filesystem();
        /* Le dossier d'audit est présent */
        if ($filesystem->exists($path)) {
            /** Rotation des logs */
            $rotation = new Rotation([
                'files' => 5,
                'compress' => true,
                'min-size' => 102400,
                'truncate' => false,
                'then' => function ($filenameTarget, $filenameRotated) {},
                'catch' => function (RotationFailed $exception) {},
                'finally' => function ($message, $filenameTarget) {},
            ]);
        }

        /** on récupère les logs */
        $finder = new Finder();
        $finder->files()->in($path)->depth(0)->sortByName();
        foreach ($finder as $file) {
            $rotation->rotate($file->getPathname());
        }
    }

    /**
     * [Description for information]
     * Ajoute un journal de traitements différés pour un portefeuille.
     *
     * @param string $portefeuille
     * @param mixed $log
     *
     * @return void
     *
     * Created at: 05/03/2023, 00:01:12 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function information($path, $portefeuille, $log): int
    {
        /* On initialise le journal des traces */
        $filesystem = new Filesystem();
        /* Le dossier d'audit est présent */
        if ($filesystem->exists($path)) {
            $name = preg_replace('/\s+/', '_', $portefeuille);
            $fichier = "$path\manuel_{$name}.log";
            $filesystem->appendToFile($fichier, $log, true);
        } else {
            return 404;
        }
        return 200;
    }

}
