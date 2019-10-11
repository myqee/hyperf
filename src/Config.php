<?php

declare(strict_types = 1);

namespace MyQEE\Hyperf;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Server\Server;
use Hyperf\Server\SwooleEvent;
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
     * 初始化配置并生效
     *
     * 初始化后，数组的 myqee 将被替换成 \MyQEE\Server\Config 对象
     */
    public function setup() {
        $this->syncServersConfig();

        $config = \MyQEE\Server\Config::create($this->configs['myqee']);
        $this->configs['myqee']  = $config;

        $config->initConfig();
        $config->effectiveConfig();
    }

    /**
     * 同步 myqee 配置和 hyperf 中配置相同部分的参数（比如 servers mode 等）
     */
    public function syncServersConfig() {
        $MyQEEConfig     = $this->configs['myqee'] ?? [];
        $hyServerConfig = $this->configs['server'] ?? ['settings' => [], 'servers' => []];

        if (isset($hyServerConfig['mode'])) {
            $MyQEEConfig['mode'] = $hyServerConfig['mode'];
        }
        elseif (isset($MyQEEConfig['mode'])) {
            $hyServerConfig['mode'] = $MyQEEConfig['mode'];
        }

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

        $typeMap = [
            'ws'   => Server::SERVER_WEBSOCKET,
            'http' => Server::SERVER_HTTP,
            'tcp'  => Server::SERVER_BASE,
        ];
        $typeMapFlip = array_flip($typeMap);

        $servers = $MyQEEConfig['servers'] ?? [];
        $tmpHyServerNameIndex = [];
        foreach ($hyServerConfig['servers'] as $index => $item) {
            $name = $item['name'];
            $tmpHyServerNameIndex[$name] = $index;

            $item['type'] = $typeMapFlip[$item['type']] ?? $item['type'];
            $merge($item, $servers[$name] ?? []);
            $servers[$name] = $item;

            if (!isset($item['class'])) {
                $servers[$name]['class'] = 'Worker' . ucfirst($name);
            }
        }

        foreach ($servers as $name => $item) {
            if (!isset($tmpHyServerNameIndex[$name])) {
                // 将 servers 中服务器赋值过去
                $hyServerConfig[] = [
                    'name'      => $name,
                    'type'      => $typeMap[$item['type']] ?? Server::SERVER_BASE,
                    'host'      => $item['host'],
                    'port'      => $item['port'],
                    'sock_type' => SWOOLE_SOCK_TCP,
                    'callbacks' => [],
                ];
            }
        }

        $MyQEEConfig['servers'] = $servers;
        $MyQEEConfig['redis']   = $this->configs['redis'] = array_merge($this->configs['redis'] ?? [], $MyQEEConfig['redis'] ?? []);
        $MyQEEConfig['swoole']  = $hyServerConfig['settings'] = array_merge($hyServerConfig['settings'], $MyQEEConfig['swoole'] ?? []);
        $MyQEEConfig['log']     = $this->getMyQEELogConfig();

        $this->configs['server'] = $hyServerConfig;
        $this->configs['myqee']  = $MyQEEConfig;
    }

    /**
     * 获取日志配置
     *
     * @return array
     */
    public function getMyQEELogConfig() {
        $logConfig = $this->configs['myqee']['log'] ?? [];

        return array_merge_recursive([
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
    }
}
