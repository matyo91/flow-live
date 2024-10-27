<?php

declare(strict_types=1);

namespace App\Job\FlowExamples;

use App\Model\FlowExemples\YFlowData;
use Flow\JobInterface;
use InvalidArgumentException;

/**
 * @implements JobInterface<mixed, mixed>
 */
class FactorialJob implements JobInterface
{
    public function __invoke($data): mixed
    {
        if (!$data instanceof YFlowData) {
            throw new InvalidArgumentException('Expected an instance of YFlowData');
        }

        printf("*... #%d - Job 1 : Calculating factorial(%d)\n", $data->id, $data->number);

        // raw factorial calculation
        $result = $this->factorial($data->number);

        printf("*... #%d - Job 1 : Result for factorial(%d) = %d\n", $data->id, $data->number, $result);

        return new YFlowData($data->id, $data->number);
    }

    private function factorial(int $n): int
    {
        return ($n <= 1) ? 1 : $n * $this->factorial($n - 1);
    }
}
