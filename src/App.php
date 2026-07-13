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
    protected EventDispatcher $dispatcher;
    protected array $boots = [];

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
        $this->eventDispatch($event, 'kernel.console');
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
        $this->eventDispatch($event, 'kernel.request');

        // 获取 Response
        $request = $event->getRequest();
        $response = $event->hasResponse()
            ? $event->getResponse()
            : $this->handle($request);

        // 内容发送前
        $this->eventDispatch(
            new Event\ResponseEvent($request, $response),
            'kernel.response'
        );
        // 发送
        $response->prepare($request)->send();
        // 结束
        $this->eventDispatch(
            new Event\TerminateEvent($request, $response),
            'kernel.terminate'
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
            $this->eventDispatch($event, 'kernel.router');
            $collection = $event->getRouteCollection();

            $context = (new RequestContext)->fromRequest($request);
            return new UrlMatcher($collection, $context);
        } else {
            // 缓存模式
            $cacheFile = Nil::path()->getRuntimeFile('UrlMatcher.php');
            if (!file_exists($cacheFile)) {
                // 路由事件 收集路由集
                $event = new Event\RouterEvent($request);
                $this->eventDispatch($event, 'kernel.router');
                $collection = $event->getRouteCollection();

                $dumper = new CompiledUrlMatcherDumper($collection);
                file_put_contents($cacheFile, $dumper->dump());

                $context = (new RequestContext)->fromRequest($request);
                return new UrlMatcher($collection, $context);
            } else {
                $compiledRoutes = require $cacheFile;
                $context = (new RequestContext)->fromRequest($request);
                return new CompiledUrlMatcher($compiledRoutes, $context);
            }
        }
    }

    /**
     * 处理 request->Response
     */
    public function handle(Request $request): Response
    {
        // 1. 获取urlmatch
        $matcher = $this->_getUrlMatcher($request);

        // 2. 匹配路由
        try {
            $parameters = $matcher->matchRequest($request);
        } catch (\Throwable $th) {
            // 记录日志

            // 事件 未匹配成功
            $event = new Event\NotMatchedEvent($request, $th);
            $this->eventDispatch($event, 'kernel.notmatched');
            if ($event->hasResponse()) {
                return $event->getResponse();
            } else {
                return new Response('404 Not Found.', 404);
            }
        }

        // 3. 定义参数
        $request->attributes->replace($parameters);

        // 4. 事件 路由匹配成功
        $event = new Event\MatchedEvent($request);
        $this->eventDispatch($event, 'kernel.matched');
        if ($event->hasResponse()) {
            return $event->getResponse();
        }

        // 4. 中间件定义中间操作
        $handler = $event->getMiddlewareHandler();
        if (!$handler->hasMiddle()) {
            $controller = $request->attributes->get('_controller');

            if (!is_callable($controller)) {
                throw new \Exception("router of _controller must callable!");
            }

            $handler->setMiddle($controller);
        }

        // 5. 执行中间件
        return $handler->handle($request);
    }

    /**
     * 发送数据
     */
    protected function _send(Request $request, Response $response)
    {
        // 事件分发
        $event = new Event\ResponseEvent($request, $response);
        $this->eventDispatch($event, 'kernel.response');

        $response->prepare($request)->send();
    }
}
