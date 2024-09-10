<?php

declare(strict_types=1);

namespace App\Job\Scrap;

use App\Model\UrlContent;
use Fiber;
use Flow\JobInterface;

/**
 * @implements JobInterface<array<UrlContent>, array<UrlContent>>
 */
class ScrapUrlsJob implements JobInterface
{
    public function __invoke($urlContents): array
    {
        // Initialize a cURL multi handle
        $mh = curl_multi_init();

        // Array to hold individual cURL handles
        $curl_handles = [];

        // Initialize individual cURL handles and add them to the multi handle
        foreach ($urlContents as $urlContent) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlContent->url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_multi_add_handle($mh, $ch);
            $curl_handles[] = [$ch, $urlContent];
        }

        // Execute the multi handle
        $running = null;
        do {
            curl_multi_exec($mh, $running);

            Fiber::suspend();
        } while ($running > 0);

        // Collect the content from each handle
        $urlContents = [];
        foreach ($curl_handles as $curl_handle) {
            [$ch, $urlContent] = $curl_handle;
            $urlContent->content = curl_multi_getcontent($ch);
            $urlContents[] = $urlContent;
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        // Close the multi handle
        curl_multi_close($mh);

        return $urlContents;
    }
}
