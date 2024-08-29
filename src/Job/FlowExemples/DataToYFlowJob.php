<?php

declare(strict_types=1);

namespace App\Job\Examples;

use App\Model\YFlowData;
use Flow\JobInterface;

class DataToYFlowJob implements JobInterface
{
    public function __invoke($data): YFlowData
    {
        return new YFlowData($data->id, $data->id, $data->id);
    }
}
