<?php

declare(strict_types=1);

namespace App\Model;

class Seo
{
    public function __construct(
        public ?string $page = null,
        public ?string $title = null,
        public ?string $description = null,
    ) {}
}
