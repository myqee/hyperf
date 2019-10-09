<?php

declare(strict_types=1);

namespace MyQEE\Hyperf\Command;

use MyQEE\Hyperf\ServerHyperfFactory;
use Hyperf\Command\Annotation\Command;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @Command
 */
class StartServer extends SymfonyCommand
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container) {
        parent::__construct('start');
        $this->container = $container;
    }

    protected function configure() {
        $this->setDescription('Start the server.');
        $this->addOption('daemon', 'd', InputOption::VALUE_NONE, 'daemonize server process');
        $this->addOption('log', 'l', InputOption::VALUE_OPTIONAL, 'log file path');
        $this->addOption('worker-num', 'w', InputOption::VALUE_OPTIONAL, 'worker process number');
        $this->addOption('pid-file', null, InputOption::VALUE_OPTIONAL, 'pid file');
        $this->addOption('host', null, InputOption::VALUE_OPTIONAL, 'main server hostname', '0.0.0.0');
        $this->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'main server port');
        $this->addOption('group', 'group', InputOption::VALUE_OPTIONAL, 'change group');
        $this->addOption('user', 'user', InputOption::VALUE_OPTIONAL, 'change user');
    }

    protected function parseOption(InputInterface $input, & $config) {
        $log = $input->getOption('log');
        if ($log) {
            $config['log']['path'] = $log;
        }

        $daemon = $input->getOption('daemon');
        if ($daemon) {
            $config['swoole']['daemonize'] = 1;
        }

        $user = $input->getOption('user');
        if ($user) {
            $config['swoole']['user'] = $user;
        }

        $group = $input->getOption('group');
        if ($group) {
            $config['swoole']['user'] = $group;
        }

        $pidFile = $input->getOption('pid-file');
        if ($pidFile) {
            $config['swoole']['pid_file'] = $pidFile;
        }

        $host = $input->getOption('host');
        if ($host) {
            $mainKey                           = key($config['hosts']);
            $config['hosts'][$mainKey]['host'] = $host;
        }

        $port = intval($input->getOption('port'));
        if ($port > 0) {
            $mainKey                           = key($config['hosts']);
            $config['hosts'][$mainKey]['port'] = $port;
        }

        $workerNum = intval($input->getOption('worker-num'));
        if ($workerNum > 0) {
            $config['swoole']['worker_num'] = $workerNum;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        \Swoole\Runtime::enableCoroutine(true);

        /**
         * @var $serverFactory ServerHyperfFactory
         */
        $serverFactory = $this->container->get(ServerHyperfFactory::class)
            ->setEventDispatcher($this->container->get(EventDispatcherInterface::class))
            ->setLogger($this->container->get(StdoutLoggerInterface::class));

        /**
         * @var $configFactory \MyQEE\Hyperf\Config
         */
        $configFactory = $this->container->get(ConfigInterface::class);
        $serverConfig = $configFactory->get('server', []);
        if (!$serverConfig) {
            throw new \InvalidArgumentException('At least one server should be defined.');
        }
        $myqeeConfig = $configFactory->getMyQEEConfig();

        $this->parseOption($input, $myqeeConfig);

        # 创建 MyQEE 服务器
        $serverFactory->createMyQEEServer($myqeeConfig);

        # 配置 Hyperf 参数
        $serverFactory->configure($serverConfig);

        # 服务启动
        $serverFactory->start();
    }
}
