<?php

declare(strict_types=1);

namespace App\Scheduler\Task;

use App\EnumType\WaveFunctionCollapse\DataSetEnumType;
use App\Flow\WaveFunctionCollapse\FlowFactory;
use Flow\Ip;
use Symfony\Component\Mime\Part\File;
use Symfony\Component\Notifier\Bridge\Twitter\TwitterOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

// #[AsCronTask('0 7 * * *', arguments: ['memory' => 256, 'width' => 5, 'height' => 5])]
#[AsCronTask('* * * * *', arguments: ['memory' => 256, 'width' => 5, 'height' => 5])]
final class FlowTask
{
    public function __construct(
        private FlowFactory $flowFactory,
        private ChatterInterface $chatter,
    ) {}

    public function __invoke(int $memory, int $width, int $height): void
    {
        if ($memory) {
            ini_set('memory_limit', $memory . 'M');
        }

        $dataSet = DataSetEnumType::cases()[array_rand(DataSetEnumType::cases())];

        $flow = $this->flowFactory->doMp4($dataSet)
            ->fn(function ($path) {
                $message = (new ChatMessage('Daily Flow generation.', (new TwitterOptions())->attachVideo(new File($path))))
                    ->transport('twitter')
                ;
                $this->chatter->send($message);

                unlink($path);
            })
        ;

        $flow(new Ip([$width, $height, $dataSet]));

        $flow->await();
    }
}
