<?php

declare(strict_types=1);

namespace App\Command;

use App\IpStrategy\FlattenIpStrategy;
use App\Job\Scrap\ScrapUrlJob;
use App\Job\Scrap\ScrapUrlsJob;
use App\Model\Scrap\UrlContent;
use Fiber;
use Flow\AsyncHandler\DeferAsyncHandler;
use Flow\Driver\FiberDriver;
use Flow\Flow\Flow;
use Flow\Ip;
use Flow\Job\YJob;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\SluggerInterface;
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
    private string $cacheDir;
    private SluggerInterface $slugger;

    public function __construct(
        HttpClientInterface $httpClient,
        ParameterBagInterface $parameterBag,
        SluggerInterface $slugger
    ) {
        parent::__construct();
        $this->httpClient = $httpClient;
        $this->cacheDir = $parameterBag->get('kernel.cache_dir');
        $this->slugger = $slugger;
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
            yield function (UrlContent $urlData) use ($io) {
                $filesystem = new Filesystem();
                $scrapDir = $this->cacheDir . '/scrap';

                $filesystem->mkdir($scrapDir);

                $slug = $this->slugger->slug($urlData->title)->lower();

                $filename = $scrapDir . '/' . $slug . '.html';

                $filesystem->dumpFile($filename, $urlData->content);

                $io->writeln(sprintf('Content saved to: %s', $filename));
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

        $urls = [
            ['https://github.com/Jmgr/actiona', 'Actiona Cross platform automation tool'],
            ['https://github.com/tryanything-ai/anything', 'Anything Local Zapier replacement written in Rust'],
            ['https://airflow.apache.org', 'Apache Airflow'],
            ['http://apify.com', 'Apify'],
            ['https://itnext.io/smart-developers-dont-code-2bf882568c37', 'Apache Camel'],
            ['https://play.google.com/store/apps/details?id=com.llamalab.automate', 'Automate'],
            ['https://www.automa.site', 'Automa Automate your browser by connecting blocks'],
            ['https://www.blitznocode.com', 'Blitznocode'],
            ['https://fr.bonitasoft.com', 'Bonitasoft'],
            ['https://camunda.com', 'Camunda'],
            ['https://github.com/bolinfest/chickenfoot', 'Chickenfoot'],
            ['https://github.com/DataFire/DataFire', 'Datafire'],
            ['https://github.com/antonmi/flowex', 'Flowex'],
            ['http://www.flogo.io', 'Flogo'],
            ['https://www.flyde.dev', 'Flyde Visual Programming on VS Code'],
            ['https://developers.google.com/blockly', 'Google Blockly'],
            ['https://gluedata.io', 'Gluedata'],
            ['https://github.com/huginn/huginn', 'Huginn'],
            ['https://ifttt.com', 'IFTTT'],
            ['https://www.integromat.com', 'Integromat'],
            ['https://github.com/integrate-io', 'Integrate-io'],
            ['https://github.com/kestra-io/kestra', 'Kestra'],
            ['https://www.levity.ai', 'Levity'],
            ['https://github.com/n8n-io/n8n', 'n8n.io'],
            ['https://noflojs.org', 'NoFlo'],
            ['https://nodered.org', 'Nodered'],
            ['https://parabola.io', 'Parabola'],
            ['https://www.prefect.io', 'Prefect'],
            ['https://pipedream.com', 'Pipedream'],
            ['https://www.refinery.io', 'Refinery.io'],
            ['https://scratch.mit.edu', 'Scratch'],
            ['https://apps.apple.com/us/app/scriptable/id1405459188', 'Scriptable.app'],
            ['https://apps.apple.com/us/app/shortcuts/id915249334', 'Shortcut for iOS'],
            ['https://github.com/pfgithub/scpl', 'Shocut like for Mac OS'],
            ['https://github.com/steventroughtonsmith/shortcuts-iosmac', 'Shortcut like'],
            ['https://skyvia.com', 'Skyvia'],
            ['https://github.com/temporalio/samples-php', 'Temporal'],
            ['https://titanoboa.io', 'Titanoboa'],
            ['https://tray.io', 'Tray.io'],
            ['https://ui.vision/x/desktop-automation', 'UIVision'],
            ['https://www.workato.com', 'Workato'],
            ['https://zapier.com', 'Zapier'],
        ];

        $datas = array_map(static function ($data) {
            [$url, $title] = $data;

            return new UrlContent($url, $title);
        }, $urls);

        $flow(new Ip($datas));

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
