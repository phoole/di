<?php

declare(strict_types=1);

namespace Phoole\Tests\Util;

use Phoole\Di\Container;
use Phoole\Config\Config;
use Phoole\Di\Util\ContainerTrait;
use PHPUnit\Framework\TestCase;

class A {};
class B {};
class C {
    public $a;
    public function __construct(A $a) {
        $this->a = $a;
    }
};

class ContainerTraitTest extends TestCase
{
    private $obj;
    private $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->obj = new Container(new Config([
            'name.a' => A::class,
            'di.service' => [
                'a' => '${name.a}',
                'b' => [
                    'class' => B::class,
                    'args'  => [],
                ],
                'c' => [
                    'class' => C::class,
                    'args'  => ['${#a}']
                ],
            ]
        ]));
        $this->ref = new \ReflectionClass(get_class($this->obj));
    }

    protected function tearDown(): void
    {
        $this->obj = $this->ref = null;
        parent::tearDown();
    }

    protected function invokeMethod($methodName, array $parameters = array())
    {
        $method = $this->ref->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->obj, $parameters);
    }

    /**
     * @covers Phoole\Di\Util\ContainerTrait::hasDefinition()
     */
    public function testHasDefinition()
    {
        $this->assertTrue($this->invokeMethod('hasDefinition', ['a']));
        $this->assertFalse($this->invokeMethod('hasDefinition', ['x']));
    }

    /**
     * @covers Phoole\Di\Util\ContainerTrait::getRawId()
     */
    public function testGetRawId()
    {
        $g = 'di.service.cache';
        $this->assertEquals($g, $this->invokeMethod('getRawId', ['cache@']));
        $this->assertEquals($g, $this->invokeMethod('getRawId', ['cache@SS']));
    }

    /**
     * @covers Phoole\Di\Util\ContainerTrait::newInstance()
     */
    public function testNewInstance()
    {
        $a = $this->invokeMethod('newInstance', ['a@SESS']);
        $b = $this->invokeMethod('newInstance', ['a']);
        $this->assertTrue(is_object($a));
        $this->assertTrue(is_object($b));
        $this->assertFalse($a === $b);
    }

    /**
     * @covers Phoole\Di\Util\ContainerTrait::getInstance()
     */
    public function testGetInstance()
    {
        // get shared object
        $a = $this->invokeMethod('getInstance', ['a']);
        $b = $this->invokeMethod('getInstance', ['a']);
        $this->assertTrue(is_object($a));
        $this->assertTrue($a === $b);

        // get new object
        $x = $this->invokeMethod('getInstance', ['a@']);
        $y = $this->invokeMethod('getInstance', ['a@']);
        $this->assertFalse($x === $y);

        // same scope object
        $x = $this->invokeMethod('getInstance', ['a@XX']);
        $y = $this->invokeMethod('getInstance', ['a@XX']);
        $this->assertTrue($x === $y);

        // using shared object
        $c = $this->invokeMethod('getInstance', ['c']);
        $this->assertTrue(is_object($c));
        $this->assertTrue($a === $c->a);
    }

    /**
     * @covers Phoole\Di\Util\ContainerTrait::getInstance()
     */
    public function testGetInstance2()
    {
        // check predefined objects
        $a = $this->invokeMethod('getInstance', ['config']);
        $this->assertTrue($a instanceof Config);

        $b = $this->invokeMethod('getInstance', ['container']);
        $this->assertTrue($b instanceof Container);
    }

        /**
     * @covers Phoole\Di\Util\ContainerTrait::reloadAll()
     */
    public function testReloadAll()
    {
        $a = $this->invokeMethod('getInstance', ['a']);
        $b = $this->invokeMethod('getInstance', ['a']);
        $this->assertTrue($a === $b);

        $this->invokeMethod('reloadAll');
        $c = $this->invokeMethod('getInstance', ['a']);
        $this->assertFalse($a === $c);
    }
}