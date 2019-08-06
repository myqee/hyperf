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

    public function __construct(ContainerInterface $container)
    {
        parent::__construct('start');
        $this->container = $container;

        $this->setDescription('Start the server.');
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

        # 创建 MyQEE 服务器
        $serverFactory->createMyQEEServer($myqeeConfig);

        # 配置 Hyperf 参数
        $serverFactory->configure($serverConfig);

        # 服务启动
        $serverFactory->start();
    }
}
