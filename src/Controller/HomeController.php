<?php

declare(strict_types=1);

namespace App\Controller;

use App\Job\Examples\DataToYFlowJob;
use App\Job\Examples\ErrorJob1;
use App\Job\Examples\ErrorJob2;
use App\Job\Examples\FactorialJob;
use App\Job\Examples\FactorialYJob;
use App\Job\Examples\FactorialYJobAfter;
use App\Job\Examples\FactorialYJobBefore;
use App\Job\Examples\FactorialYJobDefer;
use App\Job\Examples\FactorialYJobDeferAfter;
use App\Job\Examples\FactorialYJobDeferBefore;
use App\Job\Examples\FactorialYMemoJob;
use App\Job\Examples\Job1;
use App\Job\Examples\Job2;
use App\Job\Examples\Job3;
use App\Model\DataA;
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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
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

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
}
