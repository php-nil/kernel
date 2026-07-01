<?php

namespace Nil\Kernel\Event;

use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Contracts\EventDispatcher\Event;
use Throwable;

/**
 * NotMatchedEvent 路由未匹配事件
 * 
 * 当请求无法匹配到任何路由时触发的事件，用于处理 404 或其他路由错误。
 */
class NotMatchedEvent extends Event
{
    /**
     * 响应对象（可能为 null）
     */
    private ?Response $response = null;

    /**
     * 构造函数
     *
     * @param Request $request 当前请求对象
     * @param Throwable $throwable 异常对象
     */
    public function __construct(private Request $request, private Throwable $throwable) {}

    /**
     * 获取异常对象
     *
     * @return Throwable
     */
    public function getThrowable(): Throwable
    {
        return $this->throwable;
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
