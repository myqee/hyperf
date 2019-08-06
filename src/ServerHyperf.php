<?php

declare(strict_types=1);

namespace MyQEE\Hyperf;

use Hyperf\Contract\MiddlewareInitializerInterface;
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
                            Server::$instance->warn("worker name not found: {$serverName}");
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
