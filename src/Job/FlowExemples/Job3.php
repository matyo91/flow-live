<?php

declare(strict_types=1);

namespace App\Job\FlowExamples;

use App\Model\FlowExemples\DataD;
use Flow\JobInterface;

/**
 * @implements JobInterface<mixed, mixed>
 */
class Job3 implements JobInterface
{
    public function __invoke($dataC): mixed
    {
        printf("** #%d - Job 3 Result is %d\n", $dataC->id, $dataC->f);

        return new DataD($dataC->id);
    }
}
