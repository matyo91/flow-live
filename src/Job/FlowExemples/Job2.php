<?php

declare(strict_types=1);

namespace App\Job\Examples;

use App\Model\DataB;
use App\Model\DataC;
use Error;
use Flow\DriverInterface;
use Flow\JobInterface;

class Job2 implements JobInterface
{
    public function __construct(private DriverInterface $driver) {}

    public function __invoke($dataB): mixed
    {
        printf(".* #%d - Job 2 Calculating %d * %d\n", $dataB->id, $dataB->d, $dataB->e);

        // simulating calculating some "heavy" operation from from 1 to 3 seconds
        $delay = random_int(1, 3);
        $this->driver->delay($delay);
        $f = $dataB->d * $dataB->e;

        // simulating 1 chance on 5 to produce an exception from the "heavy" operation
        if (1 === random_int(1, 5)) {
            // throw new Error(sprintf('#%d - Failure when processing Job2', $dataB->id));
        }

        printf(".* #%d - Job 2 Result for %d * %d = %d and took %.01f seconds\n", $dataB->id, $dataB->d, $dataB->e, $f, $delay);

        return new DataC($dataB->id, $f);
    }
}
