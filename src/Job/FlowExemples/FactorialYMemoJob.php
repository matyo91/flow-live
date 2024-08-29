<?php

declare(strict_types=1);

namespace App\Job\FlowExamples;

use App\Model\YFlowData;
use Closure;
use Flow\JobInterface;

class FactorialYMemoJob implements JobInterface
{
    public function __invoke($data): mixed
    {
        printf("..*. #%d - Job 3 : Calculating factorialYMemo(%d)\n", $data->id, $data->number);

        $result = $this->factorialYMemo($data->number);

        printf("..*. #%d - Job 3 : Result for factorialYMemo(%d) = %d\n", $data->id, $data->number, $result);

        return new YFlowData($data->id, $data->number);
    }

    private function Ywrap(callable $func, callable $wrapperFunc): Closure
    {
        $U = static fn ($f) => $f($f);
        $Y = static fn (callable $f, callable $g) => $U(static fn (Closure $x) => $f($g(static fn ($y) => $U($x)($y))));

        return $Y($func, $wrapperFunc);
    }

    private function memoWrapperGenerator(callable $f): Closure
    {
        static $cache = [];

        return static function ($y) use ($f, &$cache) {
            if (!isset($cache[$y])) {
                $cache[$y] = $f($y);
            }

            return $cache[$y];
        };
    }

    private function Ymemo(callable $f): Closure
    {
        return Ywrap($f, 'memoWrapperGenerator');
    }

    private function factorialGen(callable $func): Closure
    {
        return static function (int $n) use ($func): int {
            return ($n <= 1) ? 1 : $n * $func($n - 1);
        };
    }

    private function factorialYMemo(int $n): int
    {
        return $this->Ymemo('factorialGen')($n);
    }
}
