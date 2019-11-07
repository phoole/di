<?php

declare(strict_types=1);

namespace Phoole\Tests;

use Phoole\Di\Container;
use Phoole\Config\Config;
use PHPUnit\Framework\TestCase;
use Phoole\Di\ContainerAwareTrait;
use Phoole\Di\ContainerAwareInterface;

class AA implements ContainerAwareInterface
{
    use ContainerAwareTrait;
}

class ContainerAwareTraitTest extends TestCase
{
    private $obj;

    private $di;

    protected function setUp(): void
    {
        parent::setUp();
        $this->di = new Container(new Config([]));
        $this->obj = new AA();
    }

    protected function tearDown(): void
    {
        $this->di = $this->obj = NULL;
        parent::tearDown();
    }

    /**
     * @covers \Phoole\Di\ContainerAwareTrait::setContainer()
     */
    public function testSetContainer()
    {
        $this->obj->setContainer($this->di);
        $this->assertTrue($this->di === $this->obj->getContainer());
    }

    /**
     * @covers \Phoole\Di\ContainerAwareTrait::getContainer()
     */
    public function testGetContainer()
    {
        $this->expectExceptionMessage("Container not set in");
        $this->obj->getContainer();
    }
}