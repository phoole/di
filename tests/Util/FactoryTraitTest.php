<?php

declare(strict_types=1);

namespace Phoole\Tests\Util;

use Phoole\Di\Util\FactoryTrait;
use PHPUnit\Framework\TestCase;

class Factory
{
    use FactoryTrait;
}

class ClassNoContructor
{
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
        $data = [
            'class' => 'Test',
            'args'  => ['bingo']
        ];
        $this->assertEquals(
            $data,
            $this->invokeMethod('fixDefinition', [$data])
        );
    }

    /**
     * @covers Phoole\Di\Util\FactoryTrait::constructObject()
     */
    public function testConstructObject()
    {
        $def1 = [
            'class' => __NAMESPACE__ . '\\ClassNoContructor',
            'args'  => []
        ];
        $def2 =  [
            'class' => __NAMESPACE__ . '\\ClassHasConstructor',
            'args'  => ['bingo']
        ];

        $this->assertTrue(is_object($this->invokeMethod('constructObject', [$def1])));
        $this->assertTrue(is_object($this->invokeMethod('constructObject', [$def2])));

        $def2['args'] = [];
        $this->expectExceptionMessage('Too few arguments');
        $this->assertTrue(is_object($this->invokeMethod('constructObject', [$def2])));

    }

    /**
     * @covers Phoole\Di\Util\FactoryTrait::fabricate()
     */
    public function testFabricate()
    {
        // object
        $a = new ClassNoContructor();
        $this->assertTrue($a === $this->invokeMethod('fabricate', [$a]));

        // callable
        $b = function() {
            return new ClassNoContructor();
        };
        $c = __NAMESPACE__ . '\\ClassNoContructor';
        $this->assertEquals(
            get_class($this->invokeMethod('fabricate', [$b])),
            $c
        );

        // string classname
        $this->assertEquals(
            get_class($this->invokeMethod('fabricate', [$c])),
            $c
        );
    }
}