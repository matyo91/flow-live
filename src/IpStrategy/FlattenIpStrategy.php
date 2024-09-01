<?php

declare(strict_types=1);

namespace Flow\IpStrategy;

use Flow\Event;
use Flow\Event\PoolEvent;
use Flow\Event\PullEvent;
use Flow\Event\PushEvent;
use Flow\Exception\LogicException;
use Flow\Ip;
use Flow\IpPool;
use Flow\IpStrategyInterface;

/**
 * @template T
 *
 * @implements IpStrategyInterface<T>
 */
class FlattenIpStrategy implements IpStrategyInterface
{
    /**
     * @var IpPool<T>
     */
    private IpPool $ipPool;

    public function __construct()
    {
        $this->ipPool = new IpPool();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Event::PUSH => 'push',
            Event::PULL => 'pull',
            Event::POOL => 'pool',
        ];
    }

    /**
     * @param PushEvent<T> $event
     */
    public function push(PushEvent $event): void
    {
        $ip = $event->getIp();
        if (!is_iterable($ip->data)) {
            throw new LogicException('Ip data must be iterable');
        }
        foreach ($ip->data as $data) {
            $this->ipPool->addIp(new Ip($data));
        }
    }

    /**
     * @param PullEvent<T> $event
     */
    public function pull(PullEvent $event): void
    {
        $ip = $this->ipPool->shiftIp();
        if ($ip !== null) {
            $event->addIp($ip);
        }
    }

    public function pool(PoolEvent $event): void
    {
        $event->addIps($this->ipPool->getIps());
    }
}
