<?php

namespace App\Tests\Message;

use App\Message\ActivityMessage;
use PHPUnit\Framework\TestCase;

/**
 * [Description ActivityMessageTest]
 */
class ActivityMessageTest extends TestCase
{
  private static $fromDate = '2025-01-01 00:00:00';
  private static $toDate = '2025-12-31 23:59:59';

    /**
     * Teste la construction d'un ActivityMessage et les getters
     */
    public function testConstructorAndGetters()
    {
        $fromDate = static::$fromDate;
        $toDate = static::$toDate;

        $activityMessage = new ActivityMessage($fromDate, $toDate);

        // Vérifie que les dates sont correctement définies
        $this->assertSame($fromDate, $activityMessage->getFromDate());
        $this->assertSame($toDate, $activityMessage->getToDate());
    }

    /**
     * Teste les formats de date/heure valides
     */
    public function testValidDateTimeFormats()
    {
        $fromDate = static::$fromDate;
        $toDate = static::$toDate;

        $activityMessage = new ActivityMessage($fromDate, $toDate);

        // Vérifie le format "Y-m-d H:i:s"
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $activityMessage->getFromDate()
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $activityMessage->getToDate()
        );
    }

    /**
     * Teste le comportement avec des dates vides
     */
    public function testEmptyDates()
    {
        $fromDate = '';
        $toDate = '';

        $activityMessage = new ActivityMessage($fromDate, $toDate);

        $this->assertSame($fromDate, $activityMessage->getFromDate());
        $this->assertSame($toDate, $activityMessage->getToDate());
    }

    /**
     * Teste avec des formats de date/heure non valides
     */
    public function testInvalidDateTimeFormats()
    {
        $fromDate = '2025-01-01'; // Manque l'heure
        $toDate = '31/12/2025 23:59:59'; // Mauvais format (slash)

        $activityMessage = new ActivityMessage($fromDate, $toDate);

        // Même si le format est incorrect, les valeurs doivent être conservées
        $this->assertSame($fromDate, $activityMessage->getFromDate());
        $this->assertSame($toDate, $activityMessage->getToDate());
    }

    /**
     * Teste une plage de dates valide
     */
    public function testValidDateRange()
    {
        $fromDate = static::$fromDate;
        $toDate = static::$toDate;

        $activityMessage = new ActivityMessage($fromDate, $toDate);

        // Convertir les dates en timestamps
        $fromTimestamp = strtotime($activityMessage->getFromDate());
        $toTimestamp = strtotime($activityMessage->getToDate());

      // Vérifie que la date de début est bien avant ou égale à la date de fin
      $this->assertLessThanOrEqual($toTimestamp, $fromTimestamp, 'La date de début est postérieure à la date de fin.');
    }
}
