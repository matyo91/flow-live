<?php

declare(strict_types=1);

namespace App\Job;

use App\Model\DataC;
use App\Model\DataD;
use Flow\JobInterface;

class Job3 implements JobInterface
{
    public function __invoke($dataC): mixed
    {
        printf("** #%d - Job 3 Result is %d\n", $dataC->id, $dataC->f);

        return new DataD($dataC->id);
    }
}
