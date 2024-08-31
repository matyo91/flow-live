<?php

declare(strict_types=1);

namespace App\Model;

readonly class CarbonImage
{
    public function __construct(public string $code, public string $path, public ?string $url = null) {}
}
