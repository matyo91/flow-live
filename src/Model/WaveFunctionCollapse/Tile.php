<?php

namespace App\Model\WaveFunctionCollapse;

class Tile
{
    public $index;
    public $edges;
    public $up = [];
    public $right = [];
    public $down = [];
    public $left = [];
    public $direction = 0;

    public function __construct($index, $edges, $direction = 0, $up = [], $right = [], $down = [], $left = [])
    {
        $this->index = $index;
        $this->edges = $edges;
        $this->direction = $direction;
        $this->up = $up;
        $this->right = $right;
        $this->down = $down;
        $this->left = $left;
    }

    public function analyze($tiles)
    {
        foreach ($tiles as $i => $tile) {
            // Tile can't match itself
            if ($tile->index == $this->index) continue;

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

    public function rotate($direction)
    {
        $newEdges = [];
        $len = count($this->edges);
        for ($i = 0; $i < $len; $i++) {
            $newEdges[$i] = $this->edges[($i - $direction + $len) % $len];
        }
        return new Tile($this->index, $newEdges, $direction);
    }

    private function reverseString($s)
    {
        return strrev($s);
    }

    private function compareEdge($a, $b)
    {
        return $a == $this->reverseString($b);
    }
}
