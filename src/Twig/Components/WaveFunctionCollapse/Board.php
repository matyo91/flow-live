<?php

namespace App\Twig\Components\WaveFunctionCollapse;

use App\Job\WaveFunctionCollapse\CollapseJob;
use App\Model\WaveFunctionCollapse\Cell;
use App\Model\WaveFunctionCollapse\Tile;
use Flow\Driver\FiberDriver;
use Flow\Flow\Flow;
use Flow\Ip;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent]
final class Board
{
    use DefaultActionTrait;

    #[LiveProp(hydrateWith: 'hydrateTiles', dehydrateWith: 'dehydrateTiles', writable: true)]
    public array $tiles = [];
    #[LiveProp(hydrateWith: 'hydrateGrid', dehydrateWith: 'dehydrateGrid', writable: true)]
    public array $grid = [];
    
    #[LiveProp]
    public int $width = 0;
    #[LiveProp]
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
    }

    #[LiveAction]
    public function collapse() {
        $flow = Flow::do(function () {
            yield new CollapseJob($this->tiles, $this->width, $this->height);
            yield function($nextGrid) {
                if($nextGrid === null) {
                    $this->startOver();
                } else {
                    $this->grid = $nextGrid;
                }
                return $this->grid;
            };
        });

        $flow(new Ip($this->grid));
        $flow->await();
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

    public function dehydrateTiles(array $tiles)
    {
        return array_map(function (Tile $tile) {
            return [
                'index' => $tile->index,
                'edges' => $tile->edges,
                'direction' => $tile->direction,
                'up' => $tile->up,
                'right' => $tile->right,
                'down' => $tile->down,
                'left' => $tile->left,
            ];
        }, $tiles);
    }

    public function hydrateTiles($data): array
    {
        return array_map(function ($tileData) {
            return new Tile(
                $tileData['index'],
                $tileData['edges'],
                $tileData['direction'],
                $tileData['up'],
                $tileData['right'],
                $tileData['down'],
                $tileData['left'] 
            );
        }, $data);
    }

    public function dehydrateGrid(array $grid): array
    {
        return array_map(function (Cell $cell) {
            return [
                'options' => $cell->options,
                'collapsed' => $cell->collapsed,
            ];
        }, $grid);
    }

    public function hydrateGrid(array $data): array
    {
        return array_map(function ($cellData) {
            $cell = new Cell(count($this->tiles));
            $cell->options = $cellData['options'];
            $cell->collapsed = $cellData['collapsed'];
            return $cell;
        }, $data);
    }
}
