<?php

declare(strict_types=1);

namespace App\Model;

class YFlowData
{
    public function __construct(public int $id, public ?int $number, public ?int $result = null) {}
}
