<?php

declare(strict_types=1);

namespace MyQEE\Hyperf\Command;

use Hyperf\Contract\ConfigInterface;
use MyQEE\Hyperf\ServerHyperfFactory;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @Command
 */
class StartServer extends SymfonyCommand {
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ConfigInterface
     */
    protected $config;

    protected $description = 'Start the server.';

    public function __construct(ContainerInterface $container, ConfigInterface $config) {
        parent::__construct('start');
        $this->container = $container;
        $this->config    = $config;
    }

    protected function configure() {
        $this->setDescription($this->description);
        $this->addOption('daemon',     'd', InputOption::VALUE_NONE,     'daemonize server process');
        $this->addOption('log',        'l', InputOption::VALUE_OPTIONAL, 'log file path');
        $this->addOption('worker-num', 'w', InputOption::VALUE_OPTIONAL, 'worker process number');
        $this->addOption('pid-file',  null, InputOption::VALUE_OPTIONAL, 'pid file');
        $this->addOption('host',      null, InputOption::VALUE_OPTIONAL, 'main server hostname');
        $this->addOption('port',       'p', InputOption::VALUE_OPTIONAL, 'main server port');
        $this->addOption('group',  'group', InputOption::VALUE_OPTIONAL, 'change group');
        $this->addOption('user',    'user', InputOption::VALUE_OPTIONAL, 'change user');
    }

    protected function parseOption(InputInterface $input) {
        $config = $this->config->get('myqee');

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

        if (isset($config['servers']) && is_array($config['servers'])) {
            $host = $input->getOption('host');
            if ($host) {
                $mainKey = key($config['servers']);

                $config['servers'][$mainKey]['host'] = $host;
            }
            $port = intval($input->getOption('port'));
            if ($port > 0) {
                $mainKey = key($config['servers']);

                $config['servers'][$mainKey]['port'] = $port;
            }
        }

        $workerNum = intval($input->getOption('worker-num'));
        if ($workerNum > 0) {
            $config['worker_num'] = $config['swoole']['worker_num'] = $workerNum;
        }

        $this->config->set('myqee', $config);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->parseOption($input);

        /**
         * @var $serverFactory ServerHyperfFactory
         */
        $serverFactory = $this->container->get(ServerHyperfFactory::class);

        # 安装服务
        $serverFactory->setup();

        # 服务启动
        $serverFactory->start();
        
        return 0;
    }
}
