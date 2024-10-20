<?php

declare(strict_types=1);

namespace App\Job\WaveFunctionCollapse;

use App\EnumType\WaveFunctionCollapse\DataSetEnumType;
use App\Model\WaveFunctionCollapse\Board;
use App\Model\WaveFunctionCollapse\Tile;
use Flow\JobInterface;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;

use function sprintf;

/**
 * @implements JobInterface<Board, ImageInterface>
 */
class ImgJob implements JobInterface
{
    public function __construct(
        private ImagineInterface $imagine,
        private string $assetsDir,
        public DataSetEnumType $dataSet,
        public int $tileSize = 32,
    ) {}

    public function __invoke($board): ImageInterface
    {
        $image = $this->imagine->create(new Box($board->width * $this->tileSize, $board->height * $this->tileSize));

        for ($j = 0; $j < $board->height; $j++) {
            for ($i = 0; $i < $board->width; $i++) {
                $index = $i + $j * $board->height;
                $cell = $board->grid[$index];
                if ($cell->isCollapsed()) {
                    $tile = $board->tiles[$cell->options[0]];
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
