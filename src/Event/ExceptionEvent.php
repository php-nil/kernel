<?php

namespace Nil\Kernel\Event;

use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Contracts\EventDispatcher\Event;
use Throwable;

/**
 * ExceptionEvent 执行异常事件
 *
 * 控制器或中间件抛出异常时触发。监听器调用 setResponse() 后，
 * 异常将被接管并以该响应输出；未接管时异常继续抛出。
 */
class ExceptionEvent extends Event
{
    /**
     * 响应对象（可能为 null）
     */
    private ?Response $response = null;

    /**
     * @param Request $request 当前请求对象
     * @param Throwable $throwable 异常对象
     */
    public function __construct(private Request $request, private Throwable $throwable) {}

    /**
     * 获取当前请求对象
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * 获取异常对象
     */
    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }

    /**
     * 设置响应并停止事件传播
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
        $this->stopPropagation();
    }

    /**
     * 检查是否已设置响应
     */
    public function hasResponse(): bool
    {
        return null !== $this->response;
    }

    /**
     * 获取响应对象
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }
}
