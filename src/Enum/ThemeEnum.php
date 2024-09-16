<?php

declare(strict_types=1);

namespace App\Enum;

enum ThemeEnum: string
{
    case LIGHT = 'light';
    case DARK = 'dark';
    case SEPIA = 'sepia';
}
