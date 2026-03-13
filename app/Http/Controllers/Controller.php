<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Escape LIKE wildcard characters in a search string.
     */
    protected function escapeLike(string $value): string
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $value);
    }
}
