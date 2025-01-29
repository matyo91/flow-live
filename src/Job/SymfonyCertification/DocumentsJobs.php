<?php

declare(strict_types=1);

namespace App\Job\SymfonyCertification;

use App\EnumType\SymfonyCertification\CertificationEnumType;
use Flow\JobInterface;
use LLPhant\Embeddings\Document;
use PharData;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use ValueError;

use function sprintf;

/**
 * @implements JobInterface<CertificationEnumType, array<Document>>
 */
class DocumentsJobs implements JobInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private HttpClientInterface $githubClient,
        private CacheInterface $cache,
        #[Autowire('%kernel.cache_dir%/symfony_certification')]
        private string $cacheDir,
    ) {}

    public function __invoke($certificationType): array
    {
        return match ($certificationType) {
            CertificationEnumType::SYMFONY_7 => array_merge(
                $this->retrieveGithubDocuments('symfony', 'symfony', '', '7.0'),
                $this->retrieveGithubDocuments('symfony', 'symfony-docs', '', '7.0'),
                $this->retrieveGzDocuments('https://www.php.net/distributions/manual/php_manual_en.tar.gz'),
            ),
            CertificationEnumType::TWIG_3 => $this->retrieveGithubDocuments('twigphp', 'Twig', '', '3.x'),
            CertificationEnumType::SYLIUS_1 => $this->retrieveGithubDocuments('Sylius', 'Sylius', '', '1.14'),
            default => throw new ValueError('Unsupported certification type'),
        };
    }

    /**
     * Retrieves documents from GitHub repositories.
     * Note: GitHub API is rate limited:
     * - For authenticated requests: 5,000 requests per hour
     * - For unauthenticated requests: 60 requests per hour.
     *
     * @see https://docs.github.com/en/rest/using-the-rest-api/rate-limits-for-the-rest-api
     *
     * @return array<Document>
     */
    private function retrieveGithubDocuments(string $owner, string $repo, string $path = '', string $ref = 'main'): array
    {
        $documents = [];
        $items = $this->cache->get('github_contents_' . md5(sprintf('/repos/%s/%s/contents/%s?ref=%s', $owner, $repo, $path, $ref)), function () use ($owner, $repo, $path, $ref) {
            $response = $this->githubClient->request('GET', sprintf('/repos/%s/%s/contents/%s?ref=%s', $owner, $repo, $path, $ref));

            return $response->toArray();
        });

        foreach ($items as $item) {
            if ($item['type'] === 'file' && (
                str_ends_with($item['name'], '.php')
                || str_ends_with($item['name'], '.yml')
                || str_ends_with($item['name'], '.rst')
            )) {
                // Get file content
                $fileData = $this->cache->get('github_contents_' . md5($item['url']), function () use ($item) {
                    $fileResponse = $this->githubClient->request('GET', $item['url']);

                    return $fileResponse->toArray();
                });

                $document = new Document();
                $document->content = base64_decode($fileData['content'], true);
                $document->sourceType = 'file';
                $document->sourceName = $item['url'];
                $document->hash = hash('sha256', $document->content);
                $documents[] = $document;
            }
            if ($item['type'] === 'dir') {
                $documents = array_merge(
                    $documents,
                    $this->retrieveGithubDocuments($owner, $repo, $item['path'], $ref)
                );
            }
        }

        return $documents;
    }

    /**
     * @return array<Document>
     */
    private function retrieveGzDocuments(string $url): array
    {
        $documents = [];

        $filesystem = new Filesystem();

        // Download and save gz file
        $response = $this->httpClient->request('GET', $url);
        $gzContent = $response->getContent();
        $tempGzPath = $this->cacheDir . '/temp_' . uniqid() . '.tar.gz';
        $filesystem->dumpFile($tempGzPath, $gzContent);

        // Extract gz file
        $extractPath = $this->cacheDir . '/extract_' . uniqid();
        $filesystem->mkdir($extractPath);

        $phar = new PharData($tempGzPath);
        $phar->extractTo($extractPath);

        // Read files recursively
        $finder = new Finder();
        $finder->files()
            ->in($extractPath)
            ->name('*.html')
        ;

        foreach ($finder as $file) {
            $content = $filesystem->readFile($file->getPathname());

            $document = new Document();
            $document->content = $content;
            $document->sourceType = 'gz_archive';
            $document->sourceName = $url . '#' . $file->getFilename();
            $document->hash = hash('sha256', $content);
            $documents[] = $document;
        }

        // Cleanup
        $filesystem->remove($tempGzPath);
        $filesystem->remove($extractPath);

        return $documents;
    }
}
