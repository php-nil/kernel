<?php

namespace Nil\Kernel\Event;

use Closure;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Contracts\EventDispatcher\Event;
use Nil\Kernel\Middleware\ControllerMiddlewareHandler;

/**
 * MatchedEvent 路由匹配后事件
 * 
 * 在路由匹配完成后、控制器调用前触发的事件，用于修改请求、设置响应或替换控制器。
 */
class MatchedEvent extends Event
{
    /**
     * 当前请求对象
     */
    private Request $request;

    /**
     * 响应对象（可能为 null）
     */
    private ?Response $response = null;

    /**
     * 中间件处理器（懒加载）
     */
    private ?ControllerMiddlewareHandler $handler = null;

    /**
     * 构造函数
     *
     * @param Request $request 当前请求对象
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * 获取中间件处理器（懒加载）
     *
     * @return ControllerMiddlewareHandler
     */
    public function getMiddlewareHandler(): ControllerMiddlewareHandler
    {
        if (null === $this->handler) {
            $this->handler = new ControllerMiddlewareHandler();
        }

        return $this->handler;
    }

    /**
     * 获取当前请求对象
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * 重新定义指定路由的控制器
     *
     * @param string $route 路由名称
     * @param callable $controller 控制器闭包或回调函数
     *
     * @return void
     */
    public function restController(string $route, callable $controller): void
    {
        if ($route === $this->request->attributes->get('_route')) {
            if ($controller instanceof Closure) {
                $controller = $controller();
            }

            $this->request->attributes->set('_controller', $controller);
        }
    }

    /**
     * 设置响应并停止事件传播
     *
     * @param Response $response 响应对象
     *
     * @return void
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
        $this->stopPropagation();
    }

    /**
     * 检查是否已设置响应
     *
     * @return bool
     */
    public function hasResponse(): bool
    {
        return null !== $this->response;
    }

    /**
     * 获取响应对象
     *
     * @return Response|null
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }
}