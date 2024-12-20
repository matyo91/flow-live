<?php

declare(strict_types=1);

namespace App\Model\WaveFunctionCollapse;

use App\EnumType\WaveFunctionCollapse\DataSetEnumType;

use function count;

final class Board
{
    /** @var Tile[] */
    public array $tiles = [];
    /** @var Cell[] */
    public array $grid = [];

    public int $width = 0;
    public int $height = 0;

    public function __construct(int $width = 1, int $height = 1)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function reset(DataSetEnumType $dataSet): void
    {
        $initialTiles = $this->loadDataset($dataSet);
        $initialTileCount = count($initialTiles);
        $this->tiles = [];
        for ($i = 0; $i < $initialTileCount; $i++) {
            $tempTiles = [];
            for ($j = 0; $j < 4; $j++) {
                $tempTiles[] = $initialTiles[$i]->rotate($j);
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

    public function startOver(): void
    {
        // Create cell for each spot on the grid
        for ($i = 0; $i < $this->width * $this->height; $i++) {
            $this->grid[$i] = new Cell(count($this->tiles));
        }
    }

    /**
     * @param array<Tile> $tiles
     *
     * @return array<Tile>
     */
    private function removeDuplicatedTiles(array $tiles): array
    {
        $uniqueTilesMap = [];
        foreach ($tiles as $tile) {
            $key = implode(',', $tile->edges); // ex: "ABB,BCB,BBA,AAA"
            $uniqueTilesMap[$key] = $tile;
        }

        return array_values($uniqueTilesMap);
    }

    /**
     * @return Tile[]
     */
    private static function loadDataset(DataSetEnumType $dataSet): array
    {
        return match ($dataSet) {
            DataSetEnumType::CIRCUIT, DataSetEnumType::CIRCUIT_CODING_TRAIN => [
                new Tile(0, ['AAA', 'AAA', 'AAA', 'AAA']),
                new Tile(1, ['BBB', 'BBB', 'BBB', 'BBB']),
                new Tile(2, ['BBB', 'BCB', 'BBB', 'BBB']),
                new Tile(3, ['BBB', 'BDB', 'BBB', 'BDB']),
                new Tile(4, ['ABB', 'BCB', 'BBA', 'AAA']),
                new Tile(5, ['ABB', 'BBB', 'BBB', 'BBA']),
                new Tile(6, ['BBB', 'BCB', 'BBB', 'BCB']),
                new Tile(7, ['BDB', 'BCB', 'BDB', 'BCB']),
                new Tile(8, ['BDB', 'BBB', 'BCB', 'BBB']),
                new Tile(9, ['BCB', 'BCB', 'BBB', 'BCB']),
                new Tile(10, ['BCB', 'BCB', 'BCB', 'BCB']),
                new Tile(11, ['BCB', 'BCB', 'BBB', 'BBB']),
                new Tile(12, ['BBB', 'BCB', 'BBB', 'BCB']),
            ],
            DataSetEnumType::DEMO, DataSetEnumType::MOUNTAINS, DataSetEnumType::PIPES, DataSetEnumType::POLKA, DataSetEnumType::ROADS, DataSetEnumType::TRAIN_TRACKS => [
                new Tile(0, ['0', '0', '0', '0']),
                new Tile(1, ['0', '1', '1', '1']),
                new Tile(2, ['1', '0', '1', '1']),
                new Tile(3, ['1', '1', '1', '0']),
                new Tile(4, ['1', '1', '0', '1']),
            ],
            DataSetEnumType::FLOOR => [
                new Tile(0, ['YYY', 'YLY', 'YYY', 'YLY']),
                new Tile(1, ['YYY', 'YLY', 'YLY', 'YLY']),
                new Tile(2, ['YLY', 'YLY', 'YYY', 'YYY']),
                new Tile(3, ['YYY', 'YLY', 'YYY', 'YLY']),
                new Tile(4, ['WWW', 'WWW', 'WWW', 'WWW']),
                new Tile(5, ['YYY', 'YYY', 'YYY', 'YYY']),
                new Tile(6, ['YYY', 'YDW', 'WWW', 'YDW']),
                new Tile(7, ['YYY', 'YDW', 'WWW', 'YBW']),
                new Tile(8, ['YYY', 'YYY', 'WBY', 'YBW']),
                new Tile(9, ['WBY', 'YBW', 'WWW', 'WWW']),
                new Tile(10, ['YYY', 'YZS', 'SSS', 'YZS']),
                new Tile(11, ['YYY', 'YYY', 'YYY', 'YYY']),
                new Tile(12, ['YYY', 'YYY', 'YYY', 'YYY']),
                new Tile(13, ['YYY', 'YBW', 'WWW', 'YBW']),
                new Tile(14, ['YYY', 'YBW', 'WWW', 'YBW']),
                new Tile(15, ['YLY', 'YBW', 'WWW', 'YBW']),
                new Tile(16, ['YYY', 'YBW', 'WWW', 'YBW']),
            ],
            DataSetEnumType::SPACE => [
                // floor
                new Tile(0, ['GGG', 'GGG', 'GGG', 'GGG']),
                new Tile(1, ['GGG', 'GGG', 'GGG', 'GGG']),
                new Tile(2, ['GGG', 'GGG', 'GGG', 'GGG']),
                new Tile(3, ['GGG', 'GGG', 'GGG', 'GGG']),
                new Tile(4, ['GGG', 'GGG', 'GGG', 'GGG']),
                new Tile(5, ['GGG', 'GGG', 'GGG', 'GGG']),
                new Tile(6, ['GGG', 'GGG', 'GGG', 'GGG']),

                // outer space
                new Tile(7, ['OOO', 'OOO', 'OOO', 'OOO']),
                new Tile(8, ['OOO', 'OOO', 'OOO', 'OOO']),
                new Tile(9, ['OOO', 'OOO', 'OOO', 'OOO']),
                new Tile(10, ['OOO', 'OOO', 'OOO', 'OOO']),

                // walls
                new Tile(11, ['OOO', 'OHG', 'GGG', 'OHG']),
                new Tile(12, ['GGG', 'GHO', 'OOO', 'GHO']),
                new Tile(13, ['GVO', 'OOO', 'GVO', 'GGG']),
                new Tile(14, ['OVG', 'GGG', 'OVG', 'OOO']),

                // bonus walls
                new Tile(15, ['GGG', 'GHO', 'OOO', 'GHO']),
                new Tile(16, ['OOO', 'OHG', 'GGG', 'OHG']),
                new Tile(17, ['OVG', 'GGG', 'OVG', 'OOO']),
                new Tile(18, ['GVO', 'OOO', 'GVO', 'GGG']),

                // corners  outer
                new Tile(19, ['OOO', 'OHG', 'OVG', 'OOO']),
                new Tile(20, ['OOO', 'OOO', 'GVO', 'OHG']),
                new Tile(21, ['GVO', 'OOO', 'OOO', 'GHO']),
                new Tile(22, ['OVG', 'GHO', 'OOO', 'OOO']),

                // corners  inner
                new Tile(23, ['GGG', 'GHO', 'GVO', 'GGG']),
                new Tile(24, ['GGG', 'GGG', 'OVG', 'GHO']),
                new Tile(25, ['OVG', 'GGG', 'GGG', 'OHG']),
                new Tile(26, ['GVO', 'OHG', 'GGG', 'GGG']),

                // pipes  straight
                new Tile(27, ['GGG', 'GLG', 'GGG', 'GLG']),
                new Tile(28, ['GGG', 'GLG', 'GGG', 'GLG']),
                new Tile(29, ['GJG', 'GGG', 'GJG', 'GGG']),

                // pipes  corners
                new Tile(30, ['GJG', 'GGG', 'GGG', 'GLG']),
                new Tile(31, ['GJG', 'GLG', 'GGG', 'GGG']),
                new Tile(32, ['GGG', 'GLG', 'GJG', 'GGG']),
                new Tile(33, ['GGG', 'GGG', 'GJG', 'GLG']),

                // pipes  vents
                new Tile(34, ['GJG', 'GGG', 'GGG', 'GGG']),
                new Tile(35, ['GGG', 'GGG', 'GJG', 'GGG']),
                new Tile(36, ['GGG', 'GLG', 'GGG', 'GGG']),
                new Tile(37, ['GGG', 'GGG', 'GGG', 'GLG']),

                // pipes  walls
                new Tile(38, ['GJG', 'GHO', 'OOO', 'GHO']),
                new Tile(39, ['OOO', 'OHG', 'GJG', 'OHG']),
                new Tile(40, ['OVG', 'GLG', 'OVG', 'OOO']),
                new Tile(41, ['GVO', 'OOO', 'GVO', 'GLG']),

                // wires
                new Tile(42, ['GKG', 'GGG', 'GKG', 'GGG']),
                new Tile(43, ['GKG', 'GGG', 'GKG', 'GGG']),
                new Tile(44, ['GKG', 'GKG', 'GGG', 'GGG']),
                new Tile(45, ['GKG', 'GKG', 'GGG', 'GGG']),

                // wires x pipes
                new Tile(46, ['GKG', 'GLG', 'GKG', 'GLG']),
                new Tile(47, ['GKG', 'GLG', 'GKG', 'GLG']),
                new Tile(48, ['GJG', 'GKG', 'GJG', 'GKG']),

                // wired servers
                new Tile(49, ['GGG', 'GKG', 'GGG', 'GGG']),
                new Tile(50, ['GKG', 'GGG', 'GGG', 'GGG']),
                new Tile(51, ['GGG', 'GGG', 'GKG', 'GGG']),
                new Tile(52, ['GGG', 'GGG', 'GGG', 'GKG']),

                // wires x walls
                new Tile(53, ['GKG', 'GHO', 'OOO', 'GHO']),
                new Tile(54, ['OOO', 'OHG', 'GKG', 'OHG']),
                new Tile(55, ['OVG', 'GKG', 'OVG', 'OOO']),
                new Tile(56, ['GVO', 'OOO', 'GVO', 'GKG']),

                // fuelpipes  straight
                new Tile(57, ['OOO', 'ONO', 'OOO', 'ONO']),
                new Tile(58, ['OMO', 'OOO', 'OMO', 'OOO']),

                // fuelpipes  corners
                new Tile(59, ['OMO', 'OOO', 'OOO', 'ONO']),
                new Tile(60, ['OMO', 'ONO', 'OOO', 'OOO']),
                new Tile(61, ['OOO', 'ONO', 'OMO', 'OOO']),
                new Tile(62, ['OOO', 'OOO', 'OMO', 'ONO']),

                // fuelpipes  walls
                new Tile(63, ['OMO', 'OHG', 'GGG', 'OHG']),
                new Tile(64, ['GGG', 'GHO', 'OMO', 'GHO']),
                new Tile(65, ['GVO', 'ONO', 'GVO', 'GGG']),
                new Tile(66, ['OVG', 'GGG', 'OVG', 'ONO']),

                // train  track
                new Tile(67, ['TTO', 'OOO', 'TTO', 'TTT']),
                new Tile(68, ['TTO', 'OOO', 'TTO', 'TTT']),
                new Tile(69, ['OTT', 'TTT', 'OTT', 'OOO']),
            ],
        };
    }
}
