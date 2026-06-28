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

/* MODIF 2026-05-21 : déplacé dans App\Command\Maintenance. */
namespace App\Command\Maintenance;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

/**
 * MODIF 2026-05-15 : commande verify qui détecte
 * les drifts JS↔Controller↔SQL dans la chaîne d'enregistrement historique.
 *
 * Pour une maven_key donnée, lit la dernière ligne `historique` et classe
 * chaque colonne en 3 catégories :
 *   - NULL  : ❌ drift certain (le payload n'a pas transmis la valeur)
 *   - ZERO  : ⚠️ fallback silencieux probable (0 INT, 0.0 DOUBLE, "" STRING)
 *   - OK    : ✅ valeur présente
 *
 * Utilisation typique en cycle :
 *   1. php bin/console app:seed:projet-fixture
 *   2. Peinture + Enregistrement via UI
 *   3. php bin/console app:verify:historique fr.test:projet-fixture
 *      → liste les colonnes NULL ou ZERO à investiguer.
 *
 * Colonnes EXCLUES de l'audit (légitimement nullable/zero par design) :
 *   - id, initial, date_enregistrement, mode_collecte, utilisateur_collecte
 *   - actuator_info (null si pas de remontée actuator)
 *   - logger_breakdown (null si plugin v1.x)
 */
#[AsCommand(
    name: 'app:verify:historique',
    description: "Détecte les drifts JS<->Controller<->SQL : liste les colonnes NULL ou 0 dans la dernière ligne historique d'une maven_key.",
)]
class VerifyHistoriqueCommand extends Command
{
    private const DEFAULT_MAVEN_KEY = 'fr.test:projet-fixture';

    /**
     * Colonnes exclues de l'audit "drift" car légitimement nullable/zero.
     */
    private const EXCLUDED_COLS = [
        'id', 'initial', 'date_enregistrement', 'mode_collecte', 'utilisateur_collecte',
        'actuator_info',     // null si pas d'actuator remonté
        'logger_breakdown',  // null si plugin v1.x
        'date_modification',
    ];

    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'maven_key',
            InputArgument::OPTIONAL,
            'Maven key à auditer (default : fr.test:projet-fixture)',
            self::DEFAULT_MAVEN_KEY
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
     * Created at: 15/05/2026 08:06:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mavenKey = (string) $input->getArgument('maven_key');
        $conn = $this->em->getConnection();

        $output->writeln('');
        $output->writeln('====================================================================');
        $output->writeln(" Audit historique : $mavenKey");
        $output->writeln('====================================================================');

        // Récupération de la dernière ligne historique pour ce projet
        $row = $conn->fetchAssociative(<<<SQL
            SELECT *
            FROM ma_moulinette.historique
            WHERE maven_key = :mk
            ORDER BY date_enregistrement DESC
            LIMIT 1
        SQL, ['mk' => $mavenKey]);

        if ($row === false) {
            $output->writeln('');
            $output->writeln(" ❌ Aucune ligne historique trouvée pour cette maven_key.");
            $output->writeln(' (Vérifie que tu as bien lancé peinture + enregistrement après le seed.)');
            return Command::FAILURE;
        }

        // Récupération des metadata de colonnes (data_type, is_nullable) pour
        // formater les zéros selon le type natif.
        $colMeta = $conn->fetchAllAssociative(<<<SQL
            SELECT column_name, data_type, is_nullable
            FROM information_schema.columns
            WHERE table_schema = 'ma_moulinette' AND table_name = 'historique'
            ORDER BY ordinal_position
        SQL);
        $typeByCol = [];
        foreach ($colMeta as $m) {
            $typeByCol[$m['column_name']] = $m['data_type'];
        }

        $nullCols = [];
        $zeroCols = [];
        $okCols   = [];
        $excluded = [];

        foreach ($row as $col => $val) {
            if (in_array($col, self::EXCLUDED_COLS, true)) {
                $excluded[] = $col;
                continue;
            }

            if ($val === null) {
                $nullCols[] = $col;
                continue;
            }

            // Détection "zero/empty" selon type natif Postgres
            $type = $typeByCol[$col] ?? '';
            $isZero = false;
            if (in_array($type, ['integer', 'bigint', 'smallint'], true)) {
                $isZero = ((int) $val) === 0;
            } elseif (in_array($type, ['double precision', 'numeric', 'real'], true)) {
                $isZero = ((float) $val) === 0.0;
            } elseif (str_contains($type, 'char') || $type === 'text') {
                $isZero = $val === '' || $val === '0';
            }
            // Pour boolean / jsonb / timestamp, on ne signale pas le "zero".

            if ($isZero) {
                $zeroCols[] = $col;
            } else {
                $okCols[] = $col;
            }
        }

        $total = count($row);
        $output->writeln('');
        $output->writeln(sprintf(' Total colonnes auditées : %d (excluded: %d)', $total - count($excluded), count($excluded)));
        $output->writeln(sprintf('   ✅ OK   : %d', count($okCols)));
        $output->writeln(sprintf('   ⚠️ ZERO : %d', count($zeroCols)));
        $output->writeln(sprintf('   ❌ NULL : %d', count($nullCols)));
        $output->writeln('');

        if ($nullCols !== []) {
            $output->writeln(' ❌ Colonnes NULL (drift certain — chaîne JS<->Controller<->SQL cassée) :');
            foreach ($nullCols as $c) {
                $output->writeln("     - $c  ({$typeByCol[$c]})");
            }
            $output->writeln('');
        }

        if ($zeroCols !== []) {
            $output->writeln(' ⚠️ Colonnes a ZERO/EMPTY (fallback silencieux probable, à verifier) :');
            foreach ($zeroCols as $c) {
                $output->writeln("     - $c  ({$typeByCol[$c]})");
            }
            $output->writeln('');
        }

        if ($nullCols === [] && $zeroCols === []) {
            $output->writeln(' 🎉 Aucune colonne a NULL ou ZERO. La chaîne est propre.');
            return Command::SUCCESS;
        }

        $output->writeln(' --------------------------------------------------------------------');
        $output->writeln(' Prochain pas : pour chaque colonne listée ci-dessus, vérifier :');
        $output->writeln('   1. La source de la donnée (table de collecte associée)');
        $output->writeln('   2. Si elle remonte cote API peinture (peintureProjetMesures + autres)');
        $output->writeln('   3. Si elle est dans window.peintureData ou hardcodé dans enregistrement.js');
        $output->writeln('   4. Si elle est dans la whitelist $historiqueColumns du repo');
        $output->writeln(' --------------------------------------------------------------------');

        return $nullCols !== [] ? Command::FAILURE : Command::SUCCESS;
    }
}
