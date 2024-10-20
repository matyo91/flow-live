<?php

declare(strict_types=1);

namespace App\Job\FlowExamples;

use App\Model\FlowExemples\YFlowData;
use Flow\JobInterface;

/**
 * @implements JobInterface<mixed, mixed>
 */
class DataToYFlowJob implements JobInterface
{
    public function __invoke($data): YFlowData
    {
        return new YFlowData($data->id, $data->id, $data->id);
    }
}
