<?php

declare(strict_types=1);

namespace App\Job\WaveFunctionCollapse;

use App\Model\WaveFunctionCollapse\Board;
use App\Model\WaveFunctionCollapse\Cell;
use Flow\JobInterface;

use function count;
use function in_array;

/**
 * @implements JobInterface<Board, Board|null>
 */
class CollapseJob implements JobInterface
{
    public function __invoke($board): mixed
    {
        // Pick cell with least entropy
        $gridNoOptions = array_filter($board->grid, static fn (Cell $a) => empty($a->getOptions()));
        $gridCopy = array_filter($board->grid, static fn (Cell $a) => !$a->isCollapsed());
        if (!empty($gridNoOptions) || empty($gridCopy)) {
            return null;
        }

        usort($gridCopy, static fn ($a, $b) => count($a->getOptions()) - count($b->getOptions()));

        $len = count($gridCopy[0]->getOptions());
        $stopIndex = 0;
        $gridCopyCount = count($gridCopy);
        for ($i = 1; $i < $gridCopyCount; $i++) {
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
        if (empty($cell->getOptions())) {
            return null;
        }
        $pick = $cell->getOptions()[array_rand($cell->getOptions())];
        $cell->setOptions([$pick]);

        $nextGrid = [];
        for ($j = 0; $j < $board->height; $j++) {
            for ($i = 0; $i < $board->width; $i++) {
                $index = $i + $j * $board->height;
                if ($board->grid[$index]->isCollapsed()) {
                    $nextGrid[$index] = $board->grid[$index];
                } else {
                    $options = range(0, count($board->tiles) - 1);

                    // Look up
                    if ($j > 0) {
                        $up = $board->grid[$i + ($j - 1) * $board->height];
                        $validOptions = [];
                        foreach ($up->getOptions() as $option) {
                            $valid = $board->tiles[$option]->down;
                            $validOptions = array_merge($validOptions, $valid);
                        }
                        $this->checkValid($options, $validOptions);
                    }
                    // Look right
                    if ($i < $board->width - 1) {
                        $right = $board->grid[$i + 1 + $j * $board->height];
                        $validOptions = [];
                        foreach ($right->getOptions() as $option) {
                            $valid = $board->tiles[$option]->left;
                            $validOptions = array_merge($validOptions, $valid);
                        }
                        $this->checkValid($options, $validOptions);
                    }
                    // Look down
                    if ($j < $board->height - 1) {
                        $down = $board->grid[$i + ($j + 1) * $board->height];
                        $validOptions = [];
                        foreach ($down->getOptions() as $option) {
                            $valid = $board->tiles[$option]->up;
                            $validOptions = array_merge($validOptions, $valid);
                        }
                        $this->checkValid($options, $validOptions);
                    }
                    // Look left
                    if ($i > 0) {
                        $left = $board->grid[$i - 1 + $j * $board->height];
                        $validOptions = [];
                        foreach ($left->getOptions() as $option) {
                            $valid = $board->tiles[$option]->right;
                            $validOptions = array_merge($validOptions, $valid);
                        }
                        $this->checkValid($options, $validOptions);
                    }

                    $nextGrid[$index] = new Cell($options);
                }
            }
        }
        $board->grid = $nextGrid;

        return $board;
    }

    /**
     * @param array<int> $arr   The array of options to check and potentially modify
     * @param array<int> $valid The array of valid options to compare against
     */
    private function checkValid(array &$arr, array $valid): void
    {
        for ($i = count($arr) - 1; $i >= 0; $i--) {
            $element = $arr[$i];
            if (!in_array($element, $valid, true)) {
                array_splice($arr, $i, 1);
            }
        }
    }
}
