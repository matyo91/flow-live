<?php

declare(strict_types=1);

namespace App\Command;

use App\Job\Scrap\ScrapUrlJob;
use App\Job\Scrap\ScrapUrlsJob;
use App\Model\UrlContent;
use Fiber;
use Flow\AsyncHandler\DeferAsyncHandler;
use Flow\Driver\FiberDriver;
use Flow\Flow\Flow;
use Flow\Ip;
use Flow\IpStrategy\FlattenIpStrategy;
use Flow\Job\YJob;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function count;
use function sprintf;

#[AsCommand(
    name: 'app:scrap',
    description: 'This allows scrap pages with flow',
)]
class ScrapCommand extends Command
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        parent::__construct();
        $this->httpClient = $httpClient;
    }

    /**
     * Get user data including todos and posts.
     *
     * @param array<mixed>        $user       The user data
     * @param HttpClientInterface $httpClient The HTTP client for making requests
     *
     * @return array<mixed> The user data with todos and posts added
     */
    public function getUserData($user, HttpClientInterface $httpClient): array
    {
        $userId = $user['id'];
        $todosUrl = "https://jsonplaceholder.typicode.com/users/{$userId}/todos";
        $postsUrl = "https://jsonplaceholder.typicode.com/users/{$userId}/posts";

        $responses = [
            'todos' => $httpClient->request('GET', $todosUrl),
            'posts' => $httpClient->request('GET', $postsUrl),
        ];

        Fiber::suspend();

        $todos = $responses['todos']->toArray();
        $posts = $responses['posts']->toArray();

        $user['todos'] = $todos;
        $user['posts'] = $posts;

        return $user;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $driver = new FiberDriver();

        $flow = Flow::do(function () use ($io) {
            yield new ScrapUrlsJob();
            yield static function (array $urlDatas) use ($io) {
                $io->writeln(sprintf('ScrapUrlsJob   : Finished scrapping %d urls', count($urlDatas)));

                return $urlDatas;
            };
            yield [new ScrapUrlJob(), null, new FlattenIpStrategy()];
            yield static function (UrlContent $urlData) use ($io) {
                $io->writeln(sprintf('ScrapUrlJob    : Finished scrapping %s', $urlData->url));
            };

            yield static fn () => [null, []];
            yield new YJob(function ($rec) {
                return function ($data) use ($rec) {
                    [$i, $users] = $data;
                    if ($i === null) {
                        $response = $this->httpClient->request('GET', 'https://jsonplaceholder.typicode.com/users');
                        Fiber::suspend();
                        $users = $response->toArray();

                        return $rec([0, $users]);
                    }
                    if ($i >= 0 && $i < count($users)) {
                        $users[$i] = $this->getUserData($users[$i], $this->httpClient);

                        return $rec([$i + 1, $users]);
                    }

                    return $users;
                };
            });
            yield static function ($users) use ($io) {
                $io->writeln(sprintf('ScrapYJob      : Finished scrapping %d', count($users)));
            };

            yield static fn () => [null, []];
            yield [new YJob(function ($rec) {
                return function ($args) use ($rec) {
                    [$data, $defer] = $args;

                    return $defer(function ($complete, $async) use ($data, $defer, $rec) {
                        [$i, $users] = $data;
                        if ($i === null) {
                            $response = $this->httpClient->request('GET', 'https://jsonplaceholder.typicode.com/users');
                            Fiber::suspend();
                            $users = $response->toArray();

                            $async($rec([[0, $users], $defer]), static function ($result) use ($complete) {
                                $complete($result);
                            });
                        } elseif ($i >= 0 && $i < count($users)) {
                            $users[$i] = $this->getUserData($users[$i], $this->httpClient);

                            $async($rec([[$i + 1, $users], $defer]), static function ($result) use ($complete) {
                                $complete($result);
                            });
                        } else {
                            $complete([$users, $defer]);
                        }
                    });
                };
            }), null, null, null, new DeferAsyncHandler()];
            yield static function ($users) use ($io) {
                $io->writeln(sprintf('ScrapYDeferJob : Finished scrapping %d', count($users)));
            };
        }, ['driver' => $driver]);

        $flow(new Ip([
            new UrlContent('https://www.google.fr'),
            new UrlContent('https://www.apple.com'),
            new UrlContent('https://www.microsoft.com'),
            new UrlContent('https://www.amazon.com'),
            new UrlContent('https://www.facebook.com'),
            new UrlContent('https://www.netflix.com'),
            new UrlContent('https://www.spotify.com'),
            new UrlContent('https://www.wikipedia.org'),
            new UrlContent('https://www.x.com'),
            new UrlContent('https://www.instagram.com'),
            new UrlContent('https://www.linkedin.com'),
            new UrlContent('https://www.reddit.com'),
            new UrlContent('https://www.ebay.com'),
            new UrlContent('https://www.cnn.com'),
            new UrlContent('https://www.bbc.co.uk'),
            new UrlContent('https://www.yahoo.com'),
            new UrlContent('https://www.bing.com'),
            new UrlContent('https://www.pinterest.com'),
            new UrlContent('https://www.tumblr.com'),
            new UrlContent('https://www.paypal.com'),
            new UrlContent('https://www.dropbox.com'),
            new UrlContent('https://www.adobe.com'),
            new UrlContent('https://www.salesforce.com'),
        ]));

        $flow->await();

        $io->success('Scraping is done.');

        return Command::SUCCESS;
    }
}
