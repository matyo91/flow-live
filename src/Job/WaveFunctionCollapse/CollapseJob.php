<?php

declare(strict_types=1);

namespace App\Job\WaveFunctionCollapse;

use App\Model\WaveFunctionCollapse\Cell;
use Exception;
use Flow\JobInterface;

/**
 * @implements JobInterface<mixed, mixed>
 */
class CollapseJob implements JobInterface
{
    public function __construct(
        public array $tiles,
        public int $width,
        public int $height,
    )
    {}

    public function __invoke($grid): mixed
    {
        // Pick cell with least entropy
        $gridNoOptions = array_filter($grid, fn(Cell $a) => empty($a->getOptions()));
        $gridCopy = array_filter($grid, fn(Cell $a) => !$a->isCollapsed());
        if (!empty($gridNoOptions) || empty($gridCopy)) {
            return null;
        }

        usort($gridCopy, fn($a, $b) => count($a->getOptions()) - count($b->getOptions()));

        $len = count($gridCopy[0]->getOptions());
        $stopIndex = 0;
        for ($i = 1; $i < count($gridCopy); $i++) {
            if (count($gridCopy[$i]->getOptions()) > $len) {
                $stopIndex = $i;
                break;
            }
        }

        if ($stopIndex > 0) {
            array_splice($gridCopy, $stopIndex);
        }

        $cell = $gridCopy[array_rand($gridCopy)];
        $cell->setCollapsed(true);
        $pick = $cell->getOptions()[array_rand($cell->getOptions())];
        if ($pick === null) {
            return null;
        }
        $cell->setOptions([$pick]);

        $nextGrid = [];
        for ($j = 0; $j < $this->height; $j++) {
            for ($i = 0; $i < $this->width; $i++) {
                $index = $i + $j * $this->height;
                if ($grid[$index]->isCollapsed()) {
                    $nextGrid[$index] = $grid[$index];
                } else {
                    $options = range(0, count($this->tiles) - 1);

                    // Look up
                    if ($j > 0) {
                        $up = $grid[$i + ($j - 1) * $this->height];
                        $validOptions = [];
                        foreach ($up->getOptions() as $option) {
                            $valid = $this->tiles[$option]->down;
                            $validOptions = array_merge($validOptions, $valid);
                        }
                        $this->checkValid($options, $validOptions);
                    }
                    // Look right
                    if ($i < $this->width - 1) {
                        $right = $grid[$i + 1 + $j * $this->height];
                        $validOptions = [];
                        foreach ($right->getOptions() as $option) {
                            $valid = $this->tiles[$option]->left;
                            $validOptions = array_merge($validOptions, $valid);
                        }
                        $this->checkValid($options, $validOptions);
                    }
                    // Look down
                    if ($j < $this->height - 1) {
                        $down = $grid[$i + ($j + 1) * $this->height];
                        $validOptions = [];
                        foreach ($down->getOptions() as $option) {
                            $valid = $this->tiles[$option]->up;
                            $validOptions = array_merge($validOptions, $valid);
                        }
                        $this->checkValid($options, $validOptions);
                    }
                    // Look left
                    if ($i > 0) {
                        $left = $grid[$i - 1 + $j * $this->height];
                        $validOptions = [];
                        foreach ($left->getOptions() as $option) {
                            $valid = $this->tiles[$option]->right;
                            $validOptions = array_merge($validOptions, $valid);
                        }
                        $this->checkValid($options, $validOptions);
                    }

                    $nextGrid[$index] = new Cell($options);
                }
            }
        }

        return $nextGrid;
    }

    private function checkValid(array &$arr, array $valid): void
    {
        for ($i = count($arr) - 1; $i >= 0; $i--) {
            $element = $arr[$i];
            if (!in_array($element, $valid)) {
                array_splice($arr, $i, 1);
            }
        }
    }
}
