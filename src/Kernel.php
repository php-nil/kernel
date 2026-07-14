<?php

namespace Nil\Kernel;

use Nil\Nil;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\ErrorHandler\ErrorHandler;

/**
 * 默认配置名称
 */
const DEFAULT_NAME = '_default';

/**
 * Kernel 核心类
 * 
 * 负责应用的初始化、错误处理、日志、缓存、数据库连接等核心功能的管理。
 * 采用单例模式设计，确保全局唯一实例。
 */
final class Kernel
{
    /**
     * 是否已初始化
     */
    private static bool $initialized = false;


    /**
     * 路径配置对象
     */
    protected static ConfigPath $path;

    /**
     * 错误处理器
     */
    protected static ErrorHandler $errorHandler;

    /**
     * 缓存管理器
     */
    protected static Cache $cache;

    /**
     * 日志管理器
     */
    protected static Log $log;

    /**
     * 数据库连接管理器
     */
    protected static Dbal $dbal;

    /**
     * 应用实例
     */
    protected static App $app;


    /**
     * 初始化 Kernel
     * 
     * 负责创建必要的目录、配置错误处理、初始化应用实例。
     * 该方法只能成功执行一次，多次调用会直接返回。
     * 
     * @return void
     * 
     * @throws \RuntimeException 如果无法创建日志目录
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        $LOG_PATH = Nil::path()->RUNTIME . \DIRECTORY_SEPARATOR . 'log';
        $CACHE_PATH = Nil::path()->RUNTIME . \DIRECTORY_SEPARATOR . 'cache';

        if (!is_dir($LOG_PATH) && !mkdir($LOG_PATH, 0777, true)) {
            throw new \RuntimeException('Failed to create log directory');
        }

        $ERROR_LOG = $LOG_PATH . \DIRECTORY_SEPARATOR . 'kernel_error.log';
        ini_set('error_log', $ERROR_LOG);

        self::$path = new ConfigPath($LOG_PATH, $CACHE_PATH);

        if (Nil::debug()) {
            self::$errorHandler = Debug::enable();
        } else {
            self::$errorHandler = ErrorHandler::register();
            self::$errorHandler->setDefaultLogger(new BufferingLogger());
        }

        self::$app = new App;
        self::$initialized = true;
    }

    /**
     * 获取错误处理器
     * 
     * @return ErrorHandler
     */
    public static function errorHandler(): ErrorHandler
    {
        return self::$errorHandler;
    }

    /**
     * 获取路径配置对象
     * 
     * @return ConfigPath
     */
    public static function path(): ConfigPath
    {
        return self::$path;
    }

    /**
     * 获取缓存管理器（懒加载）
     * 
     * @return Cache
     */
    public static function getCache(): Cache
    {
        if (!isset(self::$cache)) {
            self::$cache = new Cache;
        }

        return self::$cache;
    }

    /**
     * 缓存直接操作
     * 
     * @param string|null $name 缓存名称，默认为 DEFAULT_NAME
     * @return \Psr\Cache\CacheItemPoolInterface
     */
    public static function cache(?string $name = null): \Psr\Cache\CacheItemPoolInterface
    {
        return self::getCache()->get($name);
    }

    /**
     * 获取日志管理器（懒加载）
     * 
     * @return Log
     */
    public static function getLog(): Log
    {
        if (!isset(self::$log)) {
            self::$log = new Log;
        }

        return self::$log;
    }

    /**
     * 获取指定名称的日志实例
     * 
     * @param string|null $name 日志名称，默认为 DEFAULT_NAME
     * 
     * @return \Monolog\Logger
     */
    public static function log(?string $name = null): \Monolog\Logger
    {
        return self::getLog()->withName($name);
    }

    /**
     * 获取数据库连接管理器（懒加载）
     * 
     * @return Dbal
     */
    public static function getDbal(): Dbal
    {
        if (!isset(self::$dbal)) {
            self::$dbal = new Dbal;
        }

        return self::$dbal;
    }

    /**
     * 获取指定名称的数据库连接
     * 
     * @param string|null $name 连接名称，默认为 DEFAULT_NAME
     * 
     * @return \Doctrine\DBAL\Connection
     */
    public static function dbal(?string $name = null): \Doctrine\DBAL\Connection
    {
        return self::getDbal()->get($name ?? DEFAULT_NAME);
    }

    /**
     * 启动应用-只调用一次
     * 
     * @param string|\Closure[] $events 事件收集列表
     * 
     * @return void
     */
    public static function boot(string|\Closure ...$events): void
    {
        // 初始化 Kernel
        static::init();

        $dispatcher = self::$app->getDispatcher();
        foreach ($events as $event) {
            if ($event instanceof \Closure) {
                $event();
                continue;
            }

            // 事件类
            if (class_exists($event) && $event instanceof EventCollectorInterface) {
                $event::kernelEvent($dispatcher);
            } else {
                throw new \Exception("event{$event} class not found or not implements EventCollectorInterface!");
            }
        }

        self::$app->run();
    }
}


/**
 * 路径配置类
 * 
 * 用于管理系统运行时的关键路径配置，包括日志目录和缓存目录。
 */
class ConfigPath
{
    /**
     * @param string $LOG 日志目录路径
     * @param string $CACHE 缓存目录路径
     */
    public function __construct(
        /**
         * 日志目录路径
         */
        public readonly string $LOG,
        /**
         * 缓存目录路径
         */
        public readonly string $CACHE,
    ) {
    }
}