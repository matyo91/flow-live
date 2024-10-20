<?php

declare(strict_types=1);

namespace App\Twig\Components\WaveFunctionCollapse;

use App\EnumType\WaveFunctionCollapse\DataSetEnumType;
use App\Job\WaveFunctionCollapse\CollapseJob;
use App\Model\WaveFunctionCollapse\Board;
use Flow\Flow\Flow;
use Flow\Ip;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class Ui
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, onUpdated: 'reset')]
    public DataSetEnumType $dataSet = DataSetEnumType::CIRCUIT_CODING_TRAIN;

    #[LiveProp]
    public Board $board;

    #[LiveProp]
    public bool $pool = true;

    public function mount(int $width, int $height): void
    {
        $this->board = new Board($width, $height);

        $this->reset();
    }

    public function reset(): void
    {
        $this->board->reset($this->dataSet);
    }

    #[LiveAction]
    public function collapse(): void
    {
        $flow = Flow::do(function () {
            yield new CollapseJob();
            yield function ($nextBoard) {
                if ($nextBoard === null) {
                    $this->board->startOver();
                } else {
                    $this->board = $nextBoard;
                }
            };
        });

        $flow(new Ip($this->board));
        $flow->await();
    }

    #[LiveAction]
    public function togglePool(): void
    {
        $this->pool = !$this->pool;
    }
}
