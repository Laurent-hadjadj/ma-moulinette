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

namespace App\Tests\Unit\Entity;

use App\Entity\LoggerDetail;
use PHPUnit\Framework\TestCase;

class LoggerDetailTest extends TestCase
{
    public function testConstructorInitialisesDateEnregistrement(): void
    {
        $entity = new LoggerDetail();

        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $entity->getDateEnregistrement());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $entity = new LoggerDetail();

        $this->assertNull($entity->getId());
    }

    public function testSetGetMavenKey(): void
    {
        $entity = new LoggerDetail();
        $result = $entity->setMavenKey('fr.ma-moulinette:ma-moulinette');

        $this->assertSame($entity, $result);
        $this->assertSame('fr.ma-moulinette:ma-moulinette', $entity->getMavenKey());
    }

    public function testSetGetProjectVersion(): void
    {
        $entity = new LoggerDetail();
        $result = $entity->setProjectVersion('2.0.0-RELEASE');

        $this->assertSame($entity, $result);
        $this->assertSame('2.0.0-RELEASE', $entity->getProjectVersion());
    }

    public function testProjectVersionDefaultsToNull(): void
    {
        $entity = new LoggerDetail();

        $this->assertNull($entity->getProjectVersion());
    }

    public function testSetGetLevel(): void
    {
        $entity = new LoggerDetail();
        $result = $entity->setLevel('error');

        $this->assertSame($entity, $result);
        $this->assertSame('error', $entity->getLevel());
    }

    public function testSetGetFramework(): void
    {
        $entity = new LoggerDetail();
        $result = $entity->setFramework('SLF4J');

        $this->assertSame($entity, $result);
        $this->assertSame('SLF4J', $entity->getFramework());
    }

    public function testFrameworkDefaultsToNull(): void
    {
        $entity = new LoggerDetail();

        $this->assertNull($entity->getFramework());
    }

    public function testSetGetFilePath(): void
    {
        $entity = new LoggerDetail();
        $result = $entity->setFilePath('src/main/java/fr/ma/moulinette/App.java');

        $this->assertSame($entity, $result);
        $this->assertSame('src/main/java/fr/ma/moulinette/App.java', $entity->getFilePath());
    }

    public function testSetGetFileName(): void
    {
        $entity = new LoggerDetail();
        $result = $entity->setFileName('App.java');

        $this->assertSame($entity, $result);
        $this->assertSame('App.java', $entity->getFileName());
    }

    public function testSetGetClassName(): void
    {
        $entity = new LoggerDetail();
        $result = $entity->setClassName('App');

        $this->assertSame($entity, $result);
        $this->assertSame('App', $entity->getClassName());
    }

    public function testClassNameDefaultsToNull(): void
    {
        $entity = new LoggerDetail();

        $this->assertNull($entity->getClassName());
    }

    public function testSetGetLineNumber(): void
    {
        $entity = new LoggerDetail();
        $result = $entity->setLineNumber(42);

        $this->assertSame($entity, $result);
        $this->assertSame(42, $entity->getLineNumber());
    }

    public function testLineNumberDefaultsToNull(): void
    {
        $entity = new LoggerDetail();

        $this->assertNull($entity->getLineNumber());
    }

    public function testSetGetSonarIssueKey(): void
    {
        $entity = new LoggerDetail();
        $result = $entity->setSonarIssueKey('AY-abc123');

        $this->assertSame($entity, $result);
        $this->assertSame('AY-abc123', $entity->getSonarIssueKey());
    }

    public function testSonarIssueKeyDefaultsToNull(): void
    {
        $entity = new LoggerDetail();

        $this->assertNull($entity->getSonarIssueKey());
    }

    public function testSetGetModeCollecte(): void
    {
        $entity = new LoggerDetail();
        $result = $entity->setModeCollecte('[COLLECTE]');

        $this->assertSame($entity, $result);
        $this->assertSame('[COLLECTE]', $entity->getModeCollecte());
    }

    public function testModeCollecteDefaultsToNull(): void
    {
        $entity = new LoggerDetail();

        $this->assertNull($entity->getModeCollecte());
    }

    public function testSetGetUtilisateurCollecte(): void
    {
        $entity = new LoggerDetail();
        $result = $entity->setUtilisateurCollecte('admin@ma-moulinette.fr');

        $this->assertSame($entity, $result);
        $this->assertSame('admin@ma-moulinette.fr', $entity->getUtilisateurCollecte());
    }

    public function testUtilisateurCollecteDefaultsToNull(): void
    {
        $entity = new LoggerDetail();

        $this->assertNull($entity->getUtilisateurCollecte());
    }

    public function testSetGetDateEnregistrement(): void
    {
        $entity = new LoggerDetail();
        $date = new \DateTimeImmutable('2026-01-01 08:00:00');
        $result = $entity->setDateEnregistrement($date);

        $this->assertSame($entity, $result);
        $this->assertSame($date, $entity->getDateEnregistrement());
    }
}
