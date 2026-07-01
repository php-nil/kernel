<?php

namespace Nil\Kernel;

use Nil\Nil;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Cache\Adapter\ApcuAdapter;

// Nil::use('symfony.cache');

/**
 * Cache 缓存管理器
 * 
 * 负责管理多个缓存适配器实例，支持文件缓存和数据库缓存。
 */
class Cache
{
    /**
     * 缓存适配器存储
     *
     * @var array<string, CacheItemPoolInterface>
     */
    protected array $store = [];

    /**
     * 获取指定名称的缓存适配器
     *
     * @param string|null $name 缓存名称，默认为默认缓存
     *
     * @return CacheItemPoolInterface
     *
     * @throws \RuntimeException 如果缓存未定义且无法创建默认缓存
     */
    public function get(?string $name = null): CacheItemPoolInterface
    {
        $key = $name ?? DEFAULT_NAME;

        if (!isset($this->store[$key])) {
            if ($key === DEFAULT_NAME) {
                return $this->store[$key] = $this->createDefaultCache();
            }

            throw new \RuntimeException(\sprintf('cache: %s is not defined!', $key));
        }

        return $this->store[$key];
    }

    /**
     * 创建默认缓存适配器
     *
     * 使用 PhpFilesAdapter 作为默认缓存，因为它性能高、依赖少、启动快。
     *
     * @return PhpFilesAdapter
     */
    protected function createDefaultCache(): PhpFilesAdapter
    {
        return new PhpFilesAdapter(DEFAULT_NAME, 0, Kernel::path()->CACHE);
    }

    /**
     * 检查缓存是否存在
     *
     * @param string $name 缓存名称
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->store[$name]);
    }

    /**
     * 设置缓存适配器
     *
     * @param string $name 缓存名称
     * @param CacheItemPoolInterface $cache 缓存适配器实例
     *
     * @return CacheItemPoolInterface
     */
    public function set(string $name, CacheItemPoolInterface $cache): CacheItemPoolInterface
    {
        return $this->store[$name] = $cache;
    }

    /**
     * 设置默认缓存适配器
     *
     * @param CacheItemPoolInterface $cache 缓存适配器实例
     *
     * @return CacheItemPoolInterface
     */
    public function setDefault(CacheItemPoolInterface $cache): CacheItemPoolInterface
    {
        return $this->store[DEFAULT_NAME] = $cache;
    }

    /**
     * 创建 PHP 文件缓存适配器
     *
     * @param string $name 缓存名称/命名空间
     *
     * @return PhpFilesAdapter
     */
    public function createPhpFilesAdapter(string $name): PhpFilesAdapter
    {
        return new PhpFilesAdapter($name, 0, Kernel::path()->CACHE);
    }

    /**
     * 创建 Doctrine DBAL 缓存适配器
     *
     * @param string $table 缓存表名，默认为空（使用默认表名）
     * @param string $namespace 缓存命名空间
     * @param string|null $dbal 数据库连接名称，默认为默认连接
     *
     * @return DoctrineDbalAdapter
     */
    public function createDbalAdapter(
        string $table = '',
        string $namespace = '',
        ?string $dbal = null
    ): DoctrineDbalAdapter {
        $options = [];
        if ($table !== '') {
            $options['db_table'] = $table;
        }

        $connection = Kernel::dbal($dbal);

        return new DoctrineDbalAdapter($connection, $namespace, 0, $options);
    }

    /**
     * 创建 PHP 数组缓存适配器
     *
     * 适用于配置缓存、路由缓存等静态数据场景，数据编译为 PHP 数组，
     * 利用 OPcache 优化读取性能，是只读模式，写入需重新生成文件。
     *
     * @param string $name 缓存名称（用于生成文件名）
     * @param CacheItemPoolInterface|null $fallbackPool 后备适配器，当缓存项未命中时使用
     *
     * @return PhpArrayAdapter
     */
    public function createPhpArrayAdapter(
        string $name,
        ?CacheItemPoolInterface $fallbackPool = null
    ): PhpArrayAdapter {
        $file = Kernel::path()->CACHE . \DIRECTORY_SEPARATOR . $name . '.php';

        if ($fallbackPool === null) {
            $fallbackPool = $this->createPhpFilesAdapter($name . '_fallback');
        }

        return new PhpArrayAdapter($file, $fallbackPool);
    }

    /**
     * 创建 APCu 缓存适配器
     *
     * 适用于单服务器部署的高性能缓存场景，数据存储在 APCu 扩展提供的共享内存中。
     * 需要安装 APCu PHP 扩展。
     *
     * @param string $namespace 缓存命名空间
     * @param int $defaultLifetime 默认过期时间（秒），0 表示永不过期
     *
     * @return ApcuAdapter
     */
    public function createApcuAdapter(string $namespace = '', int $defaultLifetime = 0): ApcuAdapter
    {
        return new ApcuAdapter($namespace, $defaultLifetime);
    }
}
