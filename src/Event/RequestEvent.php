<?php

namespace Nil\Kernel\Event;

use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Contracts\EventDispatcher\Event;

/**
 * RequestEvent 请求事件
 * 
 * 在请求处理开始时触发的事件，用于修改请求或提前设置响应。
 */
class RequestEvent extends Event
{
    /**
     * 请求对象（懒加载）
     */
    private ?Request $request = null;

    /**
     * 响应对象（可能为 null）
     */
    private ?Response $response = null;

    /**
     * 获取请求对象（懒加载）
     *
     * 如果未设置，自动从全局变量创建请求对象。
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        if (null === $this->request) {
            $this->request = Request::createFromGlobals();
        }

        return $this->request;
    }

    /**
     * 设置请求对象
     *
     * @param Request $request 请求对象
     *
     * @return void
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
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

    /**
     * 设置响应并停止事件传播
     *
     * 一旦设置响应，后续监听器将不会被调用。
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
}
