<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

/* MODIF 2026-05-21 : déplacé dans App\Command\Dev. */
namespace App\Command\Dev;

use App\Entity\DcProcessingQueue;
use App\Exception\DependencyCheck\DcInvalidJsonException;
use App\Exception\DependencyCheck\DcInvalidStructureException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument,InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Ulid;

/**
 * MODIF 2026-05-12 : commande locale d'enqueue
 * de rapports DependencyCheck pour les campagnes qualité (audit, tests).
 *
 * Alternative au POST /api/secure/dependency-check/upload : insère directement
 * une row DcProcessingQueue (status=queued) en lisant des fichiers JSON locaux.
 * Le worker `app:dependency-check:process` les traite ensuite normalement.
 *
 * Reproduit fidèlement le pattern de ApiDependencyCheckUploadController :
 *   - ULID base32 unique
 *   - payload gzippe (gzencode niveau 6)
 *   - sha256 du JSON décompresse
 *   - content_type = json
 *   - status = queued
 *
 * Usage :
 *   bin/console app:dc:ingest-local var/                 # tous les *.json du dossier
 *   bin/console app:dc:ingest-local var/tetris.json      # un fichier précis
 *
 * Après enqueue, lancer le worker pour ingérer :
 *   bin/console app:dependency-check:process --batch=50 --max-batches=1
 */
