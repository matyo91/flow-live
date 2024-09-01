<?php

declare(strict_types=1);

namespace App\Model;

class UrlContent
{
    public function __construct(public string $url, public ?string $content = null) {}
}
