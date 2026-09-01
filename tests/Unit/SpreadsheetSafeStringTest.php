<?php

namespace Tests\Unit;

use App\Support\SpreadsheetSafeString;
use PHPUnit\Framework\TestCase;

class SpreadsheetSafeStringTest extends TestCase
{
    public function test_cells_starting_with_formula_chars_are_escaped(): void
    {
        $this->assertSame("'=SUM(A1)", SpreadsheetSafeString::make('=SUM(A1)'));
        $this->assertSame("'+cmd|' /C calc", SpreadsheetSafeString::make('+cmd|\' /C calc'));
        $this->assertSame("'-2+3", SpreadsheetSafeString::make('-2+3'));
        $this->assertSame("'@SUM(A1)", SpreadsheetSafeString::make('@SUM(A1)'));
    }

    public function test_normal_cells_unchanged(): void
    {
        $this->assertSame('PT Sumber Rejeki', SpreadsheetSafeString::make('PT Sumber Rejeki'));
        $this->assertSame('INV/2026/09/000001', SpreadsheetSafeString::make('INV/2026/09/000001'));
        $this->assertSame('12345', SpreadsheetSafeString::make(12345));
        $this->assertSame("'=sama", SpreadsheetSafeString::make('=sama'));
    }

    public function test_empty_string_unchanged(): void
    {
        $this->assertSame('', SpreadsheetSafeString::make(''));
    }
}
