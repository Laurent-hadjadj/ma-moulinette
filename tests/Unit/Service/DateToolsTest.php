<?php

namespace App\Tests\Unit\Service;

use App\Service\DateTools;
use PHPUnit\Framework\TestCase;

/**
 * [Description DateToolsTest]
 */
class DateToolsTest extends TestCase
{
    private DateTools $dateTools;

    protected function setUp(): void
    {
        $this->dateTools = new DateTools();
    }

    /**
     * Tests pour la méthode dateToMinute
     */
    public function testDateToMinute()
    {
        // Test avec jours, heures et minutes
        $this->assertEquals(2941, $this->dateTools->dateToMinute('2d1h1min'));

        // Test avec seulement heures et minutes
        $this->assertEquals(61, $this->dateTools->dateToMinute('1h1min'));

        // Test avec seulement minutes
        $this->assertEquals(15, $this->dateTools->dateToMinute('15min'));

        // Test avec seulement heures
        $this->assertEquals(120, $this->dateTools->dateToMinute('2h'));

        // Test avec jours seulement
        $this->assertEquals(2880, $this->dateTools->dateToMinute('2d'));
    }

    /**
     * Tests pour la méthode minutesTo
     */
    public function testMinutesTo()
    {
        // Test avec plusieurs jours
        $this->assertEquals('2d, 1h:1min', $this->dateTools->minutesTo(2941));

        // Test avec jours et heures
        $this->assertEquals('1d, 7h:0min', $this->dateTools->minutesTo(1860));

        // Test avec heures et minutes
        $this->assertEquals('1h:15min', $this->dateTools->minutesTo(75));

        // Test avec seulement minutes
        $this->assertEquals('0h:5min', $this->dateTools->minutesTo(5));
    }

    /**
     * Tests pour la méthode minutesToString
     */
    public function testMinutesToString()
    {
        // Test avec plusieurs jours
        $this->assertEquals('2d, 1h:1m', $this->dateTools->minutesToString(2941));

        // Test avec un seul jour
        $this->assertEquals('1d, 7h:0m', $this->dateTools->minutesToString(1860));

        // Test avec heures et minutes
        $this->assertEquals('0d, 1h:15m', $this->dateTools->minutesToString(75));

        // Test avec seulement minutes
        $this->assertEquals('0d, 0h:5m', $this->dateTools->minutesToString(5));

        // Test avec 0 minutes
        $this->assertEquals('0d, 0h:0m', $this->dateTools->minutesToString(0));
    }

    /**
     * Tests de robustesse
     */
    public function testRobustness()
    {
        // Test avec une chaîne vide pour dateToMinute
        $this->assertEquals(0, $this->dateTools->dateToMinute(''));

        // Test avec des minutes négatives
        $this->assertEquals('-1h:-5min', $this->dateTools->minutesTo(-65));

        // Test avec 0 minutes
        $this->assertEquals('0h:0min', $this->dateTools->minutesTo(0));
        $this->assertEquals('0d, 0h:0m', $this->dateTools->minutesToString(0));
    }
}
