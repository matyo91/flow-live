<?php

declare(strict_types=1);

namespace App\Model\SymfonyCertification;

class Topic
{
    /**
     * @param array<string> $items
     */
    public function __construct(
        public string $topic,
        public array $items
    ) {}
}
