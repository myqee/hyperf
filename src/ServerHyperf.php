<?php

declare(strict_types=1);

namespace MyQEE\Hyperf;

use Hyperf\Contract\MiddlewareInitializerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Hyperf\Framework\Event\BeforeServerStart;
use Hyperf\Server\ServerConfig;
use Hyperf\Server\ServerManager;
use Hyperf\Server\SwooleEvent;
use Hyperf\Utils\Context;
use MyQEE\Server\Server;

class ServerHyperf extends \Hyperf\Server\Server {

    protected function makeServer(int $type, string $host, int $port, int $mode, int $sockType) {
        if (Server::$instance->server) {
            return Server::$instance->server;
        }
        else {
            return parent::makeServer($type, $host, $port, $mode, $sockType);
        }
    }

    protected function initServers(ServerConfig $config) {
        $servers = $this->sortServers($config->getServers());

        foreach ($servers as $server) {
            $name = $server->getName();
            $type = $server->getType();
            $host = $server->getHost();
            $port = $server->getPort();
            $callbacks = $server->getCallbacks();

            if (!$this->server instanceof \Swoole\Server) {
                $this->server = Server::$instance->server;
                ServerManager::add($name, [$type, current($this->server->ports)]);

                if (class_exists(BeforeMainServerStart::class)) {
                    // Trigger BeforeMainEventStart event, this event only trigger once before main server start.
                    $this->eventDispatcher->dispatch(new BeforeMainServerStart($this->server, $config->toArray()));
                }
            } else {
                $slaveServer = Server::$instance->portListens["$host:$port"] ?? null;
                if ($slaveServer) {
                    ServerManager::add($name, [$type, $slaveServer]);
                }
                else {
                    $this->logger->warning("Not found port: $host:$port");
                }
            }

            // Trigger beforeStart event.
            if (isset($callbacks[SwooleEvent::ON_BEFORE_START])) {
                [$class, $method] = $callbacks[SwooleEvent::ON_BEFORE_START];
                if ($this->container->has($class)) {
                    $this->container->get($class)->{$method}();
                }
            }

            if (class_exists(BeforeServerStart::class)) {
                // Trigger BeforeEventStart event.
                $this->eventDispatcher->dispatch(new BeforeServerStart($name));
            }
        }
    }

    /**
     * @param \Swoole\Server $server
     */
    protected function registerSwooleEvents($server, array $events, string $serverName): void {
        # 停用 Hyperf 默认的注册方法
        foreach ($events as $event => $callback) {
            if (!SwooleEvent::isSwooleEvent($event)) {
                continue;
            }

            if (is_array($callback)) {
                [$className, $method] = $callback;
                if (array_key_exists($className . $method, $this->onRequestCallbacks)) {
                    $this->logger->warning(sprintf('%s will be replaced by %s, each server should has own onRequest callback, please check your configs.', $this->onRequestCallbacks[$className . $method], $serverName));
                }

                $this->onRequestCallbacks[$className . $method] = $serverName;
                $class = $this->container->get($className);
                if (method_exists($class, 'setServerName')) {
                    // Override the server name.
                    $class->setServerName($serverName);
                }
                if ($class instanceof MiddlewareInitializerInterface) {
                    $class->initCoreMiddleware($serverName);
                }
                $callback = [$class, $method];
            }

            switch ($event) {
                case SwooleEvent::ON_WORKER_START:
                    # 绑定事件
                    Server::$instance->event->on($event, $callback);
                    break;

                default:
                    # 绑定启动后的worker事件
                    Server::$instance->event->on('workerStart', function($server, $workerId) use ($event, $callback, $serverName) {
                        $worker = Server::$instance->workers[$serverName] ?? null;
                        if (!$worker) {
                            $this->logger->warning("worker name not found: {$serverName}");
                            return;
                        }
                        /**
                         * @var $worker \MyQEE\Server\Worker
                         */
                        if ($event === SwooleEvent::ON_REQUEST) {
                            $worker->event->on($event, function($req, $rep) use ($callback, $serverName) {
                                Context::set('request.worker', Server::$instance->workers[$serverName] ?? null);
                                call_user_func($callback, $req, $rep);
                                return false;
                            });
                        }
                        else {
                            $worker->event->on($event, $callback);
                        }
                    });
                    break;
            }
        }
    }
}
