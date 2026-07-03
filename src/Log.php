<?php

namespace Nil\Kernel;

use Monolog\Logger;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Nil\Nil;

// Nil::use('monolog.monolog');

/**
 * Log 日志管理器
 * 
 * 负责管理多个 Monolog 日志实例，支持多种日志处理器。
 */
class Log
{
    /**
     * 日志实例存储
     *
     * @var array<string, Logger>
     */
    protected array $logs = [];

    /**
     * 默认日志处理器
     */
    private ?HandlerInterface $defaultHandler = null;

    /**
     * 设置默认日志处理器
     *
     * @param HandlerInterface $handler
     *
     * @return void
     */
    public function setDefaultHandler(HandlerInterface $handler): void
    {
        $this->defaultHandler = $handler;
    }

    /**
     * 获取默认日志处理器
     *
     * 如果未设置，自动创建 RotatingFileHandler。
     *
     * @return HandlerInterface
     */
    public function getDefaultHandler(): HandlerInterface
    {
        if (!isset($this->defaultHandler)) {
            $this->defaultHandler = new RotatingFileHandler(
                Kernel::path()->LOG . \DIRECTORY_SEPARATOR . 'default_rotating.log',
                0,
                Level::Info
            );
        }

        return $this->defaultHandler;
    }

    /**
     * 设置日志实例
     *
     * @param string $name 日志名称
     * @param array $useHandler 处理器列表
     * @param array $processors 处理器列表
     *
     * @return Logger
     */
    public function setLogger(string $name, array $useHandler = [], array $processors = []): Logger
    {
        return $this->logs[$name] = $this->newLogger($name, $useHandler, $processors);
    }

    /**
     * 创建新的日志实例
     *
     * @param string $name 日志名称
     * @param array $useHandler 处理器列表，为空则使用默认处理器
     * @param array $processors 处理器列表
     *
     * @return Logger
     */
    public function newLogger(string $name, array $useHandler = [], array $processors = []): Logger
    {
        $handlers = empty($useHandler) ? [$this->getDefaultHandler()] : $useHandler;

        return new Logger($name, $handlers, $processors);
    }

    /**
     * 基于已有日志创建新日志实例
     *
     * @param string $name 新日志名称
     * @param string $from 源日志名称
     *
     * @return Logger
     */
    public function cloneFromName(string $name, string $from): Logger
    {
        return $this->logs[$name] ??= $this->withName($from)->withName($name);
    }

    // public function withDefault(): Logger
    // {
    //     return $this->withName(DEFAULT_NAME);
    // }

    /**
     * 获取指定名称的日志实例
     *
     * @param string|null $name 日志名称，默认为 DEFAULT_NAME
     *
     * @return Logger
     */
    public function withName(?string $name = null): Logger
    {
        return $this->logs[$name ?? DEFAULT_NAME] ??= $this->newLogger($name ?? DEFAULT_NAME);
    }

    public function withStreamLogger(string $name): Logger
    {
        if (!isset($this->logs[$name])) {
            $handler = new StreamHandler(
                Kernel::path()->LOG . \DIRECTORY_SEPARATOR . $name . '.stream.log'
            );

            $this->logs[$name] = $this->newLogger($name, [$handler]);
        }

        return $this->logs[$name];
    }

    /**
     * 检查日志实例是否存在
     *
     * @param string $name 日志名称
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->logs[$name]);
    }

    /**
     * 魔术方法：快捷获取日志实例
     *
     * @param string|null $name 日志名称，默认为默认日志
     *
     * @return Logger
     */
    public function __invoke(?string $name = null): Logger
    {
        return $this->withName($name ?? DEFAULT_NAME);
    }
}