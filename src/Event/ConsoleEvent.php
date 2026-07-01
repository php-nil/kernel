<?php

namespace Nil\Kernel\Event;

use Nil\Nil;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

Nil::use('symfony.console');

/**
 * ConsoleEvent 控制台事件管理器
 * 
 * 负责管理 Symfony Console 应用实例，提供命令注册功能。
 */
class ConsoleEvent
{
    /**
     * Symfony Console 应用实例
     */
    protected Application $application;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->application = new Application();
    }

    /**
     * 获取 Console 应用实例
     *
     * @return Application
     */
    public function getApplication(): Application
    {
        return $this->application;
    }

    /**
     * 添加命令到控制台应用
     *
     * @param Command $command 命令实例
     *
     * @return Command|null
     */
    public function add(Command $command): ?Command
    {
        return $this->application->addCommand($command);
    }
}