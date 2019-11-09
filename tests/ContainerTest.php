<?php

declare(strict_types=1);

namespace Phoole\Tests;

use Phoole\Di\Container;
use Phoole\Config\Config;
use PHPUnit\Framework\TestCase;
use Phoole\Di\ContainerAwareTrait;
use Phoole\Di\ContainerAwareInterface;

class A implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public $bingo = '_bingo_';
}

class B
{
    public function bingo($text)
    {
        echo $text;
    }
}

class C
{
    public $a;

    public function __construct(A $a)
    {
        $this->a = $a;
    }
}

class D
{
}

class DD extends D
{
}

class E
{
}

class X
{
    public $d;

    public $e;

    public function __construct(D $d, E $e)
    {
        $this->d = $d;
        $this->e = $e;
    }
}

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
                            //'args' => ['${#a}']
                        ],
                        'x' => X::class,
                        'd' => DD::class,
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
        $this->assertFalse($this->obj->has('Y'));
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
        $this->obj->get('y');

        $this->assertTrue($this->obj === $this->obj->get('container'));
    }

    /**
     * @covers Phoole\Di\Container::get()
     */
    public function testGet2()
    {
        $x = $this->obj->get('x');
        $this->assertTrue($x instanceof X);
        $this->assertTrue($x->d instanceof DD);
    }

    /**
     * test di.before & di.after
     * @covers Phoole\Di\Container::get()
     */
    public function testGet3()
    {
        $container = new Container(new Config([
            'di.before' => [
                function($def) {
                    echo "CLASS ". $def['class'];
                },
                [[new B(), 'bingo'], '_wow'],
            ],
            'di.service' => [
            ],
            'di.after' => [
                'setContainer',
                function($obj) {
                    echo $obj->bingo;
                },
                [[new B(), 'bingo'], 'wow_'],
            ],
        ]));

        // before
        $this->expectOutputString("CLASS ".A::class .'_wow_bingo_wow_');
        $a = Container::create(A::class);
        // after
        $this->assertTrue($a->getContainer() instanceof Container);
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