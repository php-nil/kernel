<?php
namespace Nil\Kernel\Middleware;

use Symfony\Component\HttpFoundation\{Request, Response};
use InvalidArgumentException;

class ControllerMiddlewareHandler extends MiddlewareHandler
{
    // 自定义
    public function handle(...$param): Response
    {
        if (\count($param) != 1 || !$param[0] instanceof Request) {
            throw new InvalidArgumentException("首个参数必须为 Request");
        }

        return $this->doHandle($param);
    }
}