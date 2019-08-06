<?php

declare(strict_types=1);

namespace MyQEE\Hyperf;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Framework\Bootstrap\WorkerStartCallback;

class ConfigProvider
{
    public function __invoke(): array {
        return [
            'dependencies' => [
                ConfigInterface::class        => ConfigFactory::class,
                StdoutLoggerInterface::class  => Logger::class,
                WorkerStartCallback::class    => Framework\Bootstrap\WorkerStartCallback::class,
            ],
            'commands' => [
            ],
            'scan' => [
                'paths' => [
                    __DIR__,
                ],
            ],
        ];
    }
}