#[AsCommand(
    name: 'app:dc:ingest-local',
    description: 'Enqueue des rapports DependencyCheck depuis un dossier local (campagnes qualité).',
)]
class DcIngestLocalCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.environment%')]
        private readonly string $appEnv,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'path',
            InputArgument::REQUIRED,
            'Chemin vers un fichier JSON ou un dossier contenant des *.json (non récursif).'
        );
    }

    /**
     * [Description for execute]
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * Created at: 15/05/2026 08:13:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        // MODIF 2026-05-21 : garde-fou env=dev (ingest local = campagnes qualité).
        if ($this->appEnv !== 'dev') {
            $io->error(sprintf('❌ Cette commande est réservée à env=dev (env courant : %s).', $this->appEnv));
            return Command::FAILURE;
        }
        $path = (string) $input->getArgument('path');

        if (!is_readable($path)) {
            $io->error("Chemin introuvable ou non lisible : {$path}");
            return Command::FAILURE;
        }

        $files = is_dir($path)
            ? glob(rtrim($path, '/\\') . DIRECTORY_SEPARATOR . '*.json')
            : [$path];
        if (empty($files)) {
            $io->warning("Aucun *.json trouvé dans {$path}");
            return Command::SUCCESS;
        }

        /* MODIF 2026-05-12 : lecture optionnelle
         * d'un sidecar `dc-metadata.json` situé dans le même dossier que les
         * rapports. Format :
         *   { "ma-moulinette-1.0.0-RELEASE.json": {
         *       "parent_label": "springboot-config",
         *       "parent_version": "1.2.0-RC1",
         *       "archetype_version": "1.2.0-RELEASE"
         *     },
         *     ...
         *   }
         * Permet de simuler les headers HTTP X-Parent-* / X-Archetype-Version
         * envoyés par la CI en prod, pour les campagnes qualité. */
        $metadataDir  = is_dir($path) ? $path : dirname($path);
        $metadataPath = rtrim($metadataDir, '/\\') . DIRECTORY_SEPARATOR . 'dc-metadata.json';
        $metadataMap  = [];
        if (is_file($metadataPath) && is_readable($metadataPath)) {
            $metaRaw = file_get_contents($metadataPath);
            if ($metaRaw !== false && $metaRaw !== '') {
                try {
                    $parsedMeta = json_decode($metaRaw, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($parsedMeta)) {
                        $metadataMap = $parsedMeta;
                        $io->note(sprintf('Sidecar dc-metadata.json detecté (%d entrées).', count($metadataMap)));
                    }
                } catch (\JsonException $e) {
                    $io->warning('dc-metadata.json présent mais JSON invalide, ignoré : ' . $e->getMessage());
                }
            }
        }

        $enqueued = 0;
        $errors   = 0;
        foreach ($files as $file) {
            $name = basename($file);
            // Skip le fichier sidecar lui-même s'il fait partie du listing
            if ($name === 'dc-metadata.json') {
                continue;
            }
            try {
                $jsonString = file_get_contents($file);
                /* MODIF 2026-05-24 : \RuntimeException → exceptions DC typées. */
                if ($jsonString === false || $jsonString === '') {
                    throw new DcInvalidJsonException('Fichier vide ou illisible.');
                }
                $parsed = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($parsed)) {
                    throw new DcInvalidJsonException('JSON racine non-objet.');
                }
                $projectInfo = $parsed['projectInfo'] ?? [];
                $group    = trim((string) ($projectInfo['groupID']    ?? ''));
                $artifact = trim((string) ($projectInfo['artifactID'] ?? ''));
                $version  = trim((string) ($projectInfo['version']    ?? ''));
                if ($group === '' || $artifact === '' || $version === '') {
                    throw new DcInvalidStructureException("projectInfo incomplet (group={$group}, artifact={$artifact}, version={$version}).");
                }

                $meta = $metadataMap[$name] ?? [];
                $parentLabel      = self::trimMeta($meta['parent_label']      ?? null, 128);
                $parentVersion    = self::trimMeta($meta['parent_version']    ?? null, 64);
                $archetypeVersion = self::trimMeta($meta['archetype_version'] ?? null, 64);

                $entry = (new DcProcessingQueue())
                    ->setUlid((new Ulid())->toBase32())
                    ->setPayloadGz(gzencode($jsonString, 6))
                    ->setPayloadSha256(hash('sha256', $jsonString))
                    ->setPayloadSize(strlen($jsonString))
                    ->setContentType(DcProcessingQueue::CONTENT_JSON)
                    ->setProjectGroup($group)
                    ->setProjectArtifact($artifact)
                    ->setProjectVersion($version)
                    ->setParentLabel($parentLabel)
                    ->setParentVersion($parentVersion)
                    ->setArchetypeVersion($archetypeVersion);

                $this->em->persist($entry);
                $this->em->flush();
                $enqueued++;
                $metaSuffix = $parentLabel !== null || $parentVersion !== null || $archetypeVersion !== null
                    ? sprintf(' [parent=%s@%s, archetype=%s]',
                        $parentLabel ?? '-', $parentVersion ?? '-', $archetypeVersion ?? '-')
                    : '';
                $io->writeln(sprintf(
                    '<info>✓</info> %s → %s:%s:%s%s (ulid=%s)',
                    $name,
                    $group,
                    $artifact,
                    $version,
                    $metaSuffix,
                    $entry->getUlid()
                ));
            } catch (\Throwable $e) {
                $errors++;
                $io->writeln(sprintf('<error>✗ %s : %s</error>', $name, $e->getMessage()));
            }
        }

        $io->newLine();
        $io->success(sprintf('%d fichier(s) enqueue, %d erreur(s).', $enqueued, $errors));
        if ($enqueued > 0) {
            $io->note("Lancer ensuite : bin/console app:dependency-check:process --batch={$enqueued} --max-batches=1");
        }
        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * [Description for trimMeta]
     * MODIF 2026-05-12 : nettoie une valeur de sidecar.
     * Retourne null si null/vide/non-string, tronque à la longueur cible.
     *
     * @param mixed $raw
     * @param int $maxLength
     *
     * @return string|null
     *
     * Created at: 15/05/2026 08:14:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function trimMeta(mixed $raw, int $maxLength): ?string
    {
        if (!is_string($raw)) {
            return null;
        }
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }
        return substr($trimmed, 0, $maxLength);
    }
}
