<?php

declare(strict_types=1);

namespace App\Command;

use App\EnumType\WaveFunctionCollapse\DataSetEnumType;
use App\Flow\WaveFunctionCollapse\FlowFactory;
use Flow\Ip;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use ValueError;

use function sprintf;

#[AsCommand(
    name: 'app:wave-function-collapse',
    description: 'Add a short description for your command',
)]
class WaveFunctionCollapseCommand extends Command
{
    public function __construct(
        private FlowFactory $flowFactory,
        ?string $name = null,
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

        $flow = $this->flowFactory->doMp4($dataSet)
            ->fn(static function ($path) use ($io) {
                $io->success(sprintf('Movie is generated: %s', $path));
            })
        ;

        $flow(new Ip([$width, $height, $dataSet]));

        $flow->await();

        return Command::SUCCESS;
    }
}
