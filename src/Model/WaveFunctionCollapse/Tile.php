<?php

declare(strict_types=1);

namespace App\Model\WaveFunctionCollapse;

use function count;

class Tile
{
    public function __construct(
        public int $index = 0,
        /** @var array<string> */
        public array $edges = [],
        public int $direction = 0,
        /** @var array<int> */
        public array $up = [],
        /** @var array<int> */
        public array $right = [],
        /** @var array<int> */
        public array $down = [],
        /** @var array<int> */
        public array $left = []
    ) {}

    /**
     * @param Tile[] $tiles
     */
    public function analyze(array $tiles): void
    {
        foreach ($tiles as $i => $tile) {
            // Tile can't match itself
            if ($tile->index === $this->index) {
                continue;
            }

            // UP
            if ($this->compareEdge($tile->edges[2], $this->edges[0])) {
                $this->up[] = $i;
            }
            // RIGHT
            if ($this->compareEdge($tile->edges[3], $this->edges[1])) {
                $this->right[] = $i;
            }
            // DOWN
            if ($this->compareEdge($tile->edges[0], $this->edges[2])) {
                $this->down[] = $i;
            }
            // LEFT
            if ($this->compareEdge($tile->edges[1], $this->edges[3])) {
                $this->left[] = $i;
            }
        }
    }

    public function rotate(int $direction): self
    {
        $newEdges = [];
        $len = count($this->edges);
        for ($i = 0; $i < $len; $i++) {
            $newEdges[$i] = $this->edges[($i - $direction + $len) % $len];
        }

        return new self($this->index, $newEdges, $direction);
    }

    private function reverseString(string $s): string
    {
        return strrev($s);
    }

    private function compareEdge(string $a, string $b): bool
    {
        return $a === $this->reverseString($b);
    }
}
