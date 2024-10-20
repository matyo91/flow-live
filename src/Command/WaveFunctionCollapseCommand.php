<?php

declare(strict_types=1);

namespace App\Command;

use App\EnumType\WaveFunctionCollapse\DataSetEnumType;
use App\Job\WaveFunctionCollapse\CollapseJob;
use App\Job\WaveFunctionCollapse\ImgJob;
use App\Job\WaveFunctionCollapse\Mp4Job;
use App\Model\WaveFunctionCollapse\Board;
use Flow\Flow\Flow;
use Flow\Flow\YFlow;
use Flow\Ip;
use Imagine\Gd\Imagine;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use ValueError;

use function sprintf;

#[AsCommand(
    name: 'app:wave-function-collapse',
    description: 'Add a short description for your command',
)]
class WaveFunctionCollapseCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/assets')]
        private string $assetsDir,
        #[Autowire('%kernel.cache_dir%/wave_function_collapse')]
        private string $cacheDir,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption('width', null, InputOption::VALUE_OPTIONAL, 'Width of the grid', 5)
            ->addOption('height', null, InputOption::VALUE_OPTIONAL, 'Height of the grid', 5)
            ->addOption('dataset', null, InputOption::VALUE_OPTIONAL, 'Dataset to use', DataSetEnumType::CIRCUIT_CODING_TRAIN->value)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $imagine = new Imagine();
        $width = (int) $input->getOption('width');
        $height = (int) $input->getOption('height');
        $dataSetValue = $input->getOption('dataset');

        try {
            $dataSet = DataSetEnumType::from($dataSetValue);
        } catch (ValueError $e) {
            $io->error('Invalid dataset specified. Available options are: ' . implode(', ', array_column(DataSetEnumType::cases(), 'value')));

            return Command::FAILURE;
        }

        $io->writeln(sprintf('Grid size: %dx%d', $width, $height));
        $io->writeln(sprintf('Dataset: %s', $dataSet->value));

        $board = new Board($width, $height);
        $board->reset($dataSet);

        $flow = Flow::do(function () use ($io, $imagine, $board, $dataSet) {
            yield new YFlow(function ($collapseLoop) use ($imagine, $board, $dataSet) {
                return function ($data) use ($collapseLoop, $imagine, $board, $dataSet) {
                    [$grid, $images] = $data;

                    $images[] = (new ImgJob(
                        $imagine,
                        $this->assetsDir,
                        $board->tiles,
                        $board->width,
                        $board->height,
                        $dataSet,
                        256
                    ))($grid);
                    $nextGrid = (new CollapseJob($board->tiles, $board->width, $board->height))($grid);

                    if ($nextGrid === null) {
                        return [$grid, $images];
                    }

                    return $collapseLoop([$nextGrid, $images]);
                };
            });
            yield function ($data) {
                [$grid, $images] = $data;

                return (new Mp4Job($this->cacheDir))($images);
            };
            yield static function ($path) use ($io) {
                $io->success(sprintf('Movie is generated: %s', $path));
            };
        });

        $flow(new Ip([$board->grid, []]));

        $flow->await();

        return Command::SUCCESS;
    }
}
