<?php

declare(strict_types=1);

namespace App\Model\Scrap;

class UrlContent
{
    public function __construct(public string $url, public ?string $title = null, public ?string $content = null) {}
}
