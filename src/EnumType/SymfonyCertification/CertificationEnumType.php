<?php

declare(strict_types=1);

namespace App\EnumType\SymfonyCertification;

enum CertificationEnumType: string
{
    case SYMFONY_7 = 'symfony7';
    case TWIG_3 = 'twig3';
    case SYLIUS_1 = 'sylius1';
}
