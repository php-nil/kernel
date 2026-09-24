# Nil Kernel

Nil 框架的轻量内核组件：事件驱动的 Web / CLI 双运行时、洋葱模型中间件链，
以及多实例的缓存、日志、数据库连接管理。基于 Symfony 8.1 组件、Doctrine DBAL 4.4 与 Monolog 3。

## 环境要求

- PHP >= 8.4
- APCu 扩展（可选，仅在使用 APCu 缓存适配器时需要）

## 安装

```bash
composer require php-nil/kernel
```

本包是 Nil 框架组件，需在 Nil 引导（`Nil::init()`）之后使用，`Nil\Kernel\` 按 PSR-4 自动加载。

## 启动

`Kernel::boot()` 的参数是事件收集器：接收 `EventDispatcher` 的闭包，或实现
`EventCollectorInterface` 的类名；可传多个。`boot()` 只生效一次，重复调用会被
忽略（收集器非法抛异常时不算启动成功，纠正后可重试）。

```php
use Nil\Kernel\Kernel;
use Nil\Kernel\EventCollectorInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

Kernel::boot(
    function (EventDispatcher $dispatcher) {
        $dispatcher->addListener(/* ... */);
    },
    AppEventCollector::class, // implements EventCollectorInterface
);
```

## Web 请求生命周期

```
kernel.request → kernel.router → kernel.notmatched(404)
                              ↘ kernel.matched → 中间件链/控制器 → kernel.exception
kernel.response（发送前，可替换响应）→ 发送 → kernel.terminate
CLI: kernel.console
```

事件名常量集中定义在 `App::EVENT_*`。

### 路由与控制器

```php
use Nil\Kernel\App;
use Nil\Kernel\Event\RouterEvent;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Route;

$dispatcher->addListener(App::EVENT_ROUTER, function (RouterEvent $event) {
    $event->add(
        new Route('/hello/{name}', ['_controller' => function (Request $request) {
            return new Response('hello ' . $request->attributes->get('name'));
        }]),
        'hello' // 路由名，省略时自动生成唯一名
    );
});
```

控制器接收 `Symfony\Component\HttpFoundation\Request`，返回 `Response`；
路由参数合并写入 `$request->attributes`（同名键以路由结果覆盖，request 阶段
写入的自定义属性予以保留），可通过 `$request->attributes` 获取。

### 中间件

中间件签名为 `function (Request $request, callable $next): Response`，
在 `kernel.matched` 中通过事件的 `MiddlewareHandler` 装配；`addMiddleWare($ware, true)`
插入链首（最先执行，洋葱最外层）。链按深度索引推进，同一层重复调用 `$next`
（重试/重入）每次都会完整经过后续中间件。

### 404 / 500 接管

- `kernel.notmatched`：路由未命中，`setResponse()` 可替换默认 `404 Not Found.`；
- `kernel.exception`：控制器、中间件或路由收集 / 缓存编译阶段抛异常，`setResponse()`
  可接管为 500 响应；未接管时异常继续抛出，交由 ErrorHandler 处理；
- `kernel.matched` / `kernel.request` 中 `setResponse()` 会立即短路后续处理。

### CLI

在 `kernel.console` 事件中通过 `ConsoleEvent::add(Command $command)` 注册命令。

## 内置服务

| 入口 | 说明 |
| --- | --- |
| `Kernel::cache(?string $name)` | 获取命名缓存池；默认 `PhpFilesAdapter`（`RUNTIME/cache`） |
| `Kernel::getCache()` | 缓存管理器：`set/setDefault`、`createPhpFilesAdapter`、`createPhpArrayAdapter`、`createDbalAdapter`、`createApcuAdapter` |
| `Kernel::log(?string $name)` | 获取命名 Monolog 实例 |
| `Kernel::getLog()` | 日志管理器：`withStreamLogger`、`withRotatingFileLogger`、`withTestLogger`、`setLogger`、`setDefaultHandler` |
| `Kernel::dbal(?string $name)` | 获取命名 DBAL 连接 |
| `Kernel::getDbal()` | 连接管理器：`setDefaultConfig/setConfig`（参数同 `DriverManager::getConnection`），`setTestHandler/setDumpHandler` 开启 SQL 日志 |
| `Kernel::errorHandler()` / `Kernel::path()` | 错误处理器 / 日志与缓存路径 |

## 路由缓存

- debug 模式：每次请求实时收集路由，不写缓存；
- 非 debug 模式：首次请求将匹配器原子写入 `RUNTIME/<入口名>.UrlMatcher.php`
  （临时文件 + rename，并发安全），之后直接使用编译缓存；**路由变更后删除该文件即可重新生成**。

注意：编译缓存基于 VarExporter，非 debug 模式下 `_controller` 不能使用闭包，
请使用静态方法（`[Class::class, 'method']`）、函数名或可导出对象；
PHP 8.4 起非静态方法的 `[类名, 方法]` 不被 `is_callable()` 接受。debug 模式无此限制。

## 错误与日志

`Kernel::init()` 始终将 `error_log` 指向 `RUNTIME/log/kernel_error.log`，并按模式分流：

- debug 模式：`Debug::enable()`，错误直接输出；
- 非 debug 模式：错误处理器的 PSR 日志接入 Monolog，deprecation / fatal 等记录级错误
  写入 `RUNTIME/log/default_rotating-YYYY-MM-DD.log`（channel：`kernel_error`）。

## License

MIT，详见 [LICENSE](LICENSE)。
