<?php

namespace App\Support;

class SpreadsheetSafeString
{
    /**
     * Cegah spreadsheet formula injection (CSV/XLSX) — nilai string
     * yang diawali karakter formula di-"escape" dengan apostrof.
     */
    public static function make(mixed $value): string
    {
        $text = (string) $value;

        if ($text === '') {
            return $text;
        }

        if (in_array($text[0], ['=', '+', '-', '@'], true)) {
            return "'".$text;
        }

        return $text;
    }
}
