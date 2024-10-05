<?php

declare(strict_types=1);

namespace App\Enum\WaveFunctionCollapse;

enum DataSetEnum: string
{
    case CIRCUIT = 'circuit';
    case CIRCUIT_CODING_TRAIN = 'circuit-coding-train';
    case DEMO = 'demo';
    case FLOOR = 'floor';
    case MOUNTAINS = 'mountains';
    case PIPES = 'pipes';
    case POLKA = 'polka';
    case ROADS = 'roads';
    case SPACE = 'space';
    case TRAIN_TRACKS = 'train-tracks';
}
