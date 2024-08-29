<?php

declare(strict_types=1);

namespace App\Job\Examples;

use App\Model\YFlowData;
use Flow\JobInterface;

class FactorialYJobDeferAfter implements JobInterface
{
    public function __invoke($args): mixed
    {
        [$data, $defer] = $args;

        return $defer(static function ($complete) use ($data, $defer) {
            printf("...* #%d - Job 4 : Result for factorialYJobDefer(%d) = %d\n", $data->id, $data->number, $data->result);

            $complete([new YFlowData($data->id, $data->number), $defer]);
        });
    }
}
