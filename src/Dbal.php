<?php

namespace Nil\Kernel;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Logging\Middleware;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Handler\TestHandler;
use Monolog\Handler\HandlerInterface;

// Nil::use(['doctrine.dbal']);

/**
 * Dbal 数据库连接管理器
 * 
 * 负责管理多个数据库连接的配置和生命周期，支持懒加载创建连接。
 */
class Dbal
{
    /**
     * 已建立的数据库连接缓存
     *
     * @var array<string, Connection>
     */
    private array $connections = [];

    /**
     * 数据库连接配置
     *
     * @var array<string, array>
     */
    private array $config = [];

    /**
     * 日志处理器（用于 SQL 日志记录）
     */
    protected ?HandlerInterface $handler = null;

    /**
     * 设置默认数据库连接配置
     *
     * @param mixed ...$params 连接参数，格式同 DriverManager::getConnection
     *
     * @return self
     */
    public function setDefaultConfig(...$params): self
    {
        $this->config[DEFAULT_NAME] = $params;

        return $this;
    }

    /**
     * 设置指定名称的数据库连接配置
     *
     * @param string $name 连接名称
     * @param mixed ...$params 连接参数，格式同 DriverManager::getConnection
     *
     * @return self
     */
    public function setConfig(string $name, ...$params): self
    {
        $this->config[$name] = $params;

        return $this;
    }

    /**
     * 创建数据库连接
     *
     * @param mixed ...$params 连接参数
     *
     * @return Connection
     */
    public function create(...$params): Connection
    {
        return DriverManager::getConnection(...$params);
    }

    /**
     * 检查连接或配置是否存在
     *
     * @param string $name 连接名称
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->connections[$name]) || isset($this->config[$name]);
    }

    /**
     * 获取默认数据库连接
     *
     * @return Connection
     */
    public function getDefault(): Connection
    {
        return $this->get(DEFAULT_NAME);
    }

    /**
     * 获取指定名称的数据库连接
     *
     * @param string $name 连接名称
     *
     * @return Connection
     *
     * @throws \RuntimeException 如果连接配置未定义
     */
    public function get(string $name): Connection
    {
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        if (!isset($this->config[$name])) {
            throw new \RuntimeException(\sprintf('dbal:config of %s is not defined!', $name));
        }

        if ($this->handler !== null && !isset($this->config[$name][1])) {
            $logger = Kernel::getLog()->newLogger(
                $name,
                [$this->handler],
                [new PsrLogMessageProcessor(null, true)]
            );
            $middleware = new Middleware($logger);

            $this->config[$name][1] = (new Configuration())->setMiddlewares([$middleware]);
        }

        return $this->connections[$name] = \call_user_func_array(
            [$this, 'create'],
            $this->config[$name]
        );
    }

    /**
     * 获取日志处理器
     *
     * @return HandlerInterface|null
     */
    public function getHandler(): ?HandlerInterface
    {
        return $this->handler;
    }

    /**
     * 设置测试日志处理器
     *
     * @return TestHandler
     */
    public function setTestHandler(): TestHandler
    {
        return $this->handler = new TestHandler();
    }

    /**
     * 设置调试日志处理器（直接输出到控制台）
     *
     * @return HandlerInterface
     */
    public function setDumpHandler(): HandlerInterface
    {
        return $this->handler = new class extends AbstractProcessingHandler {
            protected function write(LogRecord $record): void
            {
                dump($record->toArray());
            }
        };
    }

    /**
     * 魔术方法：快捷获取数据库连接
     *
     * @param string|null $name 连接名称，默认为默认连接
     *
     * @return Connection
     */
    public function __invoke(?string $name = null): Connection
    {
        return $this->get($name ?? DEFAULT_NAME);
    }
}