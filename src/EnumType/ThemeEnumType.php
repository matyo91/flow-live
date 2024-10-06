<?php

declare(strict_types=1);

namespace App\EnumType;

enum ThemeEnumType: string
{
    case LIGHT = 'light';
    case DARK = 'dark';
    case SEPIA = 'sepia';
}
