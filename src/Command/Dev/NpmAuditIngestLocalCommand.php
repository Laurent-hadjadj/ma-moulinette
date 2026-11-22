<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Command\Dev;

use App\Entity\NpmProcessingQueue;
use App\Exception\UnexpectedExecutionPathException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Ulid;

/**
 * Commande locale d'injection dans la queue d'un rapport npm audit, pour rejouer un fichier
 * `npm audit --json` sans passer par un vrai upload HTTP/token CI — même
 * rôle que DcIngestLocalCommand.
 *
 * Contrairement à DC, npm audit --json ne porte aucune information projet
 * (pas de projectInfo) : group/artifact/version sont donc des options
 * requises de la commande, pas lues depuis le JSON.
 *
 * Le fichier donné est le rapport "full" brut (sortie de `npm audit
 * --json`), enveloppé automatiquement en {"full": {...}}. --prod-only
 * ajoute un second fichier (sortie de `npm audit --omit=dev --json`) dans
 * la même enveloppe, pour tester le croisement isProd. --installed ajoute
 * un troisième fichier optionnel (sortie de `npm ls --all --json`) pour
 * tester la résolution de la version réellement installée.
 *
 * Usage :
 *   bin/console app:npm-audit:ingest-local var/failles-angular.json --group=fr.ma-moulinette --artifact=angular-demo --project-version=1.0.0
 *   bin/console app:npm-audit:ingest-local var/failles-angular.json --group=... --artifact=... --project-version=... --prod-only=var/failles-angular-prod.json --installed=var/audit-ls-all.json
 *
 * Après enqueue, lancer le worker pour ingérer :
 *   bin/console app:npm-audit:process --batch=10 --max-batches=1
 */
#[AsCommand(
    name: 'app:npm-audit:ingest-local',
    description: "Enqueue un rapport npm audit depuis un fichier local (sans upload HTTP).",
)]
class NpmAuditIngestLocalCommand extends Command
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
        $this
            ->addArgument('path', InputArgument::REQUIRED, 'Chemin vers le rapport npm audit --json (rapport "full").')
            ->addOption('group', null, InputOption::VALUE_REQUIRED, 'X-Project-Group.')
            ->addOption('artifact', null, InputOption::VALUE_REQUIRED, 'X-Project-Artifact.')
            ->addOption('project-version', null, InputOption::VALUE_REQUIRED, 'X-Project-Version.')
            ->addOption('prod-only', null, InputOption::VALUE_REQUIRED, 'Chemin vers le rapport npm audit --omit=dev --json (optionnel).', null)
            ->addOption('installed', null, InputOption::VALUE_REQUIRED, 'Chemin vers le rapport npm ls --all --json (optionnel, résolution de la version installée).', null);
    }

    /**
     * Lit, décode et valide un fichier JSON optionnel (prod-only/installed) —
     * factorisé pour éviter de tripler la même logique. `$failed` distingue
     * "option absente" (retour null, `$failed` reste false, l'appelant
     * continue normalement) de "fichier fourni mais invalide" (retour null,
     * `$failed` passe à true, message déjà écrit sur `$io`, l'appelant doit
     * sortir en FAILURE).
     *
     * @return array<string, mixed>|null
     */
    private function readOptionalJsonFile(SymfonyStyle $io, mixed $path, string $label, bool &$failed): array|null
    {
        $failed = false;
        if (!is_string($path) || $path === '') {
            return null;
        }
        if (!is_readable($path)) {
            $io->error("Fichier {$label} introuvable ou non lisible : {$path}");
            $failed = true;
            return null;
        }
        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            $io->error("Fichier {$label} vide ou illisible : {$path}");
            $failed = true;
            return null;
        }
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $io->error('JSON invalide dans ' . $path . ' : ' . $e->getMessage());
            $failed = true;
            return null;
        }
        if (!is_array($decoded)) {
            $io->error('JSON racine doit être un objet : ' . $path);
            $failed = true;
            return null;
        }
        $io->note(ucfirst($label) . ' inclus : ' . $path);
        return $decoded;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->appEnv !== 'dev') {
            $io->error(sprintf('❌ Cette commande est réservée à env=dev (env courant : %s).', $this->appEnv));
            return Command::FAILURE;
        }

        $path     = (string) $input->getArgument('path');
        $group    = trim((string) $input->getOption('group'));
        $artifact = trim((string) $input->getOption('artifact'));
        $version  = trim((string) $input->getOption('project-version'));
        $prodOnlyPath = $input->getOption('prod-only');
        $installedPath = $input->getOption('installed');

        if ($group === '' || $artifact === '' || $version === '') {
            $io->error('Les options --group, --artifact et --project-version sont requises.');
            return Command::FAILURE;
        }

        if (!is_readable($path)) {
            $io->error("Fichier introuvable ou non lisible : {$path}");
            return Command::FAILURE;
        }

        $fullRaw = file_get_contents($path);
        if ($fullRaw === false || $fullRaw === '') {
            $io->error("Fichier vide ou illisible : {$path}");
            return Command::FAILURE;
        }

        try {
            $full = json_decode($fullRaw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $io->error('JSON invalide dans ' . $path . ' : ' . $e->getMessage());
            return Command::FAILURE;
        }
        if (!is_array($full)) {
            $io->error('JSON racine doit être un objet : ' . $path);
            return Command::FAILURE;
        }

        $envelope = ['full' => $full];

        $prodOnlyFailed = false;
        $prodOnly = $this->readOptionalJsonFile($io, $prodOnlyPath, 'prod-only', $prodOnlyFailed);
        if ($prodOnlyFailed) {
            return Command::FAILURE;
        }
        if ($prodOnly !== null) {
            $envelope['prodOnly'] = $prodOnly;
        }

        $installedFailed = false;
        $installed = $this->readOptionalJsonFile($io, $installedPath, 'installed', $installedFailed);
        if ($installedFailed) {
            return Command::FAILURE;
        }
        if ($installed !== null) {
            $envelope['installed'] = $installed;
        }

        $envelopeJson = json_encode($envelope, JSON_THROW_ON_ERROR);
        $payloadGz = gzencode($envelopeJson, 6);
        if ($payloadGz === false) {
            throw new UnexpectedExecutionPathException('Échec de la compression gzip du payload.');
        }

        $entry = (new NpmProcessingQueue())
            ->setUlid((new Ulid())->toBase32())
            ->setPayloadGz($payloadGz)
            ->setPayloadSha256(hash('sha256', $envelopeJson))
            ->setPayloadSize(strlen($envelopeJson))
            ->setContentType(NpmProcessingQueue::CONTENT_JSON)
            ->setProjectGroup($group)
            ->setProjectArtifact($artifact)
            ->setProjectVersion($version);

        $this->em->persist($entry);
        $this->em->flush();

        $io->success(sprintf(
            '%s → %s:%s:%s (ulid=%s)',
            basename($path),
            $group,
            $artifact,
            $version,
            $entry->getUlid()
        ));
        $io->note('Lancer ensuite : bin/console app:npm-audit:process --batch=1 --max-batches=1');

        return Command::SUCCESS;
    }
}
