<?php
namespace Nil\Kernel;

use Symfony\Component\EventDispatcher\EventDispatcher;

interface EventCollectorInterface
{
    // 事件收集器
    public static function kernelEvent(EventDispatcher $dispatcher);
}