<?php

declare(strict_types=1);

namespace MyQEE\Hyperf;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Server\ServerInterface;
use MyQEE\Server\Server;
use Hyperf\Server\ServerFactory;
use Psr\EventDispatcher\EventDispatcherInterface;

class ServerHyperfFactory extends ServerFactory {
    /**
     * @var \MyQEE\Server\Server
     */
    protected $myqeeServer;

    public function start() {
        # 创建 Hyperf 服务器
        $this->getServer();

        \Swoole\Runtime::enableCoroutine(true, swoole_hook_flags());

        # 启动 swoole 服务器
        $this->myqeeServer->server->start();
    }

    public function setup() {
        /**
         * @var $configFactory \MyQEE\Hyperf\Config
         */
        $configFactory = $this->container->get(ConfigInterface::class);

        $configFactory->setup();

        $this->createServer();

        $this->setEventDispatcher($this->container->get(EventDispatcherInterface::class))
             ->setLogger($this->container->get(StdoutLoggerInterface::class));

        # 配置 Hyperf 参数
        $this->configure($configFactory->get('server'));
    }

    public function getServer(): ServerInterface {
        if (!$this->server instanceof ServerInterface) {
            $this->server = new ServerHyperf(
                $this->container,
                $this->getLogger(),
                $this->getEventDispatcher()
            );
        }
        return $this->server;
    }

    /**
     * @return Server
     */
    public function createServer() {
        if (Server::$instance) {
            return Server::$instance;
        }
        
        /**
         * @var $server Server
         * @var $config \MyQEE\Server\Config
         */
        $config = $this->container->get(ConfigInterface::class)->get('myqee', []);
        $class  = $config['dependencies'][Server::class] ?? Server::class;
        $server = new $class($config);
        $this->myqeeServer = $server;
        $server->setup();
        return $server;
    }
}
