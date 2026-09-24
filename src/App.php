<?php

namespace Nil\Kernel;

use Nil\Nil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;

class App
{
    /** 命令行事件 */
    public const string EVENT_CONSOLE = 'kernel.console';
    /** 请求开始事件 */
    public const string EVENT_REQUEST = 'kernel.request';
    /** 路由收集事件 */
    public const string EVENT_ROUTER = 'kernel.router';
    /** 路由匹配成功事件 */
    public const string EVENT_MATCHED = 'kernel.matched';
    /** 路由未匹配事件（404） */
    public const string EVENT_NOT_MATCHED = 'kernel.notmatched';
    /** 执行异常事件（500 接管） */
    public const string EVENT_EXCEPTION = 'kernel.exception';
    /** 响应输出前事件 */
    public const string EVENT_RESPONSE = 'kernel.response';
    /** 响应输出后终止事件 */
    public const string EVENT_TERMINATE = 'kernel.terminate';

    protected EventDispatcher $dispatcher;

    public function __construct()
    {
        $this->dispatcher = new EventDispatcher;
    }

    /**
     * 事件分发
     */
    protected function eventDispatch(object $event, string $eventName)
    {
        return $this->dispatcher->dispatch($event, $eventName);
    }

    /**
     * 获取事件分发
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * 运行
     */
    public function run()
    {
        if (\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            $this->runConsole();
        } else {
            $this->runWeb();
        }
    }

    /**
     * 命令行模式运行
     */
    protected function runConsole()
    {
        $event = new Event\ConsoleEvent;
        $this->eventDispatch($event, self::EVENT_CONSOLE);
        $event->getApplication()->run();
    }

    /**
     * web执行
     */
    protected function runWeb()
    {
        // 请求事件
        $event = new Event\RequestEvent;

        // 事件分发
        $this->eventDispatch($event, self::EVENT_REQUEST);

        // 获取 Response
        $request = $event->getRequest();
        $response = $event->hasResponse()
            ? $event->getResponse()
            : $this->handle($request);

        // 内容发送前（监听器可替换最终响应）
        $responseEvent = $this->eventDispatch(
            new Event\ResponseEvent($request, $response),
            self::EVENT_RESPONSE
        );
        $response = $responseEvent->getResponse();

        // 发送
        $response->prepare($request)->send();

        // 结束
        $this->eventDispatch(
            new Event\TerminateEvent($request, $response),
            self::EVENT_TERMINATE
        );
    }

    /**
     * 获取路由匹配
     */
    protected function _getUrlMatcher(Request $request)
    {
        if (Nil::debug()) {
            // 路由事件 收集路由集
            $event = new Event\RouterEvent($request);
            $this->eventDispatch($event, self::EVENT_ROUTER);
            $collection = $event->getRouteCollection();

            $context = (new RequestContext)->fromRequest($request);
            return new UrlMatcher($collection, $context);
        }

        // 缓存模式
        $cacheFile = Nil::path()->getRuntimeFile('UrlMatcher.php');
        if (!is_file($cacheFile)) {
            // 路由事件 收集路由集
            $event = new Event\RouterEvent($request);
            $this->eventDispatch($event, self::EVENT_ROUTER);
            $collection = $event->getRouteCollection();

            // 原子写入：先写临时文件再重命名，避免并发请求 require 到半成品缓存
            $dumper = new CompiledUrlMatcherDumper($collection);
            $tmpFile = $cacheFile . '.' . bin2hex(random_bytes(4)) . '.tmp';
            if (false === @file_put_contents($tmpFile, $dumper->dump(), \LOCK_EX)) {
                @unlink($tmpFile);
                throw new \RuntimeException(\sprintf('Failed to write route cache "%s".', $cacheFile));
            }
            if (!@rename($tmpFile, $cacheFile)) {
                @unlink($tmpFile);
                // 并发场景下可能已由其他请求生成，目标文件可用即视为成功
                if (!is_file($cacheFile)) {
                    throw new \RuntimeException(\sprintf('Failed to finalize route cache "%s".', $cacheFile));
                }
            }

            $context = (new RequestContext)->fromRequest($request);
            return new UrlMatcher($collection, $context);
        }

        $compiledRoutes = require $cacheFile;
        $context = (new RequestContext)->fromRequest($request);
        return new CompiledUrlMatcher($compiledRoutes, $context);
    }

    /**
     * 处理 request->Response
     */
    public function handle(Request $request): Response
    {
        // 1. 获取urlmatch（路由收集 / 缓存编译失败同样可由 kernel.exception 接管）
        try {
            $matcher = $this->_getUrlMatcher($request);
        } catch (\Throwable $e) {
            return $this->handleException($request, $e);
        }

        // 2. 匹配路由
        try {
            $parameters = $matcher->matchRequest($request);
        } catch (\Throwable $th) {
            // 事件 未匹配成功
            $event = new Event\NotMatchedEvent($request, $th);
            $this->eventDispatch($event, self::EVENT_NOT_MATCHED);
            if ($event->hasResponse()) {
                return $event->getResponse();
            }

            return new Response('404 Not Found.', 404);
        }

        // 3. 匹配成功后的处理（控制器/中间件异常可由 kernel.exception 接管）
        try {
            // 合并路由参数：同名键以路由结果覆盖，同时保留 request 阶段写入的自定义属性
            $request->attributes->add($parameters);

            // 4. 事件 路由匹配成功
            $event = new Event\MatchedEvent($request);
            $this->eventDispatch($event, self::EVENT_MATCHED);
            if ($event->hasResponse()) {
                return $event->getResponse();
            }

            // 5. 中间件定义中间操作
            $handler = $event->getMiddlewareHandler();
            if (!$handler->hasMiddle()) {
                $controller = $request->attributes->get('_controller');

                if (!is_callable($controller)) {
                    throw new \RuntimeException(\sprintf(
                        'The "_controller" of route "%s" must be callable.',
                        $request->attributes->get('_route') ?? ''
                    ));
                }

                $handler->setMiddle($controller);
            }

            // 6. 执行中间件
            return $handler->handle($request);
        } catch (\Throwable $e) {
            return $this->handleException($request, $e);
        }
    }

    /**
     * 分发 kernel.exception 事件
     *
     * 监听器调用 setResponse() 接管时返回其 Response；未接管时异常继续抛出。
     */
    protected function handleException(Request $request, \Throwable $e): Response
    {
        $event = new Event\ExceptionEvent($request, $e);
        $this->eventDispatch($event, self::EVENT_EXCEPTION);

        if ($event->hasResponse()) {
            return $event->getResponse();
        }

        throw $e;
    }
}
