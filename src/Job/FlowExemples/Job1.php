<?php

declare(strict_types=1);

namespace App\Job\Examples;

use App\Model\DataA;
use App\Model\DataB;
use Error;
use Flow\DriverInterface;
use Flow\JobInterface;

class Job1 implements JobInterface
{
    public function __construct(private DriverInterface $driver) {}

    public function __invoke($dataA): mixed
    {
        printf("*. #%d - Job 1 Calculating %d + %d\n", $dataA->id, $dataA->a, $dataA->b);

        // simulating calculating some "light" operation from 0.1 to 1 seconds
        $delay = random_int(1, 3);
        $this->driver->delay($delay);
        $d = $dataA->a + $dataA->b;

        // simulating 1 chance on 5 to produce an exception from the "light" operation
        if (1 === random_int(1, 5)) {
            // throw new Error(sprintf('#%d - Failure when processing Job1', $dataA->id));
        }

        printf("*. #%d - Job 1 Result for %d + %d = %d and took %.01f seconds\n", $dataA->id, $dataA->a, $dataA->b, $d, $delay);

        return new DataB($dataA->id, $d, $dataA->c);
    }
}
