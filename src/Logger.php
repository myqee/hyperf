<?php

declare(strict_types = 1);


namespace MyQEE\Hyperf;

use Hyperf\Contract\StdoutLoggerInterface;

/**
 * Default logger for logging server start and requests.
 * PSR-3 logger implementation that logs to STDOUT, using a newline after each
 * message. Priority is ignored.
 */
class Logger implements StdoutLoggerInterface {
    use \MyQEE\Server\Traits\Log;

    public function __construct() {

    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = []) {
        \MyQEE\Server\Logger::instance()->addRecord(\MyQEE\Server\Logger::NOTICE, $message, $context);
    }
}
