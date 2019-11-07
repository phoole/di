<?php

declare(strict_types=1);

namespace Phoole\Tests\Util;

use PHPUnit\Framework\TestCase;
use Phoole\Di\Util\FactoryTrait;

class Factory
{
    use FactoryTrait;
}

class ClassNoContructor
{
    public function get()
    {
        return 'a';
    }
}

class ClassHasConstructor
{
    public function __construct(string $str)
    {
    }
}

class FactoryTraitTest extends TestCase
{
    private $obj;

    private $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->obj = new Factory();
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
     * @covers Phoole\Di\Util\FactoryTrait::fixDefinition()
     */
    public function testFixDefinition()
    {
        // convert classname
        $this->assertEquals(
            ['class' => 'Test', 'args' => []],
            $this->invokeMethod('fixDefinition', ['Test'])
        );

        // passthru
        $data = ['class' => 'Test', 'args' => ['bingo']];
        $this->assertEquals(
            $data,
            $this->invokeMethod('fixDefinition', [$data])
        );

        // callable
        $closure = function() {
            return new Factory();
        };

        $this->assertEquals(
            ['class' => $closure, 'args' => []],
            $this->invokeMethod('fixDefinition', [$closure])
        );
    }

    /**
     * @covers Phoole\Di\Util\FactoryTrait::constructObject()
     */
    public function testConstructObject()
    {
        $noc = __NAMESPACE__ . '\\ClassNoContructor';
        $arg1 = [];
        $has = __NAMESPACE__ . '\\ClassHasConstructor';
        $arg2 = ['bingo'];

        $this->assertTrue(is_object($this->invokeMethod('constructObject', [$noc, $arg1])));
        $this->assertTrue(is_object($this->invokeMethod('constructObject', [$has, $arg2])));

        $this->expectExceptionMessage('Too few arguments');
        $this->assertTrue(is_object($this->invokeMethod('constructObject', [$has])));
    }

    /**
     * @covers Phoole\Di\Util\FactoryTrait::executeCallable()
     */
    public function testExecuteCallable()
    {
        // callable
        $a = function() { return 'a'; };
        $this->assertEquals('a', $this->invokeMethod('executeCallable', [$a, []]));

        // object
        $a = new Factory();
        $this->assertTrue($a === $this->invokeMethod('executeCallable', [$a, []]));

        // array callable
        $a = [new ClassNoContructor(), 'get'];
        $this->assertEquals('a', $this->invokeMethod('executeCallable', [$a, []]));

        // exception
        $this->expectExceptionMessage('not a callable');
        $this->assertEquals('a', $this->invokeMethod('executeCallable', ['bingo', []]));
    }

    /**
     * @covers Phoole\Di\Util\FactoryTrait::fixMethod()
     */
    public function testFixMethod()
    {
        // object's method
        $obj = new ClassNoContructor();
        $line = 'get';
        list($callable, $args) = $this->invokeMethod('fixMethod', [[$line], $obj]);
        $this->assertTrue(is_object($callable[0]));
        $this->assertTrue('get' === $callable[1]);
        $this->assertTrue([] === $args);

        // callable
        $a = function($a) { echo $a; };
        $line = [$a, ['test']];
        list($callable, $args) = $this->invokeMethod('fixMethod', [$line, $obj]);
        $this->assertTrue($a === $callable);
        $this->assertTrue(['test'] === $args);
    }

    /**
     * @covers Phoole\Di\Util\FactoryTrait::aroundConstruct()
     */
    public function testAroundConstruct()
    {
        $a = function($x) { return $x; };
        $def = [
            'after' => [
                'get',
                [$a, 'test'],
            ]
        ];
        $this->invokeMethod('aroundConstruct', [$def, 'after', new ClassNoContructor()]);
        $this->assertTrue(TRUE);
    }

    /**
     * @covers Phoole\Di\Util\FactoryTrait::fabricate()
     */
    public function testFabricate()
    {
        // passthru object
        $a = new ClassNoContructor();
        $x = ['class' => $a, 'args' => []];
        $this->assertTrue($a === $this->invokeMethod('fabricate', [$x]));

        // callable
        $b = ['class' => function() {
            return new ClassNoContructor();
        }, 'args' => []
        ];
        $this->assertTrue($this->invokeMethod('fabricate', [$b]) instanceof ClassNoContructor);

        // string classname
        $x = __NAMESPACE__ . '\\ClassNoContructor';
        $c = ['class' => $x, 'args' => []];
        $this->assertEquals(
            $x,
            get_class($this->invokeMethod('fabricate', [$c]))
        );
    }
}