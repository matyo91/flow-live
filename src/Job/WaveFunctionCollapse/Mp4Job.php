<?php

declare(strict_types=1);

namespace App\Job\WaveFunctionCollapse;

use Flow\JobInterface;
use Imagine\Image\ImageInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

use function sprintf;

/**
 * @implements JobInterface<ImageInterface[], string>
 */
class Mp4Job implements JobInterface
{
    public function __construct(
        private string $cacheDir,
    ) {}

    public function __invoke($images): string
    {
        $filesystem = new Filesystem();
        $filesystem->mkdir($this->cacheDir);
        $generationId = 'wave_function_collapse_' . uniqid();

        // make frames
        foreach ($images as $i => $image) {
            $imagePath = sprintf('%s/%s_%03d.png', $this->cacheDir, $generationId, $i);
            $image->save($imagePath);
        }

        // animate
        $outputFile = sprintf('%s/%s.mp4', $this->cacheDir, $generationId);
        $cmd = sprintf(
            'ffmpeg -framerate 10 -i %s/%s_%%03d.png -vf fps=30 -c:v libx264 -pix_fmt yuv420p %s',
            $this->cacheDir,
            $generationId,
            $outputFile
        );

        $process = Process::fromShellCommandline($cmd);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput());
        }

        // cleanup
        $finder = new Finder();
        $finder->files()->in($this->cacheDir)->name(sprintf('%s_*.png', $generationId));
        foreach ($finder as $file) {
            $filesystem->remove($file->getRealPath());
        }

        return $outputFile;
    }
}
