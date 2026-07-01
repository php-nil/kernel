<?php
namespace Nil\Kernel\Middleware;

use InvalidArgumentException;

abstract class MiddlewareHandler implements MiddlewareHandlerInterface
{
    protected $wares = [];
    protected $middle;

    public function setMiddle(callable $middle)
    {
        $this->middle = $middle;

        return $this;
    }

    public function hasMiddle()
    {
        return null !== $this->middle;
    }

    // 添加中间件
    public function addMiddleWare(callable $ware, bool $isPre = false)
    {
        if ($isPre) {
            array_unshift($this->wares, $ware);
        } else {
            $this->wares[] = $ware;
        }

    }

    // 执行
    protected function doHandle(array $param)
    {
        if (!$this->hasMiddle()) {
            throw new InvalidArgumentException("未定义中间操作");
        }

        $ware = current($this->wares);

        // 完成
        if (false === $ware) {
            return call_user_func_array($this->middle, $param);
        }

        next($this->wares);

        // next
        $param[] = function () {
            return call_user_func_array([$this, 'handle'], func_get_args());
        };

        return call_user_func_array($ware, $param);
    }
}