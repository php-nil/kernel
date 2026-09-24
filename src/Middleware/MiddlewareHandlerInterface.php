<?php
namespace Nil\Kernel\Middleware;

interface MiddlewareHandlerInterface
{
    // 处理
    public function handle(mixed ...$param): mixed;
}