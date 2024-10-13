<?php

declare(strict_types=1);

namespace App\Command;

use Flow\Flow\Flow;
use Flow\Ip;
use Generator;
use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Pipeline\Pipeline;
use Kiboko\Component\Pipeline\PipelineRunner;
use Kiboko\Component\Pipeline\StepCode;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\ExtractorInterface;
use Kiboko\Contract\Pipeline\FlushableInterface;
use Kiboko\Contract\Pipeline\LoaderInterface;
use Kiboko\Contract\Pipeline\NullState;
use Kiboko\Contract\Pipeline\NullStepRejection;
use Kiboko\Contract\Pipeline\NullStepState;
use Kiboko\Contract\Pipeline\TransformerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:php-etl',
    description: 'Add a short description for your command',
)]
class PhpEtlCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $flow = Flow::do(static function () {
            yield static function ($data) {
                $pipeline = new Pipeline(new PipelineRunner(new NullLogger()), new NullState());
                $pipeline->extract(
                    StepCode::fromString('extractor'),
                    new class($data) implements ExtractorInterface {
                        /** @param array<string> $data */
                        public function __construct(private array $data) {}

                        public function extract(): iterable
                        {
                            foreach ($this->data as $item) {
                                yield new AcceptanceResultBucket([$item]);
                            }
                        }
                    },
                    new NullStepRejection(),
                    new NullStepState()
                );

                return $pipeline;
            };
            yield static function (Pipeline $pipeline) {
                $pipeline->transform(
                    StepCode::fromString('transformer'),
                    new class() implements TransformerInterface,
                        FlushableInterface {
                        /** @return Generator<null|ResultBucketInterface<mixed>> */
                        public function transform(): Generator
                        {
                            $line = yield;
                            $line = yield new AcceptanceResultBucket(array_map(static fn (string $item) => str_rot13($item), $line));
                            $line = yield new AcceptanceResultBucket(array_map(static fn (string $item) => str_rot13($item), $line));
                            yield new AcceptanceResultBucket(array_map(static fn (string $item) => str_rot13($item), $line));
                        }

                        public function flush(): ResultBucketInterface
                        {
                            return new AcceptanceResultBucket([str_rot13('sit amet')]);
                        }
                    },
                    new NullStepRejection(),
                    new NullStepState()
                );

                return $pipeline;
            };
            yield static function (Pipeline $pipeline) {
                $pipeline->load(
                    StepCode::fromString('loader'),
                    new class() implements LoaderInterface,
                        FlushableInterface {
                        /** @return Generator<null|ResultBucketInterface<mixed>> */
                        public function load(): Generator
                        {
                            $line = yield;
                            $line = yield new AcceptanceResultBucket(array_map(static fn (string $item) => str_rot13($item), $line));
                            $line = yield new AcceptanceResultBucket(array_map(static fn (string $item) => str_rot13($item), $line));
                            yield new AcceptanceResultBucket(array_map(static fn (string $item) => str_rot13($item), $line));
                        }

                        public function flush(): ResultBucketInterface
                        {
                            return new AcceptanceResultBucket([str_rot13('sit amet')]);
                        }
                    },
                    new NullStepRejection(),
                    new NullStepState()
                );

                return $pipeline;
            };
            yield static function (Pipeline $pipeline) {
                foreach ($pipeline->walk() as $items) {
                    printf("%s\n", implode(',', $items));
                }
            };
        });

        $flow(new Ip(['lorem', 'ipsum']));
        $flow(new Ip(['dolor']));

        $flow->await();

        return Command::SUCCESS;
    }
}
