<?php

declare(strict_types=1);

namespace App\Job\FlowExamples;

use App\Model\FlowExemples\YFlowData;
use Flow\JobInterface;

/**
 * @implements JobInterface<mixed, mixed>
 */
class FactorialYJobAfter implements JobInterface
{
    public function __invoke($data): mixed
    {
        printf(".*.. #%d - Job 2 : Result for factorialYJob(%d) = %d\n", $data->id, $data->number, $data->result);

        return new YFlowData($data->id, $data->number);
    }
}
