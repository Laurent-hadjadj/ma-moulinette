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

namespace App\Tests\Unit\Service;

use App\Service\DateTools;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateTools::class)]
final class DateToolsTest extends TestCase
{
    private DateTools $service;

    protected function setUp(): void
    {
        $this->service = new DateTools();
    }

    /**
     * @param int $expected
     */
    #[DataProvider('dateToMinuteProvider')]
    public function testDateToMinuteParsesEffortStrings(string $input, int $expected): void
    {
        self::assertSame($expected, (int) $this->service->dateToMinute($input));
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function dateToMinuteProvider(): array
    {
        return [
            'days hours minutes' => ['2d1h1min', 2 * 1440 + 60 + 1],
            'hours and minutes only' => ['1h30min', 90],
            'minutes only' => ['45min', 45],
            'days only' => ['3d', 3 * 1440],
            'zero' => ['0', 0],
        ];
    }

    #[DataProvider('minutesToProvider')]
    public function testMinutesToFormatsHumanReadable(int $minutes, string $expected): void
    {
        self::assertSame($expected, $this->service->minutesTo($minutes));
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function minutesToProvider(): array
    {
        return [
            'minutes only' => [30, '0h:30min'],
            'one hour' => [60, '1h:0min'],
            'one hour thirty' => [90, '1h:30min'],
            'one day' => [1440, '1d, 0h:0min'],
            'one day, two hours, fifteen minutes' => [1440 + 120 + 15, '1d, 2h:15min'],
            'zero' => [0, '0h:0min'],
        ];
    }

    public function testMinutesToStringFormatsWithSprintf(): void
    {
        self::assertSame('0d, 0h:0m', $this->service->minutesToString(0));
        self::assertSame('0d, 1h:30m', $this->service->minutesToString(90));
        self::assertSame('1d, 0h:0m', $this->service->minutesToString(1440));
        self::assertSame('2d, 4h:5m', $this->service->minutesToString(2 * 1440 + 4 * 60 + 5));
    }

    public function testRoundTripDateToMinuteAndBack(): void
    {
        $minutes = (int) $this->service->dateToMinute('2d1h30min');
        self::assertSame(2 * 1440 + 60 + 30, $minutes);
        self::assertSame('2d, 1h:30min', $this->service->minutesTo($minutes));
    }
}
