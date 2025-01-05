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

use Cesargb\Log\Rotation;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * [Description FileLogger]
 */
class FileLogger
{
    private string $path;

    public function __construct(
        private ParameterBagInterface $params,
        private Filesystem $filesystem,
        private Finder $finder,
        private Rotation $rotation
        )
    {
        $this->params = $params;
        $this->filesystem = $filesystem;
        $this->finder = $finder;
        $this->rotation = $rotation;

        // Obtenir le chemin avec nettoyage des barres obliques inversées
        $projectDir = rtrim($this->params->get('kernel.project_dir'), '\\');
        $auditPath = ltrim($this->params->get('path.audit'), '\\');
        $this->path = $projectDir . DIRECTORY_SEPARATOR . $auditPath;
    }

    /**
     * [Description for downloadContent]
     *
     * @param string $portefeuille
     * @param string $type
     *
     * @return array
     *
     * Created at: 05/06/2024 20:08:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@  me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function downloadContent(string $portefeuille, string $type){
    $filesystem = new Filesystem();
    // Initialisation de recherche à KO
    $recherche = "KO";
    // c'est le texte qui sera affiché à l'utilisateur si le fichier qu'il a demandé n'est pas trouvé
    $content = 'Pas de contenu !!!';
    $fileFound = false;

    // Si le dossier existe
    if ($filesystem->exists($this->path)) {
        /** Le fichier est composé, du type: 'manuel' ou 'automatique' et du nom du portefeuille*/
        $name = preg_replace("/[ :.]/", "_", $portefeuille);  // Normalise le nom
        $fichier = "{$type}_{$name}.log";  // Nom du fichier attendu. Ex. manuel_mes_projets.log

        /** On scanne le dossier pour trouver le fichier demandé */
        $finder = new Finder();
        $finder->files()->in($this->path);
        $finder->name($fichier);

        foreach ($finder as $file) {
            $fileFound = true;
            // Récupère le contenu du fichier
            $content = $file->getContents();
        }

        if (!$fileFound) {
            $recherche = 'KO';
            $content = 'Pas de contenu !!!';
        } else {
            // Si le contenu est vide, retourner "Pas de journal disponible.", sinon "OK"
            $recherche = (empty($content)) ? 'Pas de journal disponible.' : 'OK';
        }
    }

    /**
     * On retourne KO, si le chemin n'existe pas,
     * 'pas de journal disponible' si on a trouvé un fichier mais vide, 'content' => 'Pas de contenu !!!'
     * 'OK', on a trouvé un fichier avec un contenu, alors 'content' => $content
     */
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
                'min-size' => 1048576, //1mo
                'truncate' => false,
                //'then' => function ($filenameTarget, $filenameRotated) {},
                //'catch' => function (RotationFailed $exception) {},
                //'finally' => function ($message, $filenameTarget) {},
            ]);

            $finder = new Finder();
            $finder->files()->in($this->path)->depth(0)->sortByName();

            /** Rotation pour tous les fichier de 1mo + 1oc  */
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
     */
    public function file($portefeuille, $collecte)
    {
        return static::log($portefeuille, $this->formatArray($collecte), 'append');
    }

    /**
     * Fonction pour formater les informations du tableau de manière récursive
     *
     * @param mixed $json
     * @param int $level
     *
     * @return string
     */
    private function formatArray($json, $level = 1)
    {
        if (!is_array($json)) {
            return '';
        }
        $output = '';
        $tag = 'h' . ($level + 1);

        foreach ($json as $key => $value) {
            if (is_array($value)) {
                // Si la clé est numérique, ne pas l'afficher
                if (!is_numeric($key)) {
                    $output .= "<$tag>" . htmlspecialchars($key) . "</$tag>";
                }
                // Appel récursif avec un niveau d'indentation supérieur
                $output .= $this->formatArray($value, $level + 1);
            } else {
                // Vérifie si la valeur est un objet DateTimeImmutable et la convertir en chaîne si nécessaire
                if ($value instanceof \DateTimeImmutable || $value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                }

                // Si la valeur est un objet, on la convertit en chaîne via print_r
                if (is_object($value)) {
                    $value = print_r($value, true); // Cela donne le format attendu de l'objet
                    // Suppression des retours à la ligne pour les objets
                    $value = str_replace(["\n", "\r"], '', $value);
                    // Suppression des espaces inutiles avant et après les parenthèses
                    $value = preg_replace('/\(\s+/', '(', $value);
                    $value = preg_replace('/\s+\)/', ')', $value);
                    $value = preg_replace('/\s+=>/', ' =>', $value); // Réduction des espaces avant '=>'
                }

                if (is_numeric($key)) {
                    $output .= '<p>' . htmlspecialchars($value) . '</p>';
                } else {
                    $output .= '<p><strong>' . htmlspecialchars($key) . ' : </strong> ' .htmlspecialchars($value) . '</p>';
                }
            }

        }
            return $output;
    }

    /**
     * [Description for log]
     * Ajoute un journal de traitements différés pour un portefeuille.
     *
     * @param string $portefeuille
     * @param string $log
     * @param string $type
     *
     * @return integer
     *
     * Created at: 05/03/2023, 00:01:12 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function log(string $portefeuille, string $log, string $type): int
    {
        $filesystem = new Filesystem();
        if ($filesystem->exists(rtrim($this->path, '/\\') . DIRECTORY_SEPARATOR)) {
            $name = preg_replace("/[ :.]/", "_", $portefeuille);
            $filePath = rtrim($this->path, '/\\') . "/manuel_{$name}.log";
            if ($type === 'append') {
                $filesystem->appendToFile($filePath, $log);
            } elseif ($type === 'remove') {
                    $filesystem->remove($filePath);
                    return 202;
            } else {
                // Si le type n'est ni 'append' ni 'remove'
                return 400;
            }
            return 200;
        }
        return 404;
    }

}
