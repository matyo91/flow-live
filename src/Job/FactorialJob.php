<?php

declare(strict_types=1);

namespace App\Job;

use App\Model\YFlowData;
use Flow\JobInterface;

class FactorialJob implements JobInterface
{
    public function __invoke($data): mixed
    {
        if (!$data instanceof YFlowData) {
            throw new \InvalidArgumentException('Expected an instance of YFlowData');
        }

        printf("*... #%d - Job 1 : Calculating factorial(%d)\n", $data->id, $data->number);

        // raw factorial calculation
        $result = $this->factorial($data->number);

        printf("*... #%d - Job 1 : Result for factorial(%d) = %d\n", $data->id, $data->number, $result);

        return new YFlowData($data->id, $data->number);
    }

    private function factorial(int $n): int
    {
        return ($n <= 1) ? 1 : $n * factorial($n - 1);
    }
}
