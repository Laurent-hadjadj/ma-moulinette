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

namespace App\Tests\Unit\Command\Dev;

use App\Command\Dev\DemoPreparerCommand;
use App\Controller\Accueil\ApiAccueilController;
use App\Controller\Batch\BatchCollecteCleanCodeController;
use App\Controller\Batch\CollecteController;
use App\Controller\Profil\ApiProfilController;
use App\Repository\ListeProjetRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Garde-fous de app:demo:preparer : la commande vide et réécrit des tables, elle ne doit JAMAIS s'exécuter
 * hors env=dev, ni sur une base autre que la pile de préparation (hôte local, port 5435).
 */
class DemoPreparerCommandTest extends TestCase
{
    /**
     * @return iterable<string, array{array<string, mixed>, bool}>
     */
    public static function connexionsProvider(): iterable
    {
        yield 'pile de préparation (127.0.0.1:5435)' => [['host' => '127.0.0.1', 'port' => 5435], true];
        yield 'localhost, port en texte' => [['host' => 'localhost', 'port' => '5435'], true];
        yield 'hôte en majuscules' => [['host' => 'LOCALHOST', 'port' => 5435], true];
        yield 'IPv6 locale' => [['host' => '::1', 'port' => 5435], true];
        yield 'base de dev (5432)' => [['host' => '127.0.0.1', 'port' => 5432], false];
        yield 'conteneur e2e (5434)' => [['host' => '127.0.0.1', 'port' => 5434], false];
        yield 'hôte distant, bon port' => [['host' => 'postgres.exemple.interne', 'port' => 5435], false];
        yield 'port absent' => [['host' => '127.0.0.1'], false];
        yield 'hôte absent' => [['port' => 5435], false];
        yield 'aucun paramètre' => [[], false];
    }

    /**
     * @param array<string, mixed> $params
     */
    #[DataProvider('connexionsProvider')]
    public function testBaseAutorisee(array $params, bool $attendu): void
    {
        $this->assertSame($attendu, DemoPreparerCommand::baseAutorisee($params));
    }

    public function testRetenirProjetsSansFiltreRendTousLesProjetsTries(): void
    {
        $liste = [['id' => 'b:projet', 'text' => 'b'], ['id' => 'a:projet', 'text' => 'a'], ['id' => 'a:projet', 'text' => 'a']];

        $this->assertSame(
            ['retenus' => ['a:projet', 'b:projet'], 'inconnus' => []],
            DemoPreparerCommand::retenirProjets($liste, [])
        );
    }

    public function testRetenirProjetsFiltreEtSignaleLesCleInconnues(): void
    {
        $liste = [['id' => 'a:projet'], ['id' => 'b:projet']];

        $this->assertSame(
            ['retenus' => ['b:projet'], 'inconnus' => ['zz:absent']],
            DemoPreparerCommand::retenirProjets($liste, ['b:projet', 'zz:absent', 'b:projet'])
        );
    }

    public function testRetenirProjetsListeVide(): void
    {
        $this->assertSame(['retenus' => [], 'inconnus' => ['a:projet']], DemoPreparerCommand::retenirProjets([], ['a:projet']));
    }

    public function testRefuseHorsEnvDevSansToucherALaBase(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('getConnection');

        $tester = new CommandTester($this->commande($em, 'prod', $this->neJamaisAppelerAccueil()));
        $exit = $tester->execute([]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('env=dev', $tester->getDisplay());
    }

    public function testRefuseUneBaseAutreQueLaPileDePreparation(): void
    {
        $connexion = $this->createStub(Connection::class);
        $connexion->method('getParams')->willReturn(['host' => '127.0.0.1', 'port' => 5432]);
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connexion);

        /* Si la garde échoue, accueilProjetListe() (qui vide liste_projet) serait appelée : le test échouerait. */
        $tester = new CommandTester($this->commande($em, 'dev', $this->neJamaisAppelerAccueil()));
        $exit = $tester->execute([]);

        $this->assertSame(1, $exit);
        $affichage = $tester->getDisplay();
        $this->assertStringContainsString('Connexion refusée', $affichage);
        $this->assertStringContainsString('127.0.0.1:5432', $affichage);
    }

