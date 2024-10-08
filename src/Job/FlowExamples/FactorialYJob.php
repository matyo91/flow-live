<?php

declare(strict_types=1);

namespace App\Job\FlowExamples;

use App\Model\FlowExemples\YFlowData;
use Flow\JobInterface;

/**
 * @implements JobInterface<mixed, mixed>
 */
class FactorialYJob implements JobInterface
{
    public function __invoke($factorial): mixed
    {
        return static function (YFlowData $data) use ($factorial) {
            return new YFlowData(
                $data->id,
                $data->number,
                ($data->result <= 1) ? 1 : $data->result * $factorial(new YFlowData($data->id, $data->number, $data->result - 1))->result
            );
        };
    }
}
