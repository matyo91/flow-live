<?php

declare(strict_types=1);

namespace App\Command;

use App\Job\FlowExamples\DataToYFlowJob;
use App\Job\FlowExamples\ErrorJob1;
use App\Job\FlowExamples\ErrorJob2;
use App\Job\FlowExamples\FactorialJob;
use App\Job\FlowExamples\FactorialYJob;
use App\Job\FlowExamples\FactorialYJobAfter;
use App\Job\FlowExamples\FactorialYJobBefore;
use App\Job\FlowExamples\FactorialYJobDefer;
use App\Job\FlowExamples\FactorialYJobDeferAfter;
use App\Job\FlowExamples\FactorialYJobDeferBefore;
use App\Job\FlowExamples\FactorialYMemoJob;
use App\Job\FlowExamples\Job1;
use App\Job\FlowExamples\Job2;
use App\Job\FlowExamples\Job3;
use App\Model\FlowExemples\DataA;
use Flow\AsyncHandler\DeferAsyncHandler;
use Flow\Driver\AmpDriver;
use Flow\Driver\FiberDriver;
use Flow\Driver\ReactDriver;
use Flow\Driver\SpatieDriver;
use Flow\Driver\SwooleDriver;
use Flow\Flow\Flow;
use Flow\Flow\YFlow;
use Flow\Ip;
use Flow\IpStrategy\MaxIpStrategy;
use Flow\Job\YJob;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:flow-exemples',
    description: 'Run exemples from flow',
)]
class FlowExemplesCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $driver = match (random_int(1, 4)) {
            1 => new AmpDriver(),
            2 => new FiberDriver(),
            3 => new ReactDriver(),
            4 => new SwooleDriver(),
            // 5 => new SpatieDriver(),
        };
        printf("Use %s\n", $driver::class);
        printf("Calculating:\n");
        printf("- DataA(a, b, c): Job1((DataA->a + DataA->b))\n");
        printf("- DataB(d, e): Job2(DataB->d * DataB->e)\n");
        printf("- DataC(f)\n");

        echo "begin - synchronous\n";
        $asyncTask = static function () use ($driver) {
            echo "begin - flow asynchronous\n";

            $flow = Flow::do(static function () use ($driver) {
                yield [new Job1($driver), new ErrorJob1(), new MaxIpStrategy(2)];
                yield [new Job2($driver), new ErrorJob2(), new MaxIpStrategy(2)];
                yield new Job3();
                yield new DataToYFlowJob();
                yield [new FactorialJob()];
                yield [new FactorialYJobBefore()];
                yield new YFlow(new FactorialYJob());
                yield [new FactorialYJobAfter()];
                yield [new FactorialYMemoJob()];
                yield [new FactorialYJobDeferBefore()];
                yield [new YJob(new FactorialYJobDefer()), null, null, null, new DeferAsyncHandler()];
                yield [new FactorialYJobDeferAfter(), null, null, null, new DeferAsyncHandler()];
            }, ['driver' => $driver]);

            for ($id = 1; $id <= 5; $id++) {
                $ip = new Ip(new DataA($id, random_int(1, 10), random_int(1, 10), random_int(1, 5)));
                $flow($ip);
            }
            $flow->await();

            echo "ended - flow asynchronous\n";
        };
        $asyncTask();
        echo "ended - synchronous\n";

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
