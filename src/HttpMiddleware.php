<?php

namespace MyQEE\Hyperf;

use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HttpMiddleware implements MiddlewareInterface {
    /**
     * @var ContainerInterface
     */
    protected $container;

    static $swEmptyResponse = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        if (null === self::$swEmptyResponse) {
            self::$swEmptyResponse = new class () extends \Swoole\Http\Response {
                function status($code, $reason = null) {}
                function end($html = '') {}
                function header($key, $value, $ucwords = null) {}
            };
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        /**
         * @var $response \Hyperf\HttpMessage\Server\Response
         */
        $response = $handler->handle($request);
        $response = $response->withHeader('Server', $response->getSwooleResponse()->header['Server']);

        if ($response->getStatusCode() === 404) {
            $worker = Context::get('request.worker');
            if ($worker) {
                try {
                    /**
                     * @var $worker \MyQEE\Server\Worker\SchemeHttp
                     * @var $request \Hyperf\HttpMessage\Server\Request
                     */
                    $worker->onRequest($request->getSwooleRequest(), $response->getSwooleResponse());
                }
                catch (\Swoole\ExitException $e) {}
                catch (\Exception $e){$worker->trace($e);}
                catch (\Throwable $t){$worker->trace($t);}

                // 设置一个不会输出的 Response 对象
                return $response->setSwooleResponse(self::$swEmptyResponse);
            }
        }

        return $response;
    }
}