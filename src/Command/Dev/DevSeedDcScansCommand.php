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

use App\Entity\{DcCve, DcDependency, DcFinding, DcScan, ListeProjet};
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * MODIF 2026-05-11 : commande dev pour générer des
 * scans DependencyCheck plausibles afin d'alimenter la base et tester les
 * fragments analytics du dashboard (cartographie risque, top deps
 * mutualisables, apparitions récentes).
 *
 * Réservée env=dev (abort sinon). Choix :
 *  - on persiste directement les entities, sans passer par DependencyCheckIngester
 *    (qui attend un JSON DependencyCheck format) -> plus simple, plus rapide
 *  - distribution sévérité plausible : ~10% CRITICAL, 25% HIGH, 45% MEDIUM, 20% LOW
 *  - scan_date échelonne sur les 30 derniers jours -> permet de tester les
 *    fragments temporels (apparitions récentes, vélocité)
 *  - mutualisation : certaines deps réapparaissent dans plusieurs scans, ce
 *    qui rend le fragment "top deps mutualisables" testable
 *
 * Exemples :
 *   php bin/console app:dev:seed-dc-scans                  -> 10 scans
 *   php bin/console app:dev:seed-dc-scans --count=20       -> 20 scans
 *   php bin/console app:dev:seed-dc-scans --clean          -> truncate + 10 scans
 */
#[AsCommand(
    name: 'app:dev:seed-dc-scans',
    description: 'Génère des scans DC plausibles (env=dev) pour alimenter le dashboard analytics.',
)]
class DevSeedDcScansCommand extends Command
{
    /** Pool de dépendances réelles connues vulnérables (CVE historiques). */
    private const POOL_DEPENDENCIES = [
        ['vendor' => 'org.apache.logging.log4j', 'product' => 'log4j-core',          'version' => '2.14.0',  'file' => 'log4j-core-2.14.0.jar'],
        ['vendor' => 'com.fasterxml.jackson.core', 'product' => 'jackson-databind',  'version' => '2.9.10',  'file' => 'jackson-databind-2.9.10.jar'],
        ['vendor' => 'org.springframework',       'product' => 'spring-core',        'version' => '5.2.0',   'file' => 'spring-core-5.2.0.jar'],
        ['vendor' => 'org.springframework',       'product' => 'spring-webmvc',      'version' => '5.2.3',   'file' => 'spring-webmvc-5.2.3.jar'],
        ['vendor' => 'com.rabbitmq',              'product' => 'amqp-client',        'version' => '5.9.0',   'file' => 'amqp-client-5.9.0.jar'],
        ['vendor' => 'commons-collections',       'product' => 'commons-collections','version' => '3.2.1',   'file' => 'commons-collections-3.2.1.jar'],
        ['vendor' => 'commons-fileupload',        'product' => 'commons-fileupload', 'version' => '1.3.2',   'file' => 'commons-fileupload-1.3.2.jar'],
        ['vendor' => 'org.apache.tomcat',         'product' => 'tomcat-embed-core',  'version' => '9.0.30',  'file' => 'tomcat-embed-core-9.0.30.jar'],
        ['vendor' => 'org.apache.struts',         'product' => 'struts2-core',       'version' => '2.5.20',  'file' => 'struts2-core-2.5.20.jar'],
        ['vendor' => 'org.hibernate',             'product' => 'hibernate-core',     'version' => '5.4.10',  'file' => 'hibernate-core-5.4.10.jar'],
        ['vendor' => 'org.bouncycastle',          'product' => 'bcprov-jdk15on',     'version' => '1.64',    'file' => 'bcprov-jdk15on-1.64.jar'],
        ['vendor' => 'com.google.guava',          'product' => 'guava',              'version' => '24.1.1',  'file' => 'guava-24.1.1-jre.jar'],
        ['vendor' => 'org.yaml',                  'product' => 'snakeyaml',          'version' => '1.27',    'file' => 'snakeyaml-1.27.jar'],
        ['vendor' => 'org.apache.commons',        'product' => 'commons-text',       'version' => '1.9',     'file' => 'commons-text-1.9.jar'],
        ['vendor' => 'io.netty',                  'product' => 'netty-handler',      'version' => '4.1.50',  'file' => 'netty-handler-4.1.50.Final.jar'],
    ];

