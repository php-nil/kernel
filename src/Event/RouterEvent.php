<?php

namespace Nil\Kernel\Event;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class RouterEvent extends Event
{
    /** 路由收集类 */
    protected RouteCollection $routeCollection;
    /** 匿名路由的递增唯一名序列 */
    protected string $name;

    public function __construct(
        /** 请求类 */
        public readonly Request $request
    ) {
        $this->routeCollection = new RouteCollection();
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->routeCollection;
    }

    /**
     * 生成递增的唯一路由名（匿名路由使用）
     */
    protected function _makeUniqidRouteName()
    {
        $this->name ??= uniqid('un');

        return $this->name = str_increment($this->name);
    }

    /**
     * 添加路由
     */
    public function add(Route $route, ?string $name = null)
    {
        $name ??= $this->_makeUniqidRouteName();

        $this->routeCollection->add($name, $route);

        return $this;
    }

    public function addCollection(RouteCollection $collection)
    {
        $this->routeCollection->addCollection($collection);

        return $this;
    }
}
