<?php

declare(strict_types=1);

namespace App\Scheduler\Task;

use App\EnumType\WaveFunctionCollapse\DataSetEnumType;
use App\Flow\WaveFunctionCollapse\FlowFactory;
use Flow\Ip;
use Symfony\Component\Notifier\Bridge\Twitter\TwitterOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCronTask('* * * * *')]
final class FlowTask
{
    public function __construct(
        private FlowFactory $flowFactory,
        private ChatterInterface $chatter,
    ) {}

    public function __invoke(): void
    {
        $dataSet = DataSetEnumType::cases()[array_rand(DataSetEnumType::cases())];

        $flow = $this->flowFactory->doMp4($dataSet)
            ->fn(function ($path) {
                $message = (new ChatMessage('Daily Flow generation.', (new TwitterOptions())->attachVideo($path)))
                    ->transport('twitter')
                ;
                $this->chatter->send($message);
            })
        ;

        $flow(new Ip([5, 5, $dataSet]));

        $flow->await();
    }
}
