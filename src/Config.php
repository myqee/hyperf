<?php

declare(strict_types = 1);

namespace MyQEE\Hyperf;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Server\Server;
use Hyperf\Utils\Arr;

class Config implements ConfigInterface {
    /**
     * @var array
     */
    private $configs = [];

    public function __construct(array $configs) {
        # 自动添加中间件
        foreach ($configs['server']['servers'] as $item) {
            if ($item['type'] === Server::SERVER_HTTP) {
                $configs['middlewares'][$item['name']][] = HttpMiddleware::class;
            }
        }
        $this->configs = $configs;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $key identifier of the entry to look for
     * @param mixed $default default value of the entry when does not found
     * @return mixed entry
     */
    public function get(string $key, $default = null) {
        return data_get($this->configs, $key, $default);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $key identifier of the entry to look for
     * @return bool
     */
    public function has(string $key) {
        return Arr::has($this->configs, $key);
    }

    /**
     * Set a value to the container by its identifier.
     *
     * @param string $key identifier of the entry to set
     * @param mixed $value the value that save to container
     */
    public function set(string $key, $value) {
        data_set($this->configs, $key, $value);
    }

    /**
     * 获取供MyQEE服务器使用的配置
     *
     * @return array
     */
    public function getMyQEEConfig() {
        $myqeeConfig  = $this->configs['myqee'] ?? [];
        $serverConfig = $this->configs['server'];

        $merge = function(& $arr1, $arr2) use (& $merge) {
            foreach ($arr2 as $k => $v) {
                if (is_array($v) && isset($arr1[$k]) && is_array($arr1[$k])) {
                    $merge($arr1[$k], $v);
                }
                else {
                    $arr1[$k] = $v;
                }
            }
        };

        $hosts = $myqeeConfig['hosts'] ?? [];
        foreach ($serverConfig['servers'] as $item) {
            $name = $item['name'];

            switch ($item['type']) {
                case Server::SERVER_WEBSOCKET:
                    $type = 'ws';
                    break;
                case Server::SERVER_HTTP:
                    $type = 'http';
                    break;
                case Server::SERVER_BASE:
                    $type = 'tcp';
                    break;
                default:
                    $type = $item['type'];
                    break;
            }

            $item['type'] = $type;
            $merge($item, $hosts[$name] ?? []);
            $hosts[$name] = $item;

            if (!isset($item['class'])) {
                $hosts[$name]['class'] = 'Worker' . ucfirst($name);
            }
        }
        $myqeeConfig['hosts']  = $hosts;
        $myqeeConfig['log']    = $this->getMyQEELogConfig();
        $myqeeConfig['swoole'] = array_merge($serverConfig['settings'], $myqeeConfig['swoole'] ?? []);
        $myqeeConfig['redis']  = array_merge($this->configs['redis'] ?? [], $myqeeConfig['redis'] ?? []);

        return $myqeeConfig;
    }

    /**
     * 获取日志配置
     *
     * @return array
     */
    public function getMyQEELogConfig() {
        $logConfig = $this->configs['myqee']['log'] ?? [];

        $rs = array_merge([
            'level'         => \MyQEE\Server\Logger::INFO,
            'stdout'        => false,
            'path'          => false,
            'withFilePath'  => true,
            'loggerProcess' => false,
            'active'        => [
                'sizeLimit' => 0,
                'timeLimit' => false,
                'timeKey'   => null,
                'compress'  => false,
                'prefix'    => 'active.',
                'path'      => null,
            ],
        ], $logConfig);

        global $argv;

        if (in_array('-vvv', $argv) || in_array('--dev', $argv)) {
            $rs['level'] = \MyQEE\Server\Logger::TRACE;
        }
        elseif (in_array('-vv', $argv) || in_array('--debug', $argv)) {
            $rs['level'] = \MyQEE\Server\Logger::DEBUG;
        }
        elseif (in_array('-v', $argv)) {
            $rs['level'] = \MyQEE\Server\Logger::INFO;
        }

        return $rs;
    }
}
