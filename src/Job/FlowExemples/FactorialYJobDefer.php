<?php

declare(strict_types=1);

namespace App\Job\Examples;

use App\Model\YFlowData;
use Flow\JobInterface;

class FactorialYJobDefer implements JobInterface
{
    public function __invoke($factorial): mixed
    {
        return static function ($args) use ($factorial) {
            [$data, $defer] = $args;

            return $defer(static function ($complete, $async) use ($data, $defer, $factorial) {
                if ($data->result <= 1) {
                    $complete([new YFlowData($data->id, $data->number, 1), $defer]);
                } else {
                    $async($factorial([new YFlowData($data->id, $data->number, $data->result - 1), $defer]), static function ($result) use ($data, $complete) {
                        [$resultData, $defer] = $result;
                        $complete([new YFlowData($data->id, $data->number, $data->result * $resultData->result), $defer]);
                    });
                }
            });
        };
    }
}
