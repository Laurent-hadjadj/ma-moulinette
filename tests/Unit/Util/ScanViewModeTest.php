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

namespace App\Tests\Unit\Util;

use App\Util\ScanViewMode;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-14 : tests Unit du
 * helper de normalisation du paramètre `?vue=` partage par les
 * pages agrégées Dependency-Check.
 */
class ScanViewModeTest extends TestCase
{
    public function testNormalizeDefaultsToProd(): void
    {
        $this->assertSame(ScanViewMode::PROD, ScanViewMode::normalize(null));
        $this->assertSame(ScanViewMode::PROD, ScanViewMode::normalize(''));
        $this->assertSame(ScanViewMode::PROD, ScanViewMode::normalize('garbage'));
        $this->assertSame(ScanViewMode::PROD, ScanViewMode::normalize('PROD'));  // case-sensitive volontaire
    }

    public function testNormalizeRecognizesDev(): void
    {
        $this->assertSame(ScanViewMode::DEV, ScanViewMode::normalize('dev'));
    }

    public function testNormalizeRecognizesProd(): void
    {
        $this->assertSame(ScanViewMode::PROD, ScanViewMode::normalize('prod'));
    }

    public function testFlagColumnMapping(): void
    {
        $this->assertSame('is_latest_release', ScanViewMode::flagColumn(ScanViewMode::PROD));
        $this->assertSame('is_latest_overall', ScanViewMode::flagColumn(ScanViewMode::DEV));
    }

    public function testConstantsAreStable(): void
    {
        $this->assertSame('prod',                ScanViewMode::PROD);
        $this->assertSame('dev',                 ScanViewMode::DEV);
        $this->assertSame(ScanViewMode::PROD,    ScanViewMode::DEFAULT);
    }
}
