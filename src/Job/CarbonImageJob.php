<?php

declare(strict_types=1);

namespace App\Job\FlowExamples;

use App\Model\CarbonImage;
use Exception;
use Flow\JobInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Panther\Client;

use function array_key_exists;
use function in_array;
use function sprintf;

/**
 * Sources from https://github.com/mixn/carbon-now-cli.
 *
 * @implements JobInterface<CarbonImage, CarbonImage>
 */
class CarbonImageJob implements JobInterface
{
    private const CARBON_URL = 'https://carbon.now.sh/';
    private const CARBON_CUSTOM_THEME = 'custom';

    /** @var array<string, mixed> */
    private array $config = [];

    /** @var array<string, string> */
    private array $keyMap = [
        'backgroundColor' => 'bg',
        'dropShadow' => 'ds',
        'dropShadowBlurRadius' => 'dsblur',
        'dropShadowOffsetY' => 'dsyoff',
        'exportSize' => 'es',
        'firstLineNumber' => 'fl',
        'fontFamily' => 'fm',
        'fontSize' => 'fs',
        'language' => 'l',
        'lineHeight' => 'lh',
        'lineNumbers' => 'ln',
        'paddingHorizontal' => 'ph',
        'paddingVertical' => 'pv',
        'selectedLines' => 'sl',
        'squaredImage' => 'si',
        'theme' => 't',
        'watermark' => 'wm',
        'widthAdjustment' => 'wa',
        'windowControls' => 'wc',
        'windowTheme' => 'wt',
    ];

    private string $type;
    private Client $client;
    private string $browserType;

    public function __construct(
        string $configFilePath,
        string $browserType = 'chrome',
        bool $disableHeadless = false,
        string $type = 'png'
    ) {
        if (!in_array($type, ['png', 'svg'], true)) {
            throw new InvalidArgumentException('Invalid type. Only png and svg are supported.');
        }

        $this->type = $type;
        $this->browserType = $browserType;

        $this->init($disableHeadless);
        $this->loadConfig($configFilePath);
    }

    /**
     * @param CarbonImage $carbonImage
     */
    public function __invoke($carbonImage): mixed
    {
        $url = $this->getTransformedUrl($carbonImage->code, $this->config);
        // $this->download($url, $carbonImage->path);

        return new CarbonImage($carbonImage->code, $carbonImage->path, $url);
    }

    /**
     * Load the configuration from a JSON file.
     *
     * @throws RuntimeException if the file cannot be read or the JSON is invalid
     */
    public function loadConfig(string $configFilePath): void
    {
        if (!file_exists($configFilePath) || !is_readable($configFilePath)) {
            throw new RuntimeException("Configuration file not found or unreadable: {$configFilePath}");
        }

        $configContent = file_get_contents($configFilePath);
        $this->config = json_decode($configContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid JSON in configuration file: ' . json_last_error_msg());
        }
    }

    public function encodeURIComponent(string $str): string
    {
        $revert = ['%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')'];

        return strtr(rawurlencode($str), $revert);
    }

    /**
     * Constructs the URL for the Carbon API with the given settings and code.
     *
     * @param array<string, mixed> $settings
     */
    public function getTransformedUrl(string $code, array $settings): string
    {
        // Encode the content
        $encodedContent = $this->encodeURIComponent($code);

        // Transform settings into query parameters
        $queryParams = $this->transformToQueryParams($settings);

        // Add the encoded content to the query parameters
        $queryParams['code'] = $encodedContent;

        // If custom theme is used, add it to the query parameters
        if (!empty($settings['custom'])) {
            $queryParams['t'] = self::CARBON_CUSTOM_THEME;
        }

        // Build the query string
        $queryString = http_build_query($queryParams);

        // Return the full URL
        return self::CARBON_URL . '?' . $queryString;
    }

    /**
     * @param array<string, string> $highlights
     */
    public function setCustomTheme(array $highlights): void
    {
        $script = <<<'JS'
        (function() {
            const themes = [
                {
                    id: 'custom',
                    name: 'custom',
                    highlights: %s,
                    custom: true,
                },
            ];
            window.localStorage.setItem('CARBON_LOCAL_STORAGE_KEY', JSON.stringify(themes));
        })();
        JS;

        $this->client->executeScript(sprintf($script, json_encode($highlights)));
    }

    public function download(string $url, string $path): void
    {
        try {
            // Navigate to the URL and trigger download
            $this->navigate($url);

            // Take a screenshot as an alternative to direct download
            $this->client->takeScreenshot($path);
        } catch (Exception $e) {
            throw new RuntimeException('Failed to download the image: ' . $e->getMessage());
        } finally {
            $this->client->quit();
        }
    }

    /**
     * Transforms the settings array into query parameters using the key map.
     *
     * @param array<string, mixed> $settings
     *
     * @return array<string, string>
     */
    private function transformToQueryParams(array $settings): array
    {
        $queryParams = [];

        foreach ($settings as $key => $value) {
            if (array_key_exists($key, $this->keyMap)) {
                $queryParams[$this->keyMap[$key]] = $value;
            }
        }

        return $queryParams;
    }

    private function init(bool $disableHeadless): void
    {
        $options = [
            'headless' => !$disableHeadless,
        ];

        $this->client = match ($this->browserType) {
            'chrome' => Client::createChromeClient(null, null, $options),
            'firefox' => Client::createFirefoxClient(null, null, $options),
            default => Client::createChromeClient(null, null, $options),
        };
    }

    private function navigate(string $url): void
    {
        $this->client->request('GET', $url);
        $this->client->waitFor('#export-menu');

        $crawler = $this->client->getCrawler();
        $exportMenu = $crawler->filter('#export-menu');
        $exportMenu->click();

        $exportOption = $crawler->filter(sprintf('#export-%s', $this->type));
        $exportOption->click();
    }
}
