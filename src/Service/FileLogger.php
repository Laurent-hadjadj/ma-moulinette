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
     * [Description for downloadContent]
     *
     * @param mixed $file
     *
     * @return array
     *
     * Created at: 05/06/2024 20:08:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@  me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function downloadContent($portefeuille, $type){
        /* On initialise le journal des traces */
        $filesystem = new Filesystem();
        $completPath = $this->path;

        $recherche = "KO";
        $content='Pas de contenu !!!';
        $finder='';
        /* Le dossier d'audit est présent */
        if ($filesystem->exists($completPath)) {
            $name = preg_replace('/\s+/', '_', $portefeuille);
            $fichier = "{$type}_$name.log";

            /** on récupère la log */
            $finder = new Finder();
            $finder->files()->in($completPath);
            $finder->name($fichier);

            foreach ($finder as $file) {
                $content = $file->getContents();
            }
            $recherche = (empty($content)) ? 'Pas de journal disponible.' : 'OK';
        }
        return ["recherche" => $recherche, 'content' => $content];
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
                //'then' => function ($filenameTarget, $filenameRotated) {},
                //'catch' => function (RotationFailed $exception) {},
                //'finally' => function ($message, $filenameTarget) {},
            ]);

            $finder = new Finder();
            $finder->files()->in($this->path)->depth(0)->sortByName();

            foreach ($finder as $file) {
                $rotation->rotate($file->getPathname());
            }
        }
    }

    /**
     * [Description for file]
     *
     * @param mixed $collecte
     *
     * @return string
     *
     * Created at: 05/06/2024 18:52:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function file($mavenKey, $collecte){
        // Fonction pour formater les informations du tableau de manière récursive
        function formatArray($json, $level = 1) {
            // Retourner une chaîne vide si les données ne sont pas un tableau
            if (!is_array($json)) {
                return '';
            }
            $output = '';
            // Déterminer la balise à utiliser (h1, h2, etc.)
            $tag = 'h' . $level;

            foreach ($json as $key => $value) {
                if (is_array($value)) {
                    // Si la clé est numérique, ne pas l'afficher
                    if (!is_numeric($key)) {
                        $output .= "<$tag>" . htmlspecialchars($key) . "</$tag>";
                    }
                    // Appel récursif avec un niveau d'indentation supérieur
                    $output .= formatArray($value, $level + 1);
                } else {
                    // Si la clé est numérique, ne pas l'afficher
                    if (is_numeric($key)) {
                        $output .= '<p>' . htmlspecialchars($value) . '</p>';
                    } else {
                        $output .= "<p><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . '</p>';
                    }
                }
            }
            return $output;
        }

        return static::log($mavenKey, formatArray($collecte));
    }

    /**
     * [Description for log]
     * Ajoute un journal de traitements différés pour un portefeuille.
     *
     * @param string $portefeuille
     * @param string $log
     *
     * @return integer
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
