<?php

declare(strict_types=1);

namespace Phoole\Tests\Util;

use Phoole\Di\Container;
use Phoole\Config\Config;
use PHPUnit\Framework\TestCase;
use Phoole\Di\Util\AutowiringTrait;

class Xclass
{
    use AutowiringTrait {
        storeClass as public;
        hasClass as public;
        matchClass as public;
        getObjectByClass as public;
        isRequiredClass as public;
        isTypeMatched as public;
        matchArguments as public;
    }
}

class Autowiring extends Xclass
{
    public $autoLoad = FALSE;
}

class AutowiringTraitTest extends TestCase
{
    private $obj;

    private $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $container = new Container(new Config([]));

        $this->obj = new Autowiring();
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
     * @covers Phoole\Di\Util\AutowiringTrait::getObjectByClass()
     */
    public function testGetObjectByClass()
    {
        include_once 'ClassmapTraitTest.php';

        $obj = new ClassMap2();
        $this->obj->storeClass($obj);

        // found in map
        $obj2 = $this->obj->getObjectByClass(ClassMap::class);
        $this->assertTrue($obj === $obj2);

        // autoload
        $this->obj->autoLoad = TRUE;
        $obj3 = $this->obj->getObjectByClass(Xclass::class);
        $this->assertTrue($obj3 instanceof Xclass);

        // turn off autoload
        $this->obj->autoLoad = FALSE;
        $this->expectExceptionMessage(Xclass::class);
        $obj4 = $this->obj->getObjectByClass(Xclass::class);
    }

    /**
     * @covers Phoole\Di\Util\AutowiringTrait::isRequiredClass()
     */
    public function testIsRequiredClass()
    {
        $func = function(?Autowiring $auto = NULL, int $i = 1, string $str = 'test') {
        };
        $parameters = (new \ReflectionFunction($func))->getParameters();
        $this->assertTrue(3 === count($parameters));

        $this->assertFalse($this->obj->isRequiredClass($parameters[0], []));
        $this->assertTrue($this->obj->isRequiredClass($parameters[0], [1]));
    }

    /**
     * @covers Phoole\Di\Util\AutowiringTrait::isTypeMatched()
     */
    public function testIsTypeMatched()
    {
        $func = function(?Autowiring $auto, int $i = 1, string $str = 'test') {
        };
        $parameters = (new \ReflectionFunction($func))->getParameters();
        $this->assertTrue(3 === count($parameters));

        $this->assertTrue($this->obj->isTypeMatched($parameters[0]->getClass(), [new Autowiring(), 1, 'wow']));
        $this->assertFalse($this->obj->isTypeMatched($parameters[0]->getClass(), [1, 'wow']));
        $this->assertTrue($this->obj->isTypeMatched($parameters[1]->getClass(), [1, 'wow']));
    }

    /**
     * @covers Phoole\Di\Util\AutowiringTrait::matchArguments()
     */
    public function testMatchArguments()
    {
        $this->obj->autoLoad = TRUE;

        // case one
        $func = function(?Autowiring $auto, int $i = 1, string $str = 'test') {
        };
        $parameters = (new \ReflectionFunction($func))->getParameters();

        $res = $this->obj->matchArguments([2, 'wow'], $parameters);
        $this->assertTrue($res[0] instanceof Autowiring);
        $this->assertTrue(2 === $res[1]);
        $this->assertTrue('wow' === $res[2]);

        $res = $this->obj->matchArguments([2], $parameters);
        $this->assertTrue($res[0] instanceof Autowiring);
        $this->assertTrue(2 === count($res));

        // case two
        $func = function(Autowiring ...$auto) {
        };
        $parameters = (new \ReflectionFunction($func))->getParameters();
        $res = $this->obj->matchArguments([], $parameters);
        $this->assertTrue(0 === count($res));
        $res = $this->obj->matchArguments([new Autowiring(), new Autowiring()], $parameters);
        $this->assertTrue(2 === count($res));
        $this->assertTrue($res[1] instanceof Autowiring);

        // case three
        $func = function($test) {
        };
        $parameters = (new \ReflectionFunction($func))->getParameters();
        $res = $this->obj->matchArguments([], $parameters);
        $this->assertTrue(0 === count($res));
    }
}