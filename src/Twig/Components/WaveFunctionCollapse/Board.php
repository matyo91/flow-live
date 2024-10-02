<?php

namespace App\Twig\Components\WaveFunctionCollapse;

use App\Model\WaveFunctionCollapse\Cell;
use App\Model\WaveFunctionCollapse\Tile;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent]
final class Board
{
    use DefaultActionTrait;

    /** @var Tile[] */
    public array $tiles = [];
    public array $grid = [];

    public int $width = 0;
    public int $height = 0;

    public function mount($width, $height): void
    {
        $this->width = (int) $width;
        $this->height = (int) $height;

        $this->tiles[0] = new Tile(0, ['AAA', 'AAA', 'AAA', 'AAA']);
        $this->tiles[1] = new Tile(1, ['BBB', 'BBB', 'BBB', 'BBB']);
        $this->tiles[2] = new Tile(2, ['BBB', 'BCB', 'BBB', 'BBB']);
        $this->tiles[3] = new Tile(3, ['BBB', 'BDB', 'BBB', 'BDB']);
        $this->tiles[4] = new Tile(4, ['ABB', 'BCB', 'BBA', 'AAA']);
        $this->tiles[5] = new Tile(5, ['ABB', 'BBB', 'BBB', 'BBA']);
        $this->tiles[6] = new Tile(6, ['BBB', 'BCB', 'BBB', 'BCB']);
        $this->tiles[7] = new Tile(7, ['BDB', 'BCB', 'BDB', 'BCB']);
        $this->tiles[8] = new Tile(8, ['BDB', 'BBB', 'BCB', 'BBB']);
        $this->tiles[9] = new Tile(9, ['BCB', 'BCB', 'BBB', 'BCB']);
        $this->tiles[10] = new Tile(10, ['BCB', 'BCB', 'BCB', 'BCB']);
        $this->tiles[11] = new Tile(11, ['BCB', 'BCB', 'BBB', 'BBB']);
        $this->tiles[12] = new Tile(12, ['BBB', 'BCB', 'BBB', 'BCB']);

        $initialTileCount = count($this->tiles);
        for ($i = 0; $i < $initialTileCount; $i++) {
            $tempTiles = [];
            for ($j = 0; $j < 4; $j++) {
                $tempTiles[] = $this->tiles[$i]->rotate($j);
            }
            $tempTiles = $this->removeDuplicatedTiles($tempTiles);
            $this->tiles = array_merge($this->tiles, $tempTiles);
        }

        // Generate the adjacency rules based on edges
        foreach ($this->tiles as $tile) {
            $tile->analyze($this->tiles);
        }

        $this->startOver();
        
        $this->draw();
        $this->draw();
        $this->draw();
    }

    private function removeDuplicatedTiles(array $tiles): array
    {
        $uniqueTilesMap = [];
        foreach ($tiles as $tile) {
            $key = implode(',', $tile->edges); // ex: "ABB,BCB,BBA,AAA"
            $uniqueTilesMap[$key] = $tile;
        }
        return array_values($uniqueTilesMap);
    }

    private function startOver(): void
    {
        // Create cell for each spot on the grid
        for ($i = 0; $i < $this->width * $this->height; $i++) {
            $this->grid[$i] = new Cell(count($this->tiles));
        }
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

    public function draw(): void
    {
        // Pick cell with least entropy
        $gridCopy = array_filter($this->grid, fn($a) => !$a->isCollapsed());

        if (empty($gridCopy)) {
            return;
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
            $this->startOver();
            return;
        }
        $cell->setOptions([$pick]);

        $nextGrid = [];
        for ($j = 0; $j < $this->height; $j++) {
            for ($i = 0; $i < $this->width; $i++) {
                $index = $i + $j * $this->height;
                if ($this->grid[$index]->isCollapsed()) {
                    $nextGrid[$index] = $this->grid[$index];
                } else {
                    $options = range(0, count($this->tiles) - 1);
                    // Look up
                    if ($j > 0) {
                        $up = $this->grid[$i + ($j - 1) * $this->height];
                        $validOptions = [];
                        foreach ($up->getOptions() as $option) {
                            $valid = $this->tiles[$option]->down;
                            $validOptions = array_merge($validOptions, $valid);
                        }
                        $this->checkValid($options, $validOptions);
                    }
                    // Look right
                    if ($i < $this->width - 1) {
                        $right = $this->grid[$i + 1 + $j * $this->height];
                        $validOptions = [];
                        foreach ($right->getOptions() as $option) {
                            $valid = $this->tiles[$option]->left;
                            $validOptions = array_merge($validOptions, $valid);
                        }
                        $this->checkValid($options, $validOptions);
                    }
                    // Look down
                    if ($j < $this->height - 1) {
                        $down = $this->grid[$i + ($j + 1) * $this->height];
                        $validOptions = [];
                        foreach ($down->getOptions() as $option) {
                            $valid = $this->tiles[$option]->up;
                            $validOptions = array_merge($validOptions, $valid);
                        }
                        $this->checkValid($options, $validOptions);
                    }
                    // Look left
                    if ($i > 0) {
                        $left = $this->grid[$i - 1 + $j * $this->height];
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

        $this->grid = $nextGrid;
    }
}
