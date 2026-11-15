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

use App\Controller\Accueil\ApiAccueilController;
use App\Controller\Batch\CollecteController;
use App\Controller\Profil\ApiProfilController;
use App\Entity\ListeProjet;
use App\Repository\ListeProjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{ArrayInput, InputInterface, InputOption};
use Symfony\Component\Console\Output\{BufferedOutput, OutputInterface};
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * Alimente la base de PRÉPARATION de l'instance de démonstration (docker/demo/docker-compose.prepa.yml) :
 *   1. rafraîchit la liste des projets visibles par le compte SonarQube de lecture (liste_projet) ;
 *   2. rafraîchit les profils de qualité ;
 *   3. pour chaque projet dont un tag commence par le préfixe demandé : collecte complète puis reconstruction de
 *      l'historique par version.
 * L'export vers docker/demo/donnees/demo.sql.gz est fait par docker/demo/preparer-donnees.sh.
 *
 * Garde-fous : environnement `dev` uniquement ET connexion obligatoirement dirigée vers la pile de préparation
 * (hôte local, port {@see self::PORT_PREPARATION}). Sans cela la commande refuse de s'exécuter : elle vide et réécrit
 * des tables, elle ne doit jamais atteindre une base de développement partagée ni de production.
 *
 * Usage :
 *   php bin/console app:demo:preparer
 *   php bin/console app:demo:preparer --projet=fr.ma.moulinette:track-logger-method
 *   php bin/console app:demo:preparer --tag=ma-moulinette --sans-historique
 */
#[AsCommand(
    name: 'app:demo:preparer',
    description: 'Collecte les projets de démonstration dans la base de préparation (dev uniquement, base dédiée).',
)]
class DemoPreparerCommand extends Command
{
    /** Port publié (127.0.0.1) par docker/demo/docker-compose.prepa.yml : seule base autorisée. */
    public const PORT_PREPARATION = 5435;

    /** Courriel enregistré comme utilisateur_collecte (information seulement). */
    public const COURRIEL_COLLECTE = 'demo@ma-moulinette.fr';

    private const HOTES_LOCAUX = ['127.0.0.1', 'localhost', '::1'];

