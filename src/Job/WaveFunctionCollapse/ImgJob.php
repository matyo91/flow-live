<?php

declare(strict_types=1);

namespace App\Job\WaveFunctionCollapse;

use App\EnumType\WaveFunctionCollapse\DataSetEnumType;
use App\Model\WaveFunctionCollapse\Cell;
use App\Model\WaveFunctionCollapse\Tile;
use Flow\JobInterface;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;

use function sprintf;

/**
 * @implements JobInterface<Cell[], ImageInterface>
 */
class ImgJob implements JobInterface
{
    public function __construct(
        private ImagineInterface $imagine,
        private string $assetsDir,
        /** @var Tile[] */
        public array $tiles,
        public int $width,
        public int $height,
        public DataSetEnumType $dataSet,
        public int $tileSize = 32,
    ) {}

    public function __invoke($grid): ImageInterface
    {
        $image = $this->imagine->create(new Box($this->width * $this->tileSize, $this->height * $this->tileSize));

        for ($j = 0; $j < $this->height; $j++) {
            for ($i = 0; $i < $this->width; $i++) {
                $index = $i + $j * $this->height;
                $cell = $grid[$index];
                if ($cell->isCollapsed()) {
                    $tile = $this->tiles[$cell->options[0]];
                    $tileImagePath = sprintf(
                        '%s/images/wave-function-collapse/%s/%d.png',
                        $this->assetsDir,
                        $this->dataSet->value,
                        $tile->index
                    );
                    $tileImage = $this->imagine->open($tileImagePath);

                    // Resize the tile image if it's not the correct size
                    if ($tileImage->getSize()->getWidth() !== $this->tileSize || $tileImage->getSize()->getHeight() !== $this->tileSize) {
                        $tileImage->resize(new Box($this->tileSize, $this->tileSize));
                    }

                    // Rotate the tile image based on its direction
                    $rotatedTileImage = $tileImage->rotate($tile->direction * 90);

                    $image->paste($rotatedTileImage, new Point($i * $this->tileSize, $j * $this->tileSize));
                }
            }
        }

        return $image;
    }
}
