<?php

declare(strict_types=1);

namespace MyQEE\Hyperf;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Framework\Bootstrap\WorkerStartCallback;

class ConfigProvider {
    public function __invoke(): array {
        if (!defined('\\BASE_DIR')) {
            define('\\BASE_DIR', (defined('\\BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) .'/');
        }

        return [
            'dependencies' => [
                ConfigInterface::class        => ConfigFactory::class,
                StdoutLoggerInterface::class  => Logger::class,
                WorkerStartCallback::class    => Framework\Bootstrap\WorkerStartCallback::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];
    }
}