    private function neJamaisAppelerAccueil(): ApiAccueilController
    {
        $accueil = $this->createMock(ApiAccueilController::class);
        $accueil->expects($this->never())->method('accueilProjetListe');

        return $accueil;
    }

    private function commande(EntityManagerInterface $em, string $env, ApiAccueilController $accueil): DemoPreparerCommand
    {
        return new DemoPreparerCommand(
            $em,
            $accueil,
            $this->createStub(ApiProfilController::class),
            $this->createStub(CollecteController::class),
            $this->createStub(BatchCollecteCleanCodeController::class),
            $this->createStub(TokenStorageInterface::class),
            $env
        );
    }

    /**
     * @return iterable<string, array{array<string, mixed>|\Throwable, int, string}>
     */
    public static function resultatsCollecteProvider(): iterable
    {
        yield 'collecte réussie (200)' => [['code' => 200], 0, 'OK'];
        /* Non-régression : 202 = l'analyse locale est déjà celle de SonarQube. Ce n'est pas un échec : sans cela,
         * relancer la préparation sans tout réinitialiser échouait pour chaque projet déjà collecté. */
        yield 'déjà à jour (202)' => [['code' => 202], 0, 'À JOUR'];
        yield 'échec (500)' => [['code' => 500], 1, 'ÉCHEC'];
        yield 'exception' => [new \RuntimeException('Sonar injoignable'), 1, 'ÉCHEC'];
    }

    /**
     * @param array<string, mixed>|\Throwable $resultat
     */
    #[DataProvider('resultatsCollecteProvider')]
    public function testCompteRenduDeLaCollecte(array|\Throwable $resultat, int $codeSortieAttendu, string $etatAttendu): void
    {
        $tester = new CommandTester($this->commandeComplete($resultat));

        $exit = $tester->execute(['--sans-historique' => true]);

        $this->assertSame($codeSortieAttendu, $exit);
        $affichage = $tester->getDisplay();
        $this->assertStringContainsString('a:projet', $affichage);
        $this->assertStringContainsString($etatAttendu, $affichage);
        if ($resultat instanceof \Throwable) {
            $this->assertStringContainsString('Sonar injoignable', $affichage);
        }
    }

