<?php

namespace Nil\Kernel\Tests;

use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    public function testKernelExists()
    {
        $this->assertTrue(class_exists('Nil\Kernel\Kernel'));
    }
}
