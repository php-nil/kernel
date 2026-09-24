<?php

namespace Nil\Kernel\Event;

use Symfony\Component\HttpFoundation\{Response, Request};
use Symfony\Contracts\EventDispatcher\Event;

/**
 * ResponseEvent 响应输出前事件
 *
 * 在 Response 发送前触发，可读取请求并替换最终响应。
 */
class ResponseEvent extends Event
{
    public function __construct(
        protected Request $request,
        protected Response $response,
    ) {
    }

    /**
     * 获取当前请求对象
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * 获取响应对象
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * 替换最终响应并停止事件传播
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
        $this->stopPropagation();
    }

    /**
     * 仅当当前路由匹配时执行回调
     *
     * @param string $route 路由名称
     * @param callable $call 回调，接收当前 ResponseEvent
     */
    public function handleRoute(string $route, callable $call): void
    {
        if ($route === $this->request->attributes->get('_route')) {
            $call($this);
        }
    }

    /**
     * @deprecated 使用 handleRoute() 代替，保留以兼容旧代码
     */
    public function handelRoute(string $route, callable $call): void
    {
        $this->handleRoute($route, $call);
    }
}