    /**
     * Non-régression : la table clean_code n'est alimentée que par la collecte interactive de la page projet, pas par
     * CollecteController. Sans cet appel, la page Clean Code de la démonstration restait vide.
     */
    public function testLesIndicateursCleanCodeSontCollectesPourChaqueProjet(): void
    {
        $cleanCode = $this->createMock(BatchCollecteCleanCodeController::class);
        $cleanCode->expects($this->once())
            ->method('BatchCollecteCleanCode')
            ->with('a:projet', 'COLLECTE', DemoPreparerCommand::COURRIEL_COLLECTE)
            ->willReturn(['code' => 200]);
        $tester = new CommandTester($this->commandeComplete(['code' => 200], ['code' => 200], $cleanCode));

        $exit = $tester->execute(['--sans-historique' => true]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Clean Code', $tester->getDisplay());
    }

    /**
     * Une analyse déjà à jour (202) peut n'avoir aucune ligne clean_code : la collecte Clean Code doit quand même tourner.
     */
    public function testLesIndicateursCleanCodeSontCollectesMemeSiLAnalyseEstDejaAJour(): void
    {
        $cleanCode = $this->createMock(BatchCollecteCleanCodeController::class);
        $cleanCode->expects($this->once())->method('BatchCollecteCleanCode')->willReturn(['code' => 200]);
        $tester = new CommandTester($this->commandeComplete(['code' => 202], ['code' => 200], $cleanCode));

        $exit = $tester->execute(['--sans-historique' => true]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('À JOUR', $tester->getDisplay());
    }

    public function testPasDeCollecteCleanCodeQuandLaCollecteEchoue(): void
    {
        $cleanCode = $this->createMock(BatchCollecteCleanCodeController::class);
        $cleanCode->expects($this->never())->method('BatchCollecteCleanCode');
        $tester = new CommandTester($this->commandeComplete(['code' => 500], ['code' => 200], $cleanCode));

        $exit = $tester->execute(['--sans-historique' => true]);

        $this->assertSame(1, $exit);
    }

    /**
     * @return iterable<string, array{array<string, mixed>|\Throwable, int, string}>
     */
    public static function resultatsCleanCodeProvider(): iterable
    {
        yield 'collectés' => [['code' => 200], 0, 'OK'];
        /* SonarQube < 10 : l'application l'ignore sans bruit (code 200, « skipped »), ce n'est pas un échec. */
        yield 'serveur sans facettes 10+' => [['code' => 200, 'skipped' => true], 0, 'NON SUPPORTÉ'];
        yield 'échec (500)' => [['code' => 500, 'erreur' => 'x'], 1, 'ÉCHEC'];
        yield 'exception' => [new \RuntimeException('Sonar injoignable'), 1, 'ÉCHEC'];
    }

    /**
     * @param array<string, mixed>|\Throwable $resultat
     */
    #[DataProvider('resultatsCleanCodeProvider')]
    public function testCompteRenduDeLaCollecteCleanCode(array|\Throwable $resultat, int $codeSortieAttendu, string $etatAttendu): void
    {
        $tester = new CommandTester($this->commandeComplete(['code' => 200], $resultat));

        $exit = $tester->execute(['--sans-historique' => true]);

        $this->assertSame($codeSortieAttendu, $exit);
        $this->assertStringContainsString($etatAttendu, $tester->getDisplay());
        if ($resultat instanceof \Throwable) {
            $this->assertStringContainsString('clean code : Sonar injoignable', $tester->getDisplay());
        }
    }

    /**
     * @param array<string, mixed>|\Throwable $resultatCollecte
     * @param array<string, mixed>|\Throwable $resultatCleanCode
     */
    private function commandeComplete(
        array|\Throwable $resultatCollecte,
        array|\Throwable $resultatCleanCode = ['code' => 200],
        ?BatchCollecteCleanCodeController $cleanCode = null
    ): DemoPreparerCommand {
        $connexion = $this->createStub(Connection::class);
        $connexion->method('getParams')->willReturn(['host' => '127.0.0.1', 'port' => DemoPreparerCommand::PORT_PREPARATION]);
        $connexion->method('fetchOne')->willReturn(1);

        $repo = $this->createStub(ListeProjetRepository::class);
        $repo->method('selectListeProjetByGroupe')->willReturn(['code' => 200, 'liste' => [['id' => 'a:projet', 'text' => 'a']]]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connexion);
        $em->method('getRepository')->willReturn($repo);

        $accueil = $this->createStub(ApiAccueilController::class);
        $accueil->method('accueilProjetListe')->willReturn(new JsonResponse(['code' => 200, 'nombre' => 1, 'public' => 0, 'private' => 1, 'empty_tags' => 0]));

        $profil = $this->createStub(ApiProfilController::class);
        $profil->method('listeQualityProfiles')->willReturn(new JsonResponse(['code' => 200, 'type' => 'success']));

        $collecte = $this->createStub(CollecteController::class);
        if ($resultatCollecte instanceof \Throwable) {
            $collecte->method('collecte')->willThrowException($resultatCollecte);
        } else {
            $collecte->method('collecte')->willReturn($resultatCollecte);
        }

        if ($cleanCode === null) {
            $cleanCode = $this->createStub(BatchCollecteCleanCodeController::class);
            if ($resultatCleanCode instanceof \Throwable) {
                $cleanCode->method('BatchCollecteCleanCode')->willThrowException($resultatCleanCode);
            } else {
                $cleanCode->method('BatchCollecteCleanCode')->willReturn($resultatCleanCode);
            }
        }

        return new DemoPreparerCommand($em, $accueil, $profil, $collecte, $cleanCode, $this->createStub(TokenStorageInterface::class), 'dev');
    }
}
