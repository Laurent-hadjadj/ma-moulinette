<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\DataFixtures\AccessibilityFixtures;
use App\Entity\Accessibility;
use App\Repository\AccessibilityRepository;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * MODIF 26/10/2026 : couverture Integration pour accessibilityRepository —
 * comble le trou de couverture déjà repéré pour EcoConceptionRepository
 * (aucun test Integration existant pour ce pattern), cf.
 * mkDocs/docs/ma-moulinette/application/projet.md.
 * MODIF 27/10/2026 : AccessibilityFixtures ajoutée pour la parité avec le
 * pattern RciFixtures/RciRepositoryTest (même maven_key de référence,
 * `fr.ma-moulinette:ma-moulinette`, distincte de MAVEN_KEY ci-dessous — aucun
 * conflit entre les deux jeux de données).
 */
class AccessibilityRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $em = null;

    private const MAVEN_KEY = 'fr.ma-moulinette:accessibility-repo-test';
    private const FIXTURE_MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';

    protected function setUp(): void
    {
        self::bootKernel();
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $this->em = $em;
        $this->em->getConnection()->executeStatement(
            "DELETE FROM ma_moulinette.accessibility WHERE maven_key = '" . self::MAVEN_KEY . "'"
        );
        $this->em->getConnection()->executeStatement(
            "DELETE FROM ma_moulinette.accessibility WHERE maven_key = '" . self::FIXTURE_MAVEN_KEY . "'"
        );
        $executor = new ORMExecutor($this->em);
        $executor->execute([new AccessibilityFixtures()], true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em?->close();
        $this->em = null;
    }

    private function getRepository(): AccessibilityRepository
    {
        return $this->em->getRepository(Accessibility::class);
    }

    private function insertRow(int $anomalies, string $rating): void
    {
        $map = [
            'maven_key' => self::MAVEN_KEY,
            'accessibility_anomalies' => $anomalies,
            'accessibility_dette_minute' => $anomalies * 3,
            'accessibility_rating' => $rating,
            'accessibility_css_anomalies' => $anomalies,
            'accessibility_css_dette_minute' => $anomalies * 3,
            'accessibility_css_rating' => $rating,
            'accessibility_web_anomalies' => null,
            'accessibility_web_dette_minute' => null,
            'accessibility_web_rating' => null,
            'accessibility_javascript_anomalies' => null,
            'accessibility_javascript_dette_minute' => null,
            'accessibility_javascript_rating' => null,
            'accessibility_typescript_anomalies' => null,
            'accessibility_typescript_dette_minute' => null,
            'accessibility_typescript_rating' => null,
            'mode_collecte' => 'COLLECTE',
            'utilisateur_collecte' => 'test@ma-moulinette.fr',
            'date_enregistrement' => new \DateTimeImmutable('now'),
        ];
        $result = $this->getRepository()->insertAccessibility($map);
        $this->assertSame(200, $result['code'], $result['erreur'] ?? '');
    }

    public function testInsertThenSelectReturnsInsertedRow(): void
    {
        $this->insertRow(9, 'C');

        $result = $this->getRepository()->selectAccessibility(['maven_key' => self::MAVEN_KEY]);

        $this->assertSame(200, $result['code']);
        $this->assertIsArray($result['liste']);
        $this->assertSame(self::MAVEN_KEY, $result['liste']['maven_key']);
        $this->assertSame(9, $result['liste']['accessibility_anomalies']);
        $this->assertSame(27, $result['liste']['accessibility_dette_minute']);
        $this->assertSame('C', $result['liste']['accessibility_rating']);
        $this->assertSame(9, $result['liste']['accessibility_css_anomalies']);
        $this->assertNull($result['liste']['accessibility_web_anomalies']);
    }

    public function testSelectReturnsFalseWhenNoRowForMavenKey(): void
    {
        $result = $this->getRepository()->selectAccessibility(['maven_key' => self::MAVEN_KEY]);

        $this->assertSame(200, $result['code']);
        $this->assertFalse($result['liste']);
    }

    public function testSelectAccessibilityReturnsMostRecentFixtureRow(): void
    {
        $result = $this->getRepository()->selectAccessibility(['maven_key' => self::FIXTURE_MAVEN_KEY]);

        $this->assertSame(200, $result['code']);
        $this->assertIsArray($result['liste'], 'AccessibilityFixtures doit avoir seedé 3 lignes.');
        $this->assertSame(self::FIXTURE_MAVEN_KEY, $result['liste']['maven_key']);
        // La fixture seed la plus récente au 2026-03-01 — ORDER BY DESC → c'est elle
        $this->assertStringStartsWith('2026-03-01', (string) $result['liste']['date_enregistrement']);
        $this->assertNull($result['liste']['accessibility_anomalies'], 'La fixture simule un projet jamais collecté.');
    }

    public function testInsertTwiceKeepsBothRowsOrderedByDateDesc(): void
    {
        $this->insertRow(5, 'A');
        usleep(1_100_000); // garantit un date_enregistrement strictement supérieur
        $this->insertRow(20, 'D');

        $result = $this->getRepository()->selectAccessibility(['maven_key' => self::MAVEN_KEY]);

        // selectAccessibility() trie par date_enregistrement DESC LIMIT 1 -> la plus récente
        $this->assertSame(20, $result['liste']['accessibility_anomalies']);
        $this->assertSame('D', $result['liste']['accessibility_rating']);

        $count = (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM ma_moulinette.accessibility WHERE maven_key = :maven_key',
            ['maven_key' => self::MAVEN_KEY]
        );
        $this->assertSame(2, $count, 'insertAccessibility() ne doit pas écraser la ligne précédente (snapshot append-only, purgé par deleteAccessibilityMavenKey()).');
    }

    public function testDeleteAccessibilityMavenKeyRemovesAllRowsForProject(): void
    {
        $this->insertRow(5, 'A');
        $this->insertRow(10, 'B');

        $result = $this->getRepository()->deleteAccessibilityMavenKey(['maven_key' => self::MAVEN_KEY]);
        $this->assertSame(200, $result['code']);

        $count = (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM ma_moulinette.accessibility WHERE maven_key = :maven_key',
            ['maven_key' => self::MAVEN_KEY]
        );
        $this->assertSame(0, $count);
    }
}