    /** @var array<string, string> cause du dernier échec par projet (affichée au compte-rendu) */
    private array $messages = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ApiAccueilController $accueil,
        private readonly ApiProfilController $profil,
        private readonly CollecteController $collecte,
        private readonly TokenStorageInterface $tokenStorage,
        #[Autowire('%kernel.environment%')]
        private readonly string $appEnv,
    ) {
        parent::__construct();
    }

    /**
     * La connexion pointe-t-elle vers la pile de préparation (hôte local et port dédié) ?
     *
     * @param array<string, mixed> $params paramètres de connexion DBAL (host, port)
     */
    public static function baseAutorisee(array $params): bool
    {
        $hote = strtolower((string) ($params['host'] ?? ''));
        $port = (int) ($params['port'] ?? 0);

        return in_array($hote, self::HOTES_LOCAUX, true) && $port === self::PORT_PREPARATION;
    }

    /**
     * Garde les projets demandés (tous si aucune clé n'est donnée) et signale les clés introuvables.
     *
     * @param array<int, array{id: string, text?: string}> $liste projets de la liste (clé Maven dans « id »)
     * @param array<int, string>                           $cles  clés demandées (option --projet)
     *
     * @return array{retenus: array<int, string>, inconnus: array<int, string>}
     */
    public static function retenirProjets(array $liste, array $cles): array
    {
        $disponibles = array_values(array_unique(array_map(static fn (array $p): string => (string) $p['id'], $liste)));
        sort($disponibles);

        if ($cles === []) {
            return ['retenus' => $disponibles, 'inconnus' => []];
        }

        $cles = array_values(array_unique($cles));
        $retenus = array_values(array_intersect($disponibles, $cles));
        $inconnus = array_values(array_diff($cles, $disponibles));

        return ['retenus' => $retenus, 'inconnus' => $inconnus];
    }

    protected function configure(): void
    {
        $this
            ->addOption('tag', null, InputOption::VALUE_REQUIRED, 'Préfixe de tag SonarQube des projets à collecter (groupe fonctionnel).', 'ma-moulinette')
            ->addOption('projet', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Clé Maven d\'un projet à collecter (répétable). Défaut : tous ceux du tag.')
            ->addOption('sans-historique', null, InputOption::VALUE_NONE, 'Ne pas reconstruire l\'historique par version.')
            ->addOption('liste-seulement', null, InputOption::VALUE_NONE, 'Rafraîchit les projets et profils, affiche les projets retenus, ne collecte rien.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->appEnv !== 'dev') {
            $io->error(sprintf('Commande réservée à env=dev (env courant : %s).', $this->appEnv));
            return Command::FAILURE;
        }

        $params = $this->em->getConnection()->getParams();
        if (!self::baseAutorisee($params)) {
            $io->error(sprintf(
                "Connexion refusée : cette commande n'écrit que dans la pile de préparation (hôte local, port %d). "
                . 'Connexion courante : %s:%s. Lancez-la par docker/demo/preparer-donnees.sh.',
                self::PORT_PREPARATION,
                (string) ($params['host'] ?? '?'),
                (string) ($params['port'] ?? '?')
            ));
            return Command::FAILURE;
        }

        $tag = (string) $input->getOption('tag');
        /** @var array<int, string> $demandes */
        $demandes = (array) $input->getOption('projet');
        $erreurs = 0;

        $io->title('Préparation des données de démonstration');

        $io->section('1. Liste des projets visibles par le compte SonarQube');
        $rep = $this->decoder($this->accueil->accueilProjetListe());
        if (($rep['code'] ?? 0) !== 200) {
            $io->error(sprintf('Échec de la liste des projets : %s', (string) ($rep['message'] ?? 'réponse invalide')));
            return Command::FAILURE;
        }
        $io->writeln(sprintf(
            '%s projet(s) : %s public(s), %s privé(s), %s sans tag.',
            (string) ($rep['nombre'] ?? '?'),
            (string) ($rep['public'] ?? '?'),
            (string) ($rep['private'] ?? '?'),
            (string) ($rep['empty_tags'] ?? '?')
        ));

        $io->section('2. Profils de qualité');
        if (!$this->rafraichirProfils()) {
            $io->warning('Les profils de qualité n\'ont pas pu être rafraîchis (voir les journaux).');
            ++$erreurs;
        } else {
            $io->writeln('Profils de qualité enregistrés.');
        }

        $io->section(sprintf('3. Projets dont un tag commence par « %s »', $tag));
        /** @var ListeProjetRepository $repo */
        $repo = $this->em->getRepository(ListeProjet::class);
        $prefixes = ListeProjetRepository::normalizeGroupesToTagPrefixes([$tag]);
        $selection = $repo->selectListeProjetByGroupe($prefixes);
        if (($selection['code'] ?? 0) !== 200) {
            $io->error('Lecture de la liste des projets impossible.');
            return Command::FAILURE;
        }
        /** @var array<int, array{id: string, text?: string}> $liste */
        $liste = (array) ($selection['liste'] ?? []);
        $choix = self::retenirProjets($liste, $demandes);

        foreach ($choix['inconnus'] as $inconnu) {
            $io->warning(sprintf('Projet demandé introuvable sous le tag « %s » : %s', $tag, $inconnu));
            ++$erreurs;
        }
        if ($choix['retenus'] === []) {
            $io->error('Aucun projet à collecter.');
            return Command::FAILURE;
        }
        $io->listing($choix['retenus']);

        if ($input->getOption('liste-seulement')) {
            $io->note('Option --liste-seulement : aucune collecte.');
            return $erreurs === 0 ? Command::SUCCESS : Command::FAILURE;
        }

        $io->section('4. Collecte et historique');
        $lignes = [];
        foreach ($choix['retenus'] as $cle) {
            $io->writeln(sprintf('<info>%s</info>', $cle));
            $etatCollecte = $this->collecter($cle);
            $collecteOk = $etatCollecte !== null;
            $historiqueOk = null;
            if ($collecteOk && !$input->getOption('sans-historique')) {
                $historiqueOk = $this->reconstruireHistorique($cle);
            }
            if (!$collecteOk || $historiqueOk === false) {
                ++$erreurs;
            }
            $lignes[] = [
                $cle,
                $etatCollecte ?? 'ÉCHEC',
                $historiqueOk === null ? '-' : ($historiqueOk ? 'OK' : 'ÉCHEC'),
                (string) $this->compterHistorique($cle),
            ];
        }

        $io->table(['Projet', 'Collecte', 'Historique', 'Lignes historique'], $lignes);
        foreach ($this->messages as $cle => $message) {
            $io->writeln(sprintf('<error> %s </error> %s', $cle, $message));
        }

        if ($erreurs > 0) {
            $io->error(sprintf('%d étape(s) en échec : vérifier les journaux avant d\'exporter.', $erreurs));
            return Command::FAILURE;
        }
        $io->success('Base de préparation alimentée. Exporter avec docker/demo/preparer-donnees.sh.');
        return Command::SUCCESS;
    }

    /**
     * Les profils exigent ROLE_GESTIONNAIRE (contrôle interne du contrôleur) : jeton temporaire, restauré ensuite.
     */
    private function rafraichirProfils(): bool
    {
        $precedent = $this->tokenStorage->getToken();
        $roles = ['ROLE_GESTIONNAIRE'];
        $this->tokenStorage->setToken(
            new UsernamePasswordToken(new InMemoryUser('preparation-demo', null, $roles), 'main', $roles)
        );
        try {
            $rep = $this->decoder($this->profil->listeQualityProfiles());
        } finally {
            $this->tokenStorage->setToken($precedent);
        }

        return ($rep['code'] ?? 0) === 200 && ($rep['type'] ?? '') !== 'error';
    }

    /**
     * @return string|null « OK » (collecté), « À JOUR » (code 202 : l'analyse locale est déjà celle de SonarQube, la
     *                     collecte s'arrête volontairement) ou null en cas d'échec
     */
    private function collecter(string $cle): ?string
    {
        try {
            $resultat = $this->collecte->collecte('demo', $cle, 'collecte', self::COURRIEL_COLLECTE);
        } catch (\Throwable $e) {
            $this->messages[$cle] = 'collecte : ' . $e->getMessage();
            return null;
        }

        $code = $resultat['code'] ?? null;
        if ($code === 202) {
            return 'À JOUR';
        }
        if ($code !== 200) {
            $this->messages[$cle] = sprintf('collecte : code %s', (string) ($code ?? '?'));
            return null;
        }

        return 'OK';
    }

    private function reconstruireHistorique(string $cle): bool
    {
        $application = $this->getApplication();
        if ($application === null) {
            return false;
        }

        $entree = new ArrayInput(['--project' => $cle]);
        $entree->setInteractive(false);

        $sortie = new BufferedOutput();
        try {
            $code = $application->find('app:historique:rebuild')->run($entree, $sortie);
        } catch (\Throwable $e) {
            $this->messages[$cle] = 'historique : ' . $e->getMessage();
            return false;
        }

        if ($code !== Command::SUCCESS) {
            $this->messages[$cle] = 'historique : ' . trim(strip_tags($sortie->fetch()));
            return false;
        }

        return true;
    }

    private function compterHistorique(string $cle): int
    {
        return (int) $this->em->getConnection()->fetchOne(
            'SELECT count(*) FROM ma_moulinette.historique WHERE maven_key = :cle',
            ['cle' => $cle]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decoder(JsonResponse $reponse): array
    {
        $donnees = json_decode((string) $reponse->getContent(), true);

        return is_array($donnees) ? $donnees : [];
    }
}
