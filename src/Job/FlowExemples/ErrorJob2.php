<?php

declare(strict_types=1);

namespace App\Job\FlowExamples;

use Flow\JobInterface;

class ErrorJob2 implements JobInterface
{
    public function __invoke($exception): mixed
    {
        printf(".* %s\n", $exception->getMessage());
    }
}
