<?php

namespace Nil\Kernel\Middleware;

use InvalidArgumentException;

abstract class MiddlewareHandler implements MiddlewareHandlerInterface
{
    /**
     * 中间件队列
     *
     * @var list<callable>
     */
    protected array $wares = [];

    /**
     * 核心操作
     *
     * @var callable|null
     */
    protected $middle;

    public function setMiddle(callable $middle): static
    {
        $this->middle = $middle;

        return $this;
    }

    public function hasMiddle(): bool
    {
        return null !== $this->middle;
    }

    /**
     * 添加中间件
     *
     * @param bool $isPre 为 true 时插入队列首部（最先执行，洋葱模型最外层）
     */
    public function addMiddleWare(callable $ware, bool $isPre = false): void
    {
        if ($isPre) {
            array_unshift($this->wares, $ware);
        } else {
            $this->wares[] = $ware;
        }
    }

    /**
     * 执行中间件链
     *
     * 以深度索引而非数组内部指针推进：同一层中间件多次调用 $next（重试/重入）
     * 每次都会完整经过后续层，且 handler 可重复执行。
     *
     * @param array<int, mixed> $param
     */
    protected function doHandle(array $param, int $depth = 0): mixed
    {
        if (!$this->hasMiddle()) {
            throw new InvalidArgumentException('未定义中间操作');
        }

        // 链已到底，执行核心操作
        if (!isset($this->wares[$depth])) {
            return ($this->middle)(...$param);
        }

        $ware = $this->wares[$depth];

        // next：固定从下一层开始，重复调用互不影响
        $param[] = function () use ($depth) {
            return $this->doHandle(func_get_args(), $depth + 1);
        };

        return $ware(...$param);
    }
}
