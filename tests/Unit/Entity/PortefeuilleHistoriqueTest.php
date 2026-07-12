<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\PortefeuilleHistorique;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-15 : tests Unit getters/setters
 * de l’entité PortefeuilleHistorique. Pattern symetrique a ProfilesHistorique
 * (meme schema, semantique cote portefeuille — cf MODIF 2026-05-05 dans le
 * fichier source).
 */
class PortefeuilleHistoriqueTest extends TestCase
{
    public function testIdSetterGetter(): void
    {
        // NB : la propriété $id est typée `int` non-nullable. L'accès avant
        // setId() lève une "Typed property must not be accessed before
        // initialization" (Doctrine remplit $id post-persist en runtime,
        // mais en Unit on l'init via setId). On teste donc juste le
        // round-trip post-setId.
        $e = new PortefeuilleHistorique();
        $e->setId(42);
        $this->assertSame(42, $e->getId());
    }

    public function testDateCourteRoundTrip(): void
    {
        $e = new PortefeuilleHistorique();
        $d = new \DateTimeImmutable('2026-05-15 10:00:00');
        $e->setDateCourte($d);
        $this->assertSame($d, $e->getDateCourte());
    }

    public function testLanguageRoundTrip(): void
    {
        $e = new PortefeuilleHistorique();
        $e->setLanguage('java');
        $this->assertSame('java', $e->getLanguage());
    }

    public function testDateRoundTrip(): void
    {
        $e = new PortefeuilleHistorique();
        $d = new \DateTimeImmutable('2026-05-15T10:00:00+02:00');
        $e->setDate($d);
        $this->assertSame($d, $e->getDate());
    }

    public function testActionRoundTrip(): void
    {
        $e = new PortefeuilleHistorique();
        $e->setAction('CREATE');
        $this->assertSame('CREATE', $e->getAction());
    }

    public function testAuteurRoundTrip(): void
    {
        $e = new PortefeuilleHistorique();
        $e->setAuteur('batch.collecte@ma-moulinette.fr');
        $this->assertSame('batch.collecte@ma-moulinette.fr', $e->getAuteur());
    }

    public function testRuleRoundTrip(): void
    {
        $e = new PortefeuilleHistorique();
        $e->setRule('S1135');
        $this->assertSame('S1135', $e->getRule());
    }

    public function testDescriptionRoundTrip(): void
    {
        $e = new PortefeuilleHistorique();
        $e->setDescription('description longue');
        $this->assertSame('description longue', $e->getDescription());
    }

    public function testDetailRoundTrip(): void
    {
        $e = new PortefeuilleHistorique();
        $e->setDetail('binary-blob-payload');
        $this->assertSame('binary-blob-payload', $e->getDetail());
    }

    public function testDateEnregistrementRoundTrip(): void
    {
        $e = new PortefeuilleHistorique();
        $d = new \DateTimeImmutable('2026-05-15T12:00:00+02:00');
        $e->setDateEnregistrement($d);
        $this->assertSame($d, $e->getDateEnregistrement());
    }

    public function testSettersAreChainable(): void
    {
        $e = new PortefeuilleHistorique();
        // setId retourne self (signature historique), setDateCourte/etc retournent static
        $this->assertSame($e, $e->setId(1));
        $this->assertSame($e, $e->setDateCourte(new \DateTimeImmutable()));
        $this->assertSame($e, $e->setLanguage('java'));
        $this->assertSame($e, $e->setDate(new \DateTimeImmutable()));
        $this->assertSame($e, $e->setAction('A'));
        $this->assertSame($e, $e->setAuteur('A'));
        $this->assertSame($e, $e->setRule('R'));
        $this->assertSame($e, $e->setDescription('D'));
        $this->assertSame($e, $e->setDetail('D'));
        $this->assertSame($e, $e->setDateEnregistrement(new \DateTimeImmutable()));
    }
}
