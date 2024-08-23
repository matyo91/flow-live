<?php

declare(strict_types=1);

namespace App\Job;

use App\Model\YFlowData;
use Flow\JobInterface;

class FactorialYJob implements JobInterface
{
    public function __invoke($factorial): mixed
    {
        return static function ($data) use ($factorial) {
            return new YFlowData(
                $data->id,
                $data->number,
                ($data->result <= 1) ? 1 : $data->result * $factorial(new YFlowData($data->id, $data->number, $data->result - 1))->result
            );
        };
    }
}
