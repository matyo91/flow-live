<?php

namespace Model\WaveFunctionCollapse;

class Tile
{
    public $img;
    public $edges;
    public $up = [];
    public $right = [];
    public $down = [];
    public $left = [];
    public $index;

    public function __construct($img, $edges, $i = null)
    {
        $this->img = $img;
        $this->edges = $edges;
        if ($i !== null) {
            $this->index = $i;
        }
    }

    public function analyze($tiles)
    {
        foreach ($tiles as $i => $tile) {
            // Tile 5 can't match itself
            if ($tile->index == 5 && $this->index == 5) continue;

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

    public function rotate($num)
    {
        $newImg = '';
        
        $newEdges = [];
        $len = count($this->edges);
        for ($i = 0; $i < $len; $i++) {
            $newEdges[$i] = $this->edges[($i - $num + $len) % $len];
        }
        return new Tile($newImg, $newEdges, $this->index);
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
