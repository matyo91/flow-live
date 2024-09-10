<?php

declare(strict_types=1);

namespace App\Job\Scrap;

use App\Model\UrlContent;
use CurlMultiHandle;
use Fiber;
use Flow\JobInterface;

/**
 * @implements JobInterface<UrlContent, UrlContent>
 */
class ScrapUrlJob implements JobInterface
{
    private CurlMultiHandle $mh;

    public function __construct()
    {
        // Initialize a cURL multi handle
        $this->mh = curl_multi_init();
    }

    public function __destruct()
    {
        curl_multi_close($this->mh);
    }

    public function __invoke($urlContent): mixed
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlContent->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_multi_add_handle($this->mh, $ch);

        do {
            $status = curl_multi_exec($this->mh, $active);
            curl_multi_exec($this->mh, $active);

            Fiber::suspend();

            $info = curl_multi_info_read($this->mh);
        } while (
            $active && $status === CURLM_OK // check curl_multi is active
            && !($info !== false && $info['handle'] === $ch && $info['result'] === CURLE_OK) // check $ch is done
        );

        $content = curl_multi_getcontent($ch);
        curl_multi_remove_handle($this->mh, $ch);
        curl_close($ch);

        return new UrlContent($urlContent->url, $content);
    }
}
