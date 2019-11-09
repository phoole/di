<?php

declare(strict_types=1);

namespace Phoole\Tests\Util;

use PHPUnit\Framework\TestCase;
use Phoole\Di\Util\ClassmapTrait;

class ClassMap
{
    use ClassmapTrait;
}

class ClassMap2 extends ClassMap
{
}

class ClassmapTraitTest extends TestCase
{
    private $obj;
    private $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->obj = new ClassMap();
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

    protected function getPrivateProperty($obj, $propertyName)
    {
        $ref = new \ReflectionClass(get_class($obj));
        $property = $ref->getProperty($propertyName);
        $property->setAccessible(TRUE);
        return $property->getValue($obj);
    }

    /**
     * @covers Phoole\Di\Util\ClassmapTrait::storeClass()
     */
    public function testStoreClass()
    {
        $this->invokeMethod('storeClass', [$this->obj]);
        $map = $this->getPrivateProperty($this->obj, 'classMap');
        $this->assertTrue(1 === count($map));
        $this->assertTrue(isset($map[ClassMap::class]));
    }

    /**
     * @covers Phoole\Di\Util\ClassmapTrait::hasClass()
     */
    public function testHasClass()
    {
        $obj = new ClassMap2();
        $this->invokeMethod('storeClass', [$obj]);

        $this->assertEquals(
            ClassMap2::class,
            $this->invokeMethod('hasClass', [ClassMap::class])
        );

        $this->assertEquals(
            ClassMap2::class,
            $this->invokeMethod('hasClass', [ClassMap2::class])
        );
    }

    /**
     * @covers Phoole\Di\Util\ClassmapTrait::matchClass()
     */
    public function testMatchClass()
    {
        $obj = new ClassMap2();
        $this->invokeMethod('storeClass', [$obj]);

        $this->assertTrue(
            $obj ===
            $this->invokeMethod('matchClass', [ClassMap::class])
        );
    }
}