# MyQEE Server For Hyperf

这是一个使得 MyQEE Server 可以兼容使用 Hyperf 的模块 (see https://www.hyperf.io )。Hyperf 是一个在 php7.2 和 swoole 4.4 基础上开发的 PHP 协程框架，具有强大的依赖注入功能以及功能丰富的组件，如果你以前熟悉 Laravel、Symfory 等框架，则会非常容易上手使用。

## 使用方法

### 在已有 MyQEE Server 项目中启用

1. 下载 [https://github.com/hyperf-cloud/hyperf-skeleton/archive/master.zip](https://github.com/hyperf-cloud/hyperf-skeleton/archive/master.zip) Hyperf 的骨骼代码，github地址：https://github.com/hyperf-cloud/hyperf-skeleton
2. 解压缩，将`config`目录及`bin/hyperf.php`文件复制到你的项目；
3. 执行 `composer require myqee/hyperf` 安装依赖；

> 如果你还需要 `hyperf` 的其它依赖内容，可参考 composer.json 中 require 部分，复制到你的 composer.json 文件中并执行 `composer update` 或 `composer require hyperf/***` 安装对应模块；

### 使用 hyperf/skeleton 骨骼结构安装

1. 创建新文件夹，运行 `composer create-project hyperf/hyperf-skeleton`，参考 https://doc.hyperf.io/#/zh/quick-start/install 
2. 执行 `composer require myqee/hyperf`

### 配置

MyQEE 相关配置可以放在 `config/autoload/myqee.php` 中，也可放在 `config/config.php` 里的数组 myqee 里。

## 差异事项

* 使用DI兼容处理了 hyperf/config；
* 使用DI将默认Logger处理设置为了MyQEE的Logger处理方法；
* 取代了 hyperf/server 的 start 命令并保持兼容；
* 对于http服务器，若使用了 `Hyperf\HttpServer\Server::class, 'onRequest'` 回调（在config/autoload/server.php中默认设置），系统会使用一个中间件处理，优先处理 Hyperf 默认的 http 逻辑，若不存在对应路由则使用MyQEE Server默认的路由规则；

虽然取代了以上功能但是和原来功能保持兼容。

### 高级使用

#### 创建一个自己的启动命令

首先，请简单的阅读并理解 `https://doc.hyperf.io/#/zh/command` 中关于命令行的说明。以下是一个简单的示例：

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Contract\ConfigInterface;
use MyQEE\Hyperf\ServerHyperfFactory;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 放在 app/Command/StartTestServer.php 
 * 或者 `config/autoload/annotations.php` 中设置的 scan 会扫描目录中
 * @Command
 */
class StartTestServer extends SymfonyCommand
{
    /**
     * @var ContainerInterface
     */
    private $container;
    
    protected $description = 'Start the test server.';

    public function __construct(ContainerInterface $container) {
        parent::__construct('server:test');     // 命令名称
        $this->container = $container;
    }

    protected function configure() {
        $this->setDescription($this->description);
        $this->addOption('daemon', 'd', InputOption::VALUE_NONE, 'daemonize server process');
    }

    protected function parseOption(InputInterface $input) {
         /**
         * @var $configFactory \MyQEE\Hyperf\Config
         */
        $configFactory = $this->container->get(ConfigInterface::class);
        $config = $configFactory->get('myqee');
        
        $daemon = $input->getOption('daemon');
        if ($daemon) {
            $config['swoole']['daemonize'] = 1;
        }
        $configFactory->set('myqee', $config);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        \Swoole\Runtime::enableCoroutine(true);

        # 处理自定义的参数（可选） ---------------
        $this->parseOption($input);


        # 启动服务 --------------------------
        /**
         * @var $serverFactory ServerHyperfFactory
         */
        $serverFactory = $this->container->get(ServerHyperfFactory::class);
        # 安装服务
        $serverFactory->setup();
        # 服务启动
        $serverFactory->start();
        # ---------------------------------


        # 若不想启动服务器，而是直接cli执行一些任务，但是又希望Config生效（比如log、php的ini等）
        # 可以这样做：
         /**
         * @var $configFactory \MyQEE\Hyperf\Config
         */
        $configFactory = $this->container->get(ConfigInterface::class);
        # 这样配置就会初始化
        $configFactory->setup();

        // do something
        // ......
    }
}
```

### 更多

可以将原来 MyQEE 用到的配置放在 `config/autoload/myqee.php` 中（不存在，需要自己创建），这样启动时可以自动加载。