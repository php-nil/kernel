<?php
namespace Nil\Kernel;

use Symfony\Component\EventDispatcher\EventDispatcher;

interface EventAppInterface
{
    // 事件
    public function kernelEvent(EventDispatcher $dispatcher);
}