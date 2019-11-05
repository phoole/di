<?php

declare(strict_types=1);

namespace Phoole\Tests;

use Phoole\Di\Container;
use Phoole\Config\Config;
use PHPUnit\Framework\TestCase;

class A
{
}

;

class B
{
}

;

class C
{
    public $a;

    public function __construct(A $a)
    {
        $this->a = $a;
    }
}

;

class ContainerTest extends TestCase
{
    private $obj;

    private $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->obj = new Container(
            new Config(
                [
                    'name.a' => A::class,
                    'di.service' => [
                        'a' => '${name.a}',
                        'b' => [
                            'class' => B::class,
                            'args' => [],
                        ],
                        'c' => [
                            'class' => C::class,
                            'args' => ['${#a}']
                        ],
                    ]
                ]
            )
        );
        $this->ref = new \ReflectionClass(get_class($this->obj));
    }

    protected function tearDown(): void
    {
        $this->obj = $this->ref = NULL;
        parent::tearDown();
    }

    protected function invokeMethod($methodName, array $parameters = array())
    {
        $method = $this->ref->getMethod($methodName);
        $method->setAccessible(TRUE);
        return $method->invokeArgs($this->obj, $parameters);
    }

    /**
     * @covers Phoole\Di\Container::has()
     */
    public function testHas()
    {
        $this->assertTrue($this->obj->has('a'));
        $this->assertTrue($this->obj->has('a@'));
        $this->assertTrue($this->obj->has('a@SESSION'));

        $this->assertTrue($this->obj->has('b'));
        $this->assertFalse($this->obj->has('x'));
    }

    /**
     * @covers Phoole\Di\Container::get()
     */
    public function testGet()
    {
        $this->assertTrue($this->obj->get('a') === $this->obj->get('a'));
        $this->assertFalse($this->obj->get('a') === $this->obj->get('a@'));
        $this->assertFalse($this->obj->get('a@') === $this->obj->get('a@'));
        $this->assertTrue($this->obj->get('a@S') === $this->obj->get('a@S'));

        $a = $this->obj->get('a');
        $c = $this->obj->get('c');
        $this->assertTrue($a === $c->a);
        $x = $this->obj->get('c@');
        $this->assertTrue($a === $x->a);

        $this->expectExceptionMessage('not found');
        $this->obj->get('x');

        $this->assertTrue($this->obj === $this->obj->get('container'));
    }

    /**
     * @covers \Phoole\Di\Container::_callStatic()
     */
    public function testCallStatic()
    {
        $a = Container::a();
        $this->assertTrue($a instanceof A);
    }
}