    /** Pool de CWE communes pour les findings. */
    private const POOL_CWES = [
        'CWE-79',   // XSS
        'CWE-89',   // SQL Injection
        'CWE-22',   // Path Traversal
        'CWE-78',   // OS Command Injection
        'CWE-352',  // CSRF
        'CWE-787',  // Out-of-bounds Write
        'CWE-400',  // Resource Consumption
        'CWE-502',  // Deserialization
        'CWE-611',  // XXE
        'CWE-918',  // SSRF
        'CWE-94',   // Code Injection
        'CWE-285',  // Improper Authorization
    ];

    /** MODIF 2026-05-11 : pool de CVE-IDs reels recycles
     *  entre scans. AVANT : chaque finding générait un cve_id unique
     *  (sprintf '...-seed%d-%d') -> impossible que la meme CVE touche
     *  plusieurs projets -> findCriticalCvesAcrossProjects retournait toujours
     *  vide. MAINTENANT : on pioche dans ce pool fixe, et on réutilise la
     *  DcCve existante via cache local + lookup DB. La sévérité reste tirée
     *  aléatoirement et appliquée à la première création seulement.
     *
     *  Liste = CVEs réelles celebres pour rester plausible dans les seeds dev. */
    private const POOL_CVE_IDS = [
        'CVE-2021-44228',  // Log4Shell
        'CVE-2021-45046',  // Log4Shell - patch incomplet
        'CVE-2022-22965',  // Spring4Shell
        'CVE-2022-22963',  // Spring Cloud Function
        'CVE-2022-42889',  // Text4Shell (commons-text)
        'CVE-2017-5638',   // Struts2 RCE
        'CVE-2019-12384',  // jackson-databind
        'CVE-2020-36518',  // jackson-databind
        'CVE-2023-46120',  // RabbitMQ Java client
        'CVE-2024-22243',  // spring-web
        'CVE-2020-15250',  // junit4
        'CVE-2022-26336',  // tomcat-embed-core
        'CVE-2023-2976',   // guava
        'CVE-2018-1000180', // bcprov
        'CVE-2021-37137',  // netty-codec
    ];

    /** Distribution sévérité : ~10% CRITICAL, 25% HIGH, 45% MEDIUM, 20% LOW. */
    private const SEVERITY_DISTRIBUTION = [
        'CRITICAL' => 10,
        'HIGH'     => 25,
        'MEDIUM'   => 45,
        'LOW'      => 20,
    ];

    /** Mapping severity -> CVSS score median. */
    private const CVSS_BY_SEVERITY = [
        'CRITICAL' => '9.5',
        'HIGH'     => '7.8',
        'MEDIUM'   => '5.5',
        'LOW'      => '3.2',
    ];

