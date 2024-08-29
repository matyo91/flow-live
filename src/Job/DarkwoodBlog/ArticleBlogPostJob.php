<?php

declare(strict_types=1);

namespace App\Job\DarkwoodBlog;

use App\Model\FlowExemples\YFlowData;
use Flow\JobInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ArticleBlogPostJob implements JobInterface
{
    public function __construct(private HttpClientInterface $httpClient)
    {
        
    }

    public function __invoke($data): YFlowData
    {
        return new YFlowData($data->id, $data->id, $data->id);
    }
}
