<?php

namespace App\Support;

class LikeQuery
{
    public static function escape(string $term): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $term
        );
    }
}
