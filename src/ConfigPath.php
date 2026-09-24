<?php

namespace Nil\Kernel;

/**
 * 路径配置类
 *
 * 用于管理系统运行时的关键路径配置，包括日志目录和缓存目录。
 */
readonly class ConfigPath
{
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