    /**
     * MODIF 2026-05-11 : cache local d'objets DcCve par
     * cve_id, alimente au fur et a mesure des findings d'un meme run. Evite
     * de re-persister un DcCve deja insère durant le meme appel (sinon
     * violation unique constraint sur cve_id au flush).
     *
     * @var array<string, DcCve>
     */
    private array $cveCacheById = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.environment%')]
        private readonly string $appEnv,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Nombre de scans a generer.', 10)
            ->addOption('clean', null, InputOption::VALUE_NONE, 'Truncate dc_finding/dc_scan/dc_dependency/dc_cve avant.');
    }

    /**
     * [Description for execute]
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * Created at: 11/05/2026 09:27:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // MODIF 2026-05-11 : reset du cache au démarrage
        // au cas ou la meme instance de commande serait réutilisée (tests).
        $this->cveCacheById = [];

        /* Garde-fou : refuse strictement tout autre env que dev. La commande
         * insère des données fictives, hors de question d'y toucher en prod. */
        if ($this->appEnv !== 'dev') {
            $io->error(sprintf('❌ Cette commande est réservée à env=dev (env courant : %s).', $this->appEnv));
            return Command::FAILURE;
        }

        $count = max(1, (int) $input->getOption('count'));
        $clean = (bool) $input->getOption('clean');

        $io->section(sprintf('Seed DC : génération de %d scans plausibles%s', $count, $clean ? ' (avec clean préalable)' : ''));

        if ($clean) {
            $this->cleanDcTables($io);
        }

        /* Pioche au hasard N projets distincts depuis liste_projet. Si la table
         * en a moins, on cap à ce qu'on a et on prévient. */
        $projetRepo  = $this->em->getRepository(ListeProjet::class);
        $allProjets  = $projetRepo->findAll();
        if (count($allProjets) === 0) {
            $io->error('❌ Aucun projet dans liste_projet. Impossible de générer des scans.');
            return Command::FAILURE;
        }

        /* filtre les projets non exploitables pour éviter les artifact='unknown' en base :
         *  - maven_key sans séparateur ':' (legacy non-maven)
         *  - tags null ou vides (le projet n'est pas rattaché à un périmètre,
         *    donc il sera invisible dans tout filtrage par groupe_fonctionnel) */
        $eligibles = array_values(array_filter($allProjets, function (ListeProjet $p): bool {
            $mavenKey = (string) $p->getMavenKey();
            if (strpos($mavenKey, ':') === false) {
                return false;
            }
            $tags = $p->getTags();
            return count($tags) > 0;
        }));
        if (count($eligibles) === 0) {
            $io->error('❌ Aucun projet éligible (maven_key avec ":" + tags non vides). Impossible de générer des scans.');
            return Command::FAILURE;
        }

        $picked = $this->pickRandomProjects($eligibles, $count);
        $io->note(sprintf(
            '%d projet(s) sélectionné(s) sur %d éligible(s) (%d total en base, %d non éligibles écartés).',
            count($picked), count($eligibles), count($allProjets), count($allProjets) - count($eligibles)
        ));

        $totalFindings = 0;
        foreach ($picked as $i => $projet) {
            $scanInfo = $this->generateScanForProject($projet, $i);
            $totalFindings += $scanInfo['nb_findings'];
            $io->writeln(sprintf(
                '  · %s:%s v%s — %d findings (C:%d H:%d M:%d L:%d)',
                $scanInfo['group'], $scanInfo['artifact'], $scanInfo['version'],
                $scanInfo['nb_findings'],
                $scanInfo['nb_critical'], $scanInfo['nb_high'], $scanInfo['nb_medium'], $scanInfo['nb_low']
            ));
        }

        $this->em->flush();

        $io->success(sprintf('ℹ️ %d scans génères, %d findings au total.', count($picked), $totalFindings));
        $this->logger->info('[DC-Seed] ℹ️ Seed dev termine.', [
            'count'    => count($picked),
            'findings' => $totalFindings,
        ]);

        return Command::SUCCESS;
    }

    /**
     * [Description for cleanDcTables]
     * Truncate les 4 tables DC (CASCADE prend en charge les FK).
     *
     * @param SymfonyStyle $io
     *
     * @return void
     *
     * Created at: 11/05/2026 09:32:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function cleanDcTables(SymfonyStyle $io): void
    {
        $io->writeln('  Truncate dc_finding, dc_scan, dc_dependency, dc_cve...');
        $conn = $this->em->getConnection();
        $conn->executeStatement('TRUNCATE TABLE ma_moulinette.dc_finding RESTART IDENTITY CASCADE');
        $conn->executeStatement('TRUNCATE TABLE ma_moulinette.dc_scan RESTART IDENTITY CASCADE');
        $conn->executeStatement('TRUNCATE TABLE ma_moulinette.dc_dependency RESTART IDENTITY CASCADE');
        $conn->executeStatement('TRUNCATE TABLE ma_moulinette.dc_cve RESTART IDENTITY CASCADE');
        $io->writeln('  → Tables purgées.');
    }

    /**
     * [Description for pickRandomProjects]
     * Pioche N projets distincts au hasard parmi la liste fournie.
     *
     * @param ListeProjet[] $allProjets
     * @return ListeProjet[]
     *
     * Created at: 11/05/2026 09:32:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function pickRandomProjects(array $allProjets, int $count): array
    {
        $count = min($count, count($allProjets));
        $keys  = array_rand($allProjets, $count);
        $keys  = is_array($keys) ? $keys : [$keys];
        return array_map(static fn($k) => $allProjets[$k], $keys);
    }

    /**
     * [Description for generateScanForProject]
     * Génère 1 scan complet (DcScan + dependencies + CVE + findings) pour 1 projet.
     * Retourne un resume des compteurs pour log console.
     *
     * @param ListeProjet $projet
     * @param int $index
     *
     * @return array{group:string, artifact:string, version:string, nb_findings:int, nb_critical:int, nb_high:int, nb_medium:int, nb_low:int}
     *
     * Created at: 11/05/2026 09:31:54 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function generateScanForProject(ListeProjet $projet, int $index): array
    {
        // Decompose maven_key (deja stockée dans ListeProjet)
        $mavenKey = $projet->getMavenKey();
        $parts    = explode(':', $mavenKey);
        $group    = $parts[0];
        $artifact = $parts[1] ?? 'unknown';

        // Version plausible : pioche dans un pool de patterns
        $versionPool = ['1.0.0', '1.2.3', '2.0.0-SNAPSHOT', '2.5.1', '3.0.0', '4.1.2-RELEASE', '5.0.0', '1.4.7'];
        $version     = $versionPool[$index % count($versionPool)];

        // Scan date : échelonne sur 30 derniers jours, ordre chronologique
        $daysAgo  = random_int(0, 30);
        $scanDate = (new \DateTimeImmutable('now'))->modify("-{$daysAgo} days")->setTime(
            random_int(8, 18), random_int(0, 59)
        );

        // Nombre de findings : 3 a 30 par scan, plus pour les "gros" projets
        $nbFindings = random_int(3, 30);

        // Repartition par sévérité selon la distribution
        $bySev = ['CRITICAL' => 0, 'HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0];
        for ($k = 0; $k < $nbFindings; $k++) {
            $bySev[$this->pickWeightedSeverity()]++;
        }

        $scan = (new DcScan())
            ->setMavenKey($mavenKey)
            ->setProjectGroup($group)
            ->setProjectArtifact($artifact)
            ->setProjectVersion($version)
            ->setScanDate($scanDate)
            ->setEngineVersion('12.2.0')
            ->setReportSchema('1.1')
            ->setDepCountTotal(random_int(40, 120))
            ->setDepCountVulnerable($nbFindings) // simplification : 1 dep = 1 finding
            ->setCveCountTotal($nbFindings)
            ->setCveCountCritical($bySev['CRITICAL'])
            ->setCveCountHigh($bySev['HIGH'])
            ->setCveCountMedium($bySev['MEDIUM'])
            ->setCveCountLow($bySev['LOW']);
        $this->em->persist($scan);

        /* Fix affichage console : on copie
         * les compteurs AVANT le for qui décrémente $bySev par référence via
         * randomSeverityForFinding(). Sans cette copie, le retour de fonction
         * ressort tous les compteurs à 0 (alors que les findings sont bien créés
         * et que le scan a les bons cve_count_* en base). */
        $finalCounts = $bySev;

        // Génère les findings pour ce scan
        for ($f = 0; $f < $nbFindings; $f++) {
            $this->generateFinding($scan, $f, $this->randomSeverityForFinding($bySev));
        }

        // Flush par scan pour éviter des batches énormes
        $this->em->flush();

        return [
            'group'       => $group,
            'artifact'    => $artifact,
            'version'     => $version,
            'nb_findings' => $nbFindings,
            'nb_critical' => $finalCounts['CRITICAL'],
            'nb_high'     => $finalCounts['HIGH'],
            'nb_medium'   => $finalCounts['MEDIUM'],
            'nb_low'      => $finalCounts['LOW'],
        ];
    }

    /**
     * [Description for generateFinding]
     * Cree 1 DcFinding lie a 1 DcDependency et 1 DcCve plausibles.
     * Les deps sont piochées dans POOL_DEPENDENCIES (-> mutualisation possible
     * entre scans). Les CVE sont générées avec un id unique par finding pour
     * éviter les collisions sur l'unique constraint cve_id.
     *
     * @param DcScan $scan
     * @param int $findingIndex
     * @param string $severity
     *
     * @return void
     *
     * Created at: 11/05/2026 09:30:54 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function generateFinding(DcScan $scan, int $findingIndex, string $severity): void
    {
        // Pioche une dep plausible (avec petite variation de version pour éviter
        // les collisions sur sha1/sha256 entre scans différents)
        $tplDep = self::POOL_DEPENDENCIES[$findingIndex % count(self::POOL_DEPENDENCIES)];

        $dep = (new DcDependency())
            ->setSha1(substr(sha1((string) $scan->getMavenKey() . $findingIndex . microtime(true)), 0, 40))
            ->setSha256(hash('sha256', (string) $scan->getMavenKey() . $findingIndex . microtime(true)))
            ->setFileName($tplDep['file'])
            ->setPkgCoordinates(sprintf('pkg:maven/%s/%s@%s', $tplDep['vendor'], $tplDep['product'], $tplDep['version']))
            ->setVendor($tplDep['vendor'])
            ->setProduct($tplDep['product'])
            ->setVersion($tplDep['version']);
        $this->em->persist($dep);

        /* MODIF 2026-05-11 : on pioche un cve_id dans
         * un pool fixe au lieu de générer un id unique. La première fois
         * qu'on rencontre ce cve_id durant le run on crée la DcCve ; sinon
         * on réutilise celle déjà persisté (via cache local ou lookup DB
         * pour les seeds successifs sans --clean). */
        $cveId = self::POOL_CVE_IDS[array_rand(self::POOL_CVE_IDS)];

        $cve = $this->cveCacheById[$cveId] ?? null;
        if ($cve === null) {
            // Pas encore vu durant ce run : on cherche en base (cas seed
            // sans --clean préalable).
            $cve = $this->em->getRepository(DcCve::class)->findOneBy(['cveId' => $cveId]);
        }

        if ($cve === null) {
            // Pioche 1 a 3 CWE distinctes pour ce CVE
            $cweCount = random_int(1, 3);
            $cwes     = (array) array_rand(array_flip(self::POOL_CWES), $cweCount);

            // Pioche un vecteur d'attaque plausible (pour tester l'heuristique de
            // decision : NETWORK -> Upgrade, LOCAL/PHYSICAL -> minoration).
            $attackVector = ['NETWORK', 'NETWORK', 'NETWORK', 'ADJACENT_NETWORK', 'LOCAL', 'PHYSICAL'][random_int(0, 5)];

            $cve = (new DcCve())
                ->setCveId($cveId)
                ->setSource('NVD')
                ->setSeverity($severity === 'CRITICAL' ? DcCve::SEVERITY_CRITICAL
                    : ($severity === 'HIGH' ? DcCve::SEVERITY_HIGH
                        : ($severity === 'MEDIUM' ? DcCve::SEVERITY_MEDIUM : DcCve::SEVERITY_LOW)))
                ->setCvssV3Score(self::CVSS_BY_SEVERITY[$severity] ?? '5.0')
                ->setCvssV3Severity($severity)
                ->setCvssV3AttackVector($attackVector)
                ->setCwes($cwes)
                ->setDescription(sprintf('Vulnerability %s affecting %s %s.', $cveId, $tplDep['product'], $tplDep['version']))
                ->setCveReferences([['source' => 'NVD', 'url' => 'https://nvd.nist.gov/vuln/detail/' . $cveId, 'name' => $cveId]]);
            $this->em->persist($cve);
        }
        // On mémorise dans tous les cas (cache OU lookup OU creation) pour les
        // findings suivants du meme run.
        $this->cveCacheById[$cveId] = $cve;

        // fixed_version : 70% du temps une version supérieure, 30% null (pas de fix disponible)
        $fixedVersion = null;
        if (random_int(0, 9) < 7) {
            $parts = explode('.', (string) $tplDep['version']);
            /* MODIF 2026-05-20 : offset 0 garanti par explode. */
            if (!empty($parts[0])) {
                $parts[0] = (string) ((int) $parts[0] + 1);
                $fixedVersion = implode('.', array_filter($parts, fn($p) => $p !== ''));
            }
        }

        $finding = (new DcFinding())
            ->setScan($scan)
            ->setDependency($dep)
            ->setCve($cve)
            ->setSeverityAtScan($severity)
            ->setCvssAtScan(self::CVSS_BY_SEVERITY[$severity] ?? '5.0')
            ->setFixedVersion($fixedVersion);
        $this->em->persist($finding);
    }

    /**
     * [Description for pickWeightedSeverity]
     * Tirage pondère selon SEVERITY_DISTRIBUTION.
     *
     * @return string
     *
     * Created at: 11/05/2026 09:30:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function pickWeightedSeverity(): string
    {
        $r = random_int(1, 100);
        $cum = 0;
        foreach (self::SEVERITY_DISTRIBUTION as $sev => $weight) {
            $cum += $weight;
            if ($r <= $cum) {
                return $sev;
            }
        }
        return 'LOW';
    }

    /**
     * [Description for randomSeverityForFinding]
     * Renvoie une severity à partir des compteurs deja prévus (décrémente
     * en passant). Garantit que les compteurs du scan correspondent aux
     * findings effectivement génères.
     *
     * @param array<string,int> $bySev passe par reference
     *
     * @return string
     *
     * Created at: 11/05/2026 09:30:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function randomSeverityForFinding(array &$bySev): string
    {
        foreach ($bySev as $sev => $n) {
            if ($n > 0) {
                $bySev[$sev]--;
                return $sev;
            }
        }
        return 'LOW';
    }
}
