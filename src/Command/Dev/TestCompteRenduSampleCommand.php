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

/* MODIF 2026-05-21 : déplacé dans App\Command\Dev. */
namespace App\Command\Dev;

use App\Entity\BatchExecutionJournal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * [Description TestCompteRenduSampleCommand]
 */
#[AsCommand(
    name: 'app:test-compte-rendu',
    description: 'Test de lecture et compression des 10 premiers compte_rendu de BatchExecutionJournal'
)]
class TestCompteRenduSampleCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.environment%')]
        private readonly string $appEnv,
    ) {
        parent::__construct();
    }

    /**
     * [Description for execute]
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * Created at: 12/11/2025 19:52:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // MODIF 2026-05-21 : garde-fou env=dev (test lecture compte_rendu).
        $io = new SymfonyStyle($input, $output);
        if ($this->appEnv !== 'dev') {
            $io->error(sprintf('❌ Cette commande est réservée à env=dev (env courant : %s).', $this->appEnv));
            return Command::FAILURE;
        }

        $repo = $this->em->getRepository(BatchExecutionJournal::class);

        $output->writeln('<info>🔍 Test des 10 premiers enregistrements de BatchExecutionJournal</info>');
        $items = $repo->createQueryBuilder('b')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        if (count($items) === 0) {
            $output->writeln('<comment>Aucun enregistrement trouvé.</comment>');
            return Command::SUCCESS;
        }

        foreach ($items as $item) {
            $id = $item->getId();
            $raw = $item->getCompteRenduBrut();

            // Si le champ est un flux (BYTEA)
            if (is_resource($raw)) {
                $binary = stream_get_contents($raw);
                fclose($raw);
            } else {
                $binary = $raw;
            }

            if ($binary === null || $binary === false || $binary === '') {
                $output->writeln("⚠️  ID {$id} : vide");
                continue;
            }

            // Vérification signature GZIP
            $isGz = (substr($binary, 0, 2) === "\x1f\x8b");

            // Taille brute
            $size = strlen($binary);

            // Tentative de décompression si gz
            $status = $isGz ? '✅ Compressé' : '❌ Non compressé';
            $htmlPreview = '';

            if ($isGz) {
                $decoded = @gzdecode($binary);
                if ($decoded === false) {
                    $status = '⚠️ Corrompu (gzdecode échoué)';
                } else {
                    // On extrait juste les 100 premiers caractères du HTML
                    $htmlPreview = substr(strip_tags($decoded), 0, 100) . '...';
                }
            } else {
                $htmlPreview = substr(strip_tags($binary), 0, 100) . '...';
            }

            $output->writeln("ID {$id} : {$status} | Taille : {$size} o");
            $output->writeln("     Aperçu : " . $htmlPreview);
        }

        $output->writeln('<info>✅ Test terminé.</info>');
        return Command::SUCCESS;
    }
}
