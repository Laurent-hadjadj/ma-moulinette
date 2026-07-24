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

namespace App\Command\Dev;

use App\Controller\Batch\CollecteController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Lance une collecte complète (les 14 étapes de CollecteController::collecte,
 * dont Actuator) pour UN SEUL projet, en direct, sans passer par la file de
 * traitement manuel (/traitement/suivi, BatchManuelController) ni par un
 * appel HTTP à app:collecte:run — pratique pour tester/déboguer rapidement
 * une étape (ex. Actuator) sur un projet donné.
 *
 * Usage :
 *   php bin/console app:collecte:run-one fr.ma-moulinette:mon-projet
 *   php bin/console app:collecte:run-one fr.ma-moulinette:mon-projet --portefeuille=Test
 */
#[AsCommand(
    name: 'app:collecte:run-one',
    description: 'Lance une collecte complète pour un seul projet, sans passer par la file de traitement manuel (dev uniquement).',
)]
class CollecteRunOneCommand extends Command
{
    public function __construct(
        private readonly CollecteController $collecte,
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.environment%')]
        private readonly string $appEnv,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('maven-key', InputArgument::REQUIRED, 'Clé Maven du projet (ex. fr.ma-moulinette:mon-projet).')
            ->addOption('portefeuille', null, InputOption::VALUE_REQUIRED, 'Nom du portefeuille (purement informatif, affiché dans le compte-rendu).', 'dev-cli')
            ->addOption('utilisateur', null, InputOption::VALUE_REQUIRED, 'Courriel enregistré comme utilisateur_collecte.', 'dev-cli@ma-moulinette.fr');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /* MODIF 2026-07-23 : réservé à env=dev, même garde-fou que les autres
         * commandes de App\Command\Dev — déclenche de vrais appels réseau
         * (SonarQube, Actuator...) et écrit en base. */
        if ($this->appEnv !== 'dev') {
            $io->error(sprintf('Cette commande est réservée à env=dev (env courant : %s).', $this->appEnv));
            return Command::FAILURE;
        }

        $mavenKey = (string) $input->getArgument('maven-key');
        $portefeuille = (string) $input->getOption('portefeuille');
        $utilisateur = (string) $input->getOption('utilisateur');

        $io->section(sprintf('Collecte pour %s', $mavenKey));

        $result = $this->collecte->collecte($portefeuille, $mavenKey, 'collecte', $utilisateur);

        if (($result['code'] ?? null) !== 200) {
            $io->error(sprintf('Échec (code %s).', $result['code'] ?? '?'));
            if (!empty($result['compte_rendu'])) {
                $io->writeln(strip_tags($result['compte_rendu']));
            }
            return Command::FAILURE;
        }

        $io->success('Collecte terminée et enregistrée dans la table historique.');

        /** Relit directement la dernière ligne historique pour afficher actuator_info sans détour. */
        $row = $this->em->getConnection()->fetchAssociative(
            'SELECT actuator_info FROM ma_moulinette.historique WHERE maven_key = :maven_key ORDER BY date_enregistrement DESC LIMIT 1',
            ['maven_key' => $mavenKey]
        );

        $io->section('Contenu de historique.actuator_info (dernière ligne)');
        $io->writeln($row['actuator_info'] ?? '(aucune ligne historique trouvée)');

        return Command::SUCCESS;
    }
}
