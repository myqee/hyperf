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

### 更多

可以将原来 MyQEE 用到的配置放在 `config/autoload/myqee.php` 中（不存在，需要自己创建），这样启动时可以自动加载。