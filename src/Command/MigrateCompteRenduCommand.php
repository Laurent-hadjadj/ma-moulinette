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

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;

/**
 * [Description MigrateCompteRenduCommand]
 *  php bin/console app:migrate-compte-rendu --batch-size=50 --dry-run
 *  php bin/console app:migrate-compte-rendu --startId=123 --batch-size=50 --dry-run
 */
#[AsCommand(
    name: 'app:migrate-compte-rendu',
    description: 'Migration des compte_rendu vers bytea gzencode'
)]
class MigrateCompteRenduCommand extends Command
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    // Adapter le namespace / entity selon ton code
    private string $entityClass = \App\Entity\BatchExecutionJournal::class;
    private string $idField = 'id';

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * [Description for configure]
     *
     * @return void
     *
     * Created at: 12/11/2025 19:50:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function configure(): void
    {
        $this
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, 'Taille du lot pour le traitement.', 100)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Afficher ce qui serait fait sans modifier la BD.')
            ->addOption('start-id', null, InputOption::VALUE_OPTIONAL, 'ID de départ (inclus).', 0)
            ->addOption('max-rows', null, InputOption::VALUE_OPTIONAL, 'Nombre max d\'enregistrements à traiter.', 0)
            ->addOption('content-type', null, InputOption::VALUE_OPTIONAL, 'Type de contenu à traiter (texte).', '')
            ->setHelp(<<<'HELP'
Parcourt les enregistrements et compresses les champ `compte_rendu` non compressés (gzencode niveau 9).
Utiliser --dry-run pour simuler.
HELP
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
     * Created at: 12/11/2025 20:24:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = (int) $input->getOption('batch-size');
        $dryRun = (bool) $input->getOption('dry-run');
        $startId = (int) $input->getOption('start-id');
        $maxRows = (int) $input->getOption('max-rows');
        $contentType = $input->getOption('content-type');

        $repo = $this->em->getRepository($this->entityClass);

        $qb = $repo->createQueryBuilder('e')
            ->select('COUNT(e.id)');

        $items = $qb->getQuery()->getSingleScalarResult();

        $output->writeln([
            'Migration compte_rendu -> gzencode()',
            'Entity: ' . $this->entityClass,
            'Batch size: ' . $batchSize,
            'Nombre enregistrement: ' . $items,
            $dryRun ? 'Mode: DRY RUN (aucune écriture)' : 'Mode: WRITE (modifications en base)',
            '',
        ]);

        $lastId = $startId;
        $processed = 0;
        $updated = 0;

        // Boucle par lots (ID croissants) pour éviter les gros offsets
        while (true) {
            // Construire une requête paramétrée : on récupère les entités > lastId limit batchSize
            $qb = $repo->createQueryBuilder('e')
                ->where("e.{$this->idField} > :lastId")
                ->setParameter('lastId', $lastId)
                ->orderBy("e.{$this->idField}", 'ASC')
                ->setMaxResults($batchSize);

            $items = $qb->getQuery()->getResult();

            if (count($items) === 0) {
                break;
            }

            foreach ($items as $item) {
                $processed++;
                $id = $item->{$this->idField} ?? null;
                $lastId = $id;

                // Accès direct au champ brut
                $content = $item->getCompteRenduBrut();

                if ($content === null || $content === '') {
                    $this->logger->debug("ID {$id} -> compte_rendu vide, skip");
                    continue;
                }

                $isGzip = substr($content, 0, 2) === "\x1f\x8b";

                // Optionnel : filtrer par type
                if ($contentType === 'texte' && $isGzip) {
                    $this->logger->debug("ID {$id} -> déjà gz, skip (--run-batch=texte)");
                    continue;
                }

                // Sinon c'est du texte brut => compression
                if (!$isGzip) {
                    $this->logger->debug("ID {$id} -> va être compressé (taille avant: " . strlen($content) . ")");

                    if (!$dryRun) {
                        /* Le setter est responsable de la compression */
                        $item->setCompteRendu($content);
                        $this->em->persist($item);
                        $updated++;
                    }
                } else {
                        $this->logger->debug("ID {$id} -> déjà gz, skip");
                }

                // Flush par lot pour limiter la mémoire
                if (!$dryRun && ($processed % $batchSize === 0)) {
                    $this->em->flush();
                    $this->em->clear();
                    // important : detach toutes les entités
                    // Re-obtenir le repository après clear
                    $repo = $this->em->getRepository($this->entityClass);
                    $this->logger->info("Flush & clear after {$processed} processed");
                }

                // limite optionnelle
                if ($maxRows > 0 && $processed >= $maxRows) {
                    break 2;
                }
            } // foreach items

            // Petit GC
            gc_collect_cycles();
        } // while

        // Flush final s'il reste des entités
        if (!$dryRun) {
            $this->em->flush();
            $this->em->clear();
        }

        $output->writeln("");
        $output->writeln("Terminé. Enregistrements parcourus : {$processed}. Enregistrements compressés : {$updated}.");
        $this->logger->info("Migration terminée. Parcourus: {$processed}, compressés: {$updated}");
        return Command::SUCCESS;
    }
}
