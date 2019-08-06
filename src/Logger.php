<?php

declare(strict_types = 1);


namespace MyQEE\Hyperf;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use \MyQEE\Server\Logger as MyQEELogger;

/**
 * Default logger for logging server start and requests.
 * PSR-3 logger implementation that logs to STDOUT, using a newline after each
 * message. Priority is ignored.
 */
class Logger implements StdoutLoggerInterface {
    /**
     * @var \MyQEE\Hyperf\Config
     */
    private $config;

    protected $logger;

    use \MyQEE\Server\Traits\Log;

    public function __construct(ConfigInterface $config) {
        $this->config = $config;
        $logConfig    = $this->config->getMyQEELogConfig();

        MyQEELogger::init($logConfig);
        $this->logger = MyQEELogger::instance();
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = []) {
        $this->logger->log($level, $message, $context);
    }
}
