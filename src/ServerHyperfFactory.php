<?php

declare(strict_types=1);

namespace MyQEE\Hyperf;

use Hyperf\Server\ServerInterface;
use MyQEE\Server\Server;
use Hyperf\Server\ServerFactory;

class ServerHyperfFactory extends ServerFactory {
    /**
     * @var \MyQEE\Server\Server
     */
    protected $myqeeServer;

    public function start() {
        # 创建 Hyperf 服务器
        $this->getServer();

        # 启动 swoole 服务器
        $this->myqeeServer->server->start();
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

    public function createMyQEEServer(array $config) {
        if (Server::$instance)return Server::$instance;

        /**
         * @var $server \MyQEE\Server\Server
         */
        $server = null;
        foreach ([\Server::class, Server::class] as $class) {
            if (class_exists($class)) {
                $server = new $class($config);
                break;
            }
        }

        $server->setup();
        $this->myqeeServer = $server;

        return $server;
    }
}
