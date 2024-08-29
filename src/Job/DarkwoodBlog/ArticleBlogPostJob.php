<?php

declare(strict_types=1);

namespace App\Job\FlowExamples;

use App\Model\YFlowData;
use Flow\JobInterface;

class ArticleBlogPostJob implements JobInterface
{
    public function __invoke($data): YFlowData
    {
        return new YFlowData($data->id, $data->id, $data->id);
    }
}
