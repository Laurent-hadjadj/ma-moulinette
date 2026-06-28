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

/* MODIF 2026-05-21 : déplacé dans App\Command\Prod. */
namespace App\Command\Prod;

use App\Repository\DcProcessingQueueRepository;
use App\Repository\DcScanRepository;
use App\Service\DependencyCheck\DependencyCheckIngester;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * MODIF 2026-05-10 : commande de re-traitement d'un scan DC
 * depuis la queue. Use case principal : ré-trofitter une donnée stockée
 * désormais sans avoir à re-uploader le JSON via l'API.
 *
 * Le payload est conservé en queue 30 jours (rétention par DependencyCheckPurgeCommand),
 * donc tant que le scan a moins de 30j il peut être ré-ingéré.
 *
 * Mécanique :
 *  1. Récupère la row de queue par son ULID.
 *  2. Trouve le scan existant correspondant au triplet (group, artifact, version).
 *  3. DELETE du scan (CASCADE -> supprime les findings).
 *  4. Appel `DependencyCheckIngester::ingest($queue)` qui recrée tout en
 *     parsant le payload depuis la queue (la nouvelle logique d'ingestion
 *     applique au passage les évolutions du schéma : fixed_version, etc.).
 *
 * Exemples :
 *   php bin/console app:dependency-check:reprocess 01HZA3KGM4XYZ...      -> reprocess (avec --force)
 *   php bin/console app:dependency-check:reprocess 01HZA3KGM4XYZ... -f   -> idem (force court)
 *   php bin/console app:dependency-check:reprocess 01HZA3KGM4XYZ... --dry-run -> simulation
 */
#[AsCommand(
    name: 'app:dependency-check:reprocess',
    description: 'Re-traite un scan DC depuis sa queue (retrofit fixed_version, etc.) - destructif !',
)]
class DependencyCheckReprocessCommand extends Command
{
    public function __construct(
        private readonly DcProcessingQueueRepository $queueRepository,
        private readonly DcScanRepository $scanRepository,
        private readonly DependencyCheckIngester $ingester,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * [Description for configure]
     *
     * @return void
     *
     * Created at: 24/05/2026 10:46:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                'ulid',
                InputArgument::REQUIRED,
                'ULID du payload en queue à re-traiter (cf. dc_processing_queue.ulid).'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Confirme la suppression du scan existant + sa ré-ingestion.'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Affiche ce qui serait fait sans rien modifier.'
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
     * Created at: 24/05/2026 10:46:42 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $ulid   = (string) $input->getArgument('ulid');
        $force  = (bool) $input->getOption('force');
        $dryRun = (bool) $input->getOption('dry-run');

        /* 1. Récupération du payload de queue */
        $queue = $this->queueRepository->findByUlid($ulid);
        if ($queue === null) {
            $io->error(sprintf(
                "Aucun payload en queue pour ulid=%s. Vérifie qu'il n'a pas été purgé (rétention 30j).",
                $ulid
            ));
            return Command::FAILURE;
        }

        $group    = $queue->getProjectGroup();
        $artifact = $queue->getProjectArtifact();
        $version  = $queue->getProjectVersion();

        $io->section(sprintf(
            'Re-traitement DC : %s:%s:%s%s',
            $group, $artifact, $version,
            $dryRun ? ' [DRY-RUN]' : ''
        ));

        /* 2. Recherche du scan existant correspondant */
        $existing = $this->scanRepository->findLatestForProject($group, $artifact, $version);
        if ($existing === null) {
            $io->note("Aucun scan existant trouvé pour ce triplet. L'ingestion va créer un nouveau scan.");
        } else {
            $io->definitionList(
                ['Scan existant id'    => (string) $existing->getId()],
                ['Date du scan'        => $existing->getScanDate()->format('Y-m-d H:i')],
                ['Engine OWASP DC'     => $existing->getEngineVersion() ?? '-'],
                ['Dépendances total'   => (string) $existing->getDepCountTotal()],
                ['Dépendances vuln.'   => (string) $existing->getDepCountVulnerable()],
                ['CVE total'           => (string) $existing->getCveCountTotal()],
            );

            /* 3. Garde-fous : confirmation explicite avant DELETE */
            if (!$force && !$dryRun) {
                $io->warning([
                    'Cette commande va SUPPRIMER le scan existant et ses findings (CASCADE),',
                    'puis recréer le scan en re-parsant le payload depuis la queue.',
                    'Re-lance avec --force pour confirmer, ou --dry-run pour simuler.',
                ]);
                return Command::FAILURE;
            }

            if ($dryRun) {
                $io->warning(sprintf(
                    '[DRY-RUN] Le scan id=%d serait supprimé (CASCADE sur findings) puis ré-ingéré.',
                    $existing->getId()
                ));
                return Command::SUCCESS;
            }

            /* 4. DELETE du scan (CASCADE supprime aussi les findings via FK) */
            $oldScanId = (int) $existing->getId();
            $this->em->remove($existing);
            $this->em->flush();
            $io->info(sprintf('Scan id=%d supprimé (CASCADE sur findings).', $oldScanId));
        }

        if ($dryRun) {
            $io->note('Aucun scan existant à supprimer. Avec --force (sans --dry-run) un nouveau scan serait ingéré.');
            return Command::SUCCESS;
        }

        /* 5. Ré-ingestion via l'Ingester (qui parse le payload de la queue) */
        try {
            $newScan = $this->ingester->ingest($queue);
        } catch (\Throwable $e) {
            $io->error('Échec de la ré-ingestion : ' . $e->getMessage());
            $this->logger->error('[DC-Reprocess] Échec.', [
                'ulid'  => $ulid,
                'group' => $group, 'artifact' => $artifact, 'version' => $version,
                'error' => $e->getMessage(),
            ]);
            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Scan ré-ingéré : id=%d, %d dépendances vulnérables / %d total, %d CVE.',
            $newScan->getId(),
            $newScan->getDepCountVulnerable(),
            $newScan->getDepCountTotal(),
            $newScan->getCveCountTotal()
        ));

        $this->logger->info('[DC-Reprocess] OK.', [
            'ulid'        => $ulid,
            'group'       => $group, 'artifact' => $artifact, 'version' => $version,
            'new_scan_id' => (int) $newScan->getId(),
        ]);

        return Command::SUCCESS;
    }
}
