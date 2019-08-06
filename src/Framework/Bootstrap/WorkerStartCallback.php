<?php

declare(strict_types = 1);

namespace MyQEE\Hyperf\Framework\Bootstrap;

use Hyperf\Framework\Event\AfterWorkerStart;
use Hyperf\Framework\Event\BeforeWorkerStart;
use Hyperf\Framework\Event\MainWorkerStart;
use Hyperf\Framework\Event\OtherWorkerStart;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Server as SwooleServer;

class WorkerStartCallback {
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher) {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Handle Swoole onWorkerStart event.
     */
    public function onWorkerStart(SwooleServer $server, int $workerId) {
        $this->eventDispatcher->dispatch(new BeforeWorkerStart($server, $workerId));

        if ($workerId === 0) {
            $this->eventDispatcher->dispatch(new MainWorkerStart($server, $workerId));
        }
        else {
            $this->eventDispatcher->dispatch(new OtherWorkerStart($server, $workerId));
        }
        $this->eventDispatcher->dispatch(new AfterWorkerStart($server, $workerId));
    }
}
