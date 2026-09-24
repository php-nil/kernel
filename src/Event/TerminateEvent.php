<?php

namespace Nil\Kernel\Event;

use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Contracts\EventDispatcher\Event;

/**
 * TerminateEvent 响应输出后终止事件（扫尾工作）
 */
class TerminateEvent extends Event
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
     * 获取最终响应对象
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
