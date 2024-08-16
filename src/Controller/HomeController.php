<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\DataA;
use App\Model\DataB;
use App\Model\DataC;
use App\Model\DataD;
use App\Model\YFlowData;
use Closure;
use Error;
use Flow\AsyncHandler\DeferAsyncHandler;
use Flow\Driver\AmpDriver;
use Flow\Driver\FiberDriver;
use Flow\Driver\ReactDriver;
use Flow\Driver\SpatieDriver;
use Flow\Driver\SwooleDriver;
use Flow\ExceptionInterface;
use Flow\Flow\Flow;
use Flow\Flow\YFlow;
use Flow\Ip;
use Flow\IpStrategy\MaxIpStrategy;
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

        $job1 = static function (DataA $dataA) use ($driver): DataB {
            printf("*. #%d - Job 1 Calculating %d + %d\n", $dataA->id, $dataA->a, $dataA->b);

            // simulating calculating some "light" operation from 0.1 to 1 seconds
            $delay = random_int(1, 3);
            $driver->delay($delay);
            $d = $dataA->a + $dataA->b;

            // simulating 1 chance on 5 to produce an exception from the "light" operation
            if (1 === random_int(1, 5)) {
                // throw new Error(sprintf('#%d - Failure when processing Job1', $dataA->id));
            }

            printf("*. #%d - Job 1 Result for %d + %d = %d and took %.01f seconds\n", $dataA->id, $dataA->a, $dataA->b, $d, $delay);

            return new DataB($dataA->id, $d, $dataA->c);
        };

        $job2 = static function (DataB $dataB) use ($driver): DataC {
            printf(".* #%d - Job 2 Calculating %d * %d\n", $dataB->id, $dataB->d, $dataB->e);

            // simulating calculating some "heavy" operation from from 1 to 3 seconds
            $delay = random_int(1, 3);
            $driver->delay($delay);
            $f = $dataB->d * $dataB->e;

            // simulating 1 chance on 5 to produce an exception from the "heavy" operation
            if (1 === random_int(1, 5)) {
                // throw new Error(sprintf('#%d - Failure when processing Job2', $dataB->id));
            }

            printf(".* #%d - Job 2 Result for %d * %d = %d and took %.01f seconds\n", $dataB->id, $dataB->d, $dataB->e, $f, $delay);

            return new DataC($dataB->id, $f);
        };

        $job3 = static function (DataC $dataC): DataD {
            printf("** #%d - Job 3 Result is %d\n", $dataC->id, $dataC->f);

            return new DataD($dataC->id);
        };

        /**
         * @param Ip<Data> $ip
         */
        $errorJob1 = static function (ExceptionInterface $exception): void {
            printf("*. %s\n", $exception->getMessage());
        };

        /**
         * @param Ip<Data> $ip
         */
        $errorJob2 = static function (ExceptionInterface $exception): void {
            printf(".* %s\n", $exception->getMessage());
        };

        $jobDataToYFlow = static function ($data): YFlowData {
            return new YFlowData($data->id, $data->id, $data->id);
        };

        function factorial(int $n): int
        {
            return ($n <= 1) ? 1 : $n * factorial($n - 1);
        }

        function Ywrap(callable $func, callable $wrapperFunc): Closure
        {
            $U = static fn ($f) => $f($f);
            $Y = static fn (callable $f, callable $g) => $U(static fn (Closure $x) => $f($g(static fn ($y) => $U($x)($y))));

            return $Y($func, $wrapperFunc);
        }

        function memoWrapperGenerator(callable $f): Closure
        {
            static $cache = [];

            return static function ($y) use ($f, &$cache) {
                if (!isset($cache[$y])) {
                    $cache[$y] = $f($y);
                }

                return $cache[$y];
            };
        }

        function Ymemo(callable $f): Closure
        {
            return Ywrap($f, 'memoWrapperGenerator');
        }

        function factorialGen(callable $func): Closure
        {
            return static function (int $n) use ($func): int {
                return ($n <= 1) ? 1 : $n * $func($n - 1);
            };
        }

        function factorialYMemo(int $n): int
        {
            return Ymemo('factorialGen')($n);
        }

        $factorialJob = static function (YFlowData $data): YFlowData {
            printf("*... #%d - Job 1 : Calculating factorial(%d)\n", $data->id, $data->number);

            // raw factorial calculation
            $result = factorial($data->number);

            printf("*... #%d - Job 1 : Result for factorial(%d) = %d\n", $data->id, $data->number, $result);

            return new YFlowData($data->id, $data->number);
        };

        $factorialYJobBefore = static function (YFlowData $data): YFlowData {
            printf(".*.. #%d - Job 2 : Calculating factorialYJob(%d)\n", $data->id, $data->number);

            return new YFlowData($data->id, $data->number, $data->number);
        };

        $factorialYJob = static function ($factorial) {
            return static function (YFlowData $data) use ($factorial): YFlowData {
                return new YFlowData(
                    $data->id,
                    $data->number,
                    ($data->result <= 1) ? 1 : $data->result * $factorial(new YFlowData($data->id, $data->number, $data->result - 1))->result
                );
            };
        };

        $factorialYJobAfter = static function (YFlowData $data): YFlowData {
            printf(".*.. #%d - Job 2 : Result for factorialYJob(%d) = %d\n", $data->id, $data->number, $data->result);

            return new YFlowData($data->id, $data->number);
        };

        $factorialYMemoJob = static function (YFlowData $data): YFlowData {
            printf("..*. #%d - Job 3 : Calculating factorialYMemo(%d)\n", $data->id, $data->number);

            $result = factorialYMemo($data->number);

            printf("..*. #%d - Job 3 : Result for factorialYMemo(%d) = %d\n", $data->id, $data->number, $result);

            return new YFlowData($data->id, $data->number);
        };

        // Define the Y-Combinator
        $U = static fn (Closure $f) => $f($f);
        $Y = static fn (Closure $f) => $U(static fn (Closure $x) => $f(static fn ($y) => $U($x)($y)));

        $factorialYJobDeferBefore = static function (YFlowData $data) {
            printf("...* #%d - Job 4 : Calculating factorialYJobDefer(%d)\n", $data->id, $data->number);

            return new YFlowData($data->id, $data->number, $data->number);
        };

        $factorialYJobDefer = $Y(static function ($factorial) {
            return static function ($args) use ($factorial) {
                [$data, $defer] = $args;

                return $defer(static function ($complete, $async) use ($data, $defer, $factorial) {
                    if ($data->result <= 1) {
                        $complete([new YFlowData($data->id, $data->number, 1), $defer]);
                    } else {
                        $async($factorial([new YFlowData($data->id, $data->number, $data->result - 1), $defer]), static function ($result) use ($data, $complete) {
                            [$resultData, $defer] = $result;
                            $complete([new YFlowData($data->id, $data->number, $data->result * $resultData->result), $defer]);
                        });
                    }
                });
            };
        });

        $factorialYJobDeferAfter = static function ($args) {
            [$data, $defer] = $args;

            return $defer(static function ($complete) use ($data, $defer) {
                printf("...* #%d - Job 4 : Result for factorialYJobDefer(%d) = %d\n", $data->id, $data->number, $data->result);

                $complete([new YFlowData($data->id, $data->number), $defer]);
            });
        };

        echo "begin - synchronous\n";
        $asyncTask = static function (
            $job1,
            $job2,
            $job3,
            $errorJob1,
            $errorJob2,
            $driver
        ) use (
            $jobDataToYFlow,
            $factorialJob,
            $factorialYJobBefore,
            $factorialYJob,
            $factorialYJobAfter,
            $factorialYMemoJob,
            $factorialYJobDeferBefore,
            $factorialYJobDefer,
            $factorialYJobDeferAfter
        ) {
            echo "begin - flow asynchronous\n";

            $flow = Flow::do(static function () use (
                $job1,
                $job2,
                $job3,
                $jobDataToYFlow,
                $errorJob1,
                $errorJob2,
                $factorialJob,
                $factorialYJobBefore,
                $factorialYJob,
                $factorialYJobAfter,
                $factorialYMemoJob,
                $factorialYJobDeferBefore,
                $factorialYJobDefer,
                $factorialYJobDeferAfter
            ) {
                yield [$job1, $errorJob1, new MaxIpStrategy(2)];
                yield [$job2, $errorJob2, new MaxIpStrategy(2)];
                yield $job3;
                yield $jobDataToYFlow;
                yield [$factorialJob];
                yield [$factorialYJobBefore];
                yield new YFlow($factorialYJob);
                yield [$factorialYJobAfter];
                yield [$factorialYMemoJob];
                yield [$factorialYJobDeferBefore];
                yield [$factorialYJobDefer, null, null, null, new DeferAsyncHandler()];
                yield [$factorialYJobDeferAfter, null, null, null, new DeferAsyncHandler()];
            }, ['driver' => $driver]);

            for ($id = 1; $id <= 5; $id++) {
                $ip = new Ip(new DataA($id, random_int(1, 10), random_int(1, 10), random_int(1, 5)));
                $flow($ip);
            }
            $flow->await();

            echo "ended - flow asynchronous\n";
        };
        $asyncTask($job1, $job2, $job3, $errorJob1, $errorJob2, $driver);
        echo "ended - synchronous\n";

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
}
