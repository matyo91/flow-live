<?php

declare(strict_types=1);

namespace App\Job\SymfonyCertification;

use App\EnumType\SymfonyCertification\CertificationEnumType;
use App\Model\SymfonyCertification\Topic;
use Flow\JobInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @implements JobInterface<CertificationEnumType, array<Topic>>
 */
class TopicsJobs implements JobInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {}

    public function __invoke($certificationType): array
    {
        [$url, $selector] = match ($certificationType) {
            CertificationEnumType::SYMFONY_7 => ['https://certification.symfony.com/exams/symfony.html', '#symfony7 .list-of-exam-topics > li'],
            CertificationEnumType::TWIG_3 => ['https://certification.symfony.com/exams/twig.html', '#twig3 .list-of-exam-topics > li'],
            CertificationEnumType::SYLIUS_1 => ['https://certification.symfony.com/exams/sylius.html', '.list-of-exam-topics > li'],
        };

        $response = $this->httpClient->request('GET', $url);
        $html = $response->getContent();

        $crawler = new Crawler($html);
        $topics = [];

        $crawler->filter($selector)->each(static function ($node) use (&$topics) {
            $topic = $node->filter('h3')->text();
            $items = [];

            $node->filter('ul li')->each(static function ($subNode) use (&$items) {
                $items[] = $subNode->text();
            });

            $topics[] = new Topic($topic, $items);
        });

        return $topics;
    }
}
