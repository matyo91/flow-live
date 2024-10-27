<?php

declare(strict_types=1);

namespace App\Flow\WaveFunctionCollapse;

use App\EnumType\WaveFunctionCollapse\DataSetEnumType;
use App\Job\WaveFunctionCollapse\CollapseJob;
use App\Job\WaveFunctionCollapse\ImgJob;
use App\Job\WaveFunctionCollapse\Mp4Job;
use App\Model\WaveFunctionCollapse\Board;
use Flow\Flow\YFlow;
use Flow\FlowFactory as FlowFlowFactory;
use Flow\FlowInterface;
use Imagine\Gd\Imagine;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class FlowFactory
{
    public function __construct(
        private FlowFlowFactory $flowFactory,
        #[Autowire('%kernel.project_dir%/assets')]
        private string $assetsDir,
        #[Autowire('%kernel.cache_dir%/wave_function_collapse')]
        private string $cacheDir,
    ) {}

    /**
     * @return FlowInterface<mixed>
     */
    public function doMp4(DataSetEnumType $dataSet): FlowInterface
    {
        $imagine = new Imagine();

        return $this->flowFactory->create(function () use ($imagine, $dataSet) {
            yield static function ($data) {
                [$width, $height, $dataSet] = $data;

                $board = new Board($width, $height);
                $board->reset($dataSet);

                return [$board, []];
            };
            yield new YFlow(function ($collapseLoop) use ($imagine, $dataSet) {
                return function ($data) use ($collapseLoop, $imagine, $dataSet) {
                    [$board, $images] = $data;

                    $images[] = (new ImgJob(
                        $imagine,
                        $this->assetsDir,
                        $dataSet,
                        256
                    ))($board);
                    $nextBoard = (new CollapseJob())($board);

                    if ($nextBoard === null) {
                        return [$board, $images];
                    }

                    return $collapseLoop([$nextBoard, $images]);
                };
            });
            yield function ($data) {
                [$board, $images] = $data;

                return (new Mp4Job($this->cacheDir))($images);
            };
        });
    }
}
