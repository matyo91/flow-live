<?php

namespace App\Twig\Components\WaveFunctionCollapse;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class Board
{
    use DefaultActionTrait;

    private array $tiles = [];
    private array $tileImages = [];
    private array $grid = [];
    private const DIM = 25;

    public function mount(): void
    {

    }
}
