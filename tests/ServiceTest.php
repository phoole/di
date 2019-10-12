<?php

declare(strict_types=1);

namespace Phoole\Tests\Service;

use Phoole\Di\Container;
use Phoole\Config\Config;
use Phoole\Di\Service;
use PHPUnit\Framework\TestCase;

class A {};
class B {};
class C {
    public $a;
    public function __construct(A $a) {
        $this->a = $a;
    }
};

class ServiceTest extends TestCase
{
    private $obj;

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
        $this->obj = null;
        Service::setContainer();
        parent::tearDown();
    }

    /**
     * @covers Phoole\Di\Service::get()
     */
    public function testGet()
    {
        Service::setContainer($this->obj);
        $this->assertTrue(Service::get('a') === $this->obj->get('a'));
        $this->assertFalse(Service::get('a@') === $this->obj->get('a'));
        $this->assertFalse(Service::get('a@') === $this->obj->get('a@'));
        $this->assertFalse(Service::get('a@S') === $this->obj->get('a'));
        $this->assertTrue(Service::get('a@S') === $this->obj->get('a@S'));
    }
}