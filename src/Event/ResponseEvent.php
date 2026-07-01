<?php

namespace Nil\Kernel\Event;

use Symfony\Component\HttpFoundation\{Response, Request};
use Symfony\Contracts\EventDispatcher\Event;
// 输出前
class ResponseEvent extends Event
{
    public function __construct(protected readonly Request $request, protected readonly Response $response)
    {
    }

    /**
     * @deprecated 待废弃
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * @deprecated 待废弃
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    // 执行路由操作
    // 缓存内容
    public function handelRoute(string $route, callable $call)
    {
        if ($route == $this->request->attributes->get('_route')) {
            // 执行指定路由操作
            $call($this);
        }
    }
}
