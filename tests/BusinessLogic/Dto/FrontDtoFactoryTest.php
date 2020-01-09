<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Logeecom\Tests\BusinessLogic\Dto;

use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\FooDto;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\NonFrontDto;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;

/**
 * Class FrontDtoFactoryTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Dto
 */
class FrontDtoFactoryTest extends BaseDtoTest
{
    public function testRegister()
    {
        TestFrontDtoFactory::register('foo', FooDto::CLASS_NAME);
        $instance = TestFrontDtoFactory::get('foo', array('foo' => 'foo_value', 'bar' => 'bar_value'));

        $this->assertNotNull($instance, 'Instance should be created.');
        $this->assertSame('foo_value', $instance->foo, 'Front DTO Factory did not create instance properly.');
        $this->assertSame('bar_value', $instance->bar, 'Front DTO Factory did not create instance properly.');
    }

    public function testGetBatch()
    {
        TestFrontDtoFactory::register('foo', FooDto::CLASS_NAME);
        $instances = TestFrontDtoFactory::getFromBatch(
            'foo',
            array(
                array('foo' => 'foo_value', 'bar' => 'bar_value'),
                array('foo' => 'foo_value2', 'bar' => 'bar_value2'),
            )
        );

        $this->assertCount(2, $instances, '2 instances should be created.');
        $this->assertSame('foo_value', $instances[0]->foo, 'Front DTO Factory did not create instance properly.');
        $this->assertSame('bar_value', $instances[0]->bar, 'Front DTO Factory did not create instance properly.');
        $this->assertSame('foo_value2', $instances[1]->foo, 'Front DTO Factory did not create instance properly.');
        $this->assertSame('bar_value2', $instances[1]->bar, 'Front DTO Factory did not create instance properly.');
    }

    /**
     * @expectedException \Packlink\BusinessLogic\DTO\Exceptions\DtoNotRegisteredException
     */
    public function testNotRegistered()
    {
        TestFrontDtoFactory::get('foo', array());
    }

    /**
     * @expectedException \Packlink\BusinessLogic\DTO\Exceptions\DtoNotRegisteredException
     */
    public function testNotRegisteredBatch()
    {
        TestFrontDtoFactory::getFromBatch('foo', array());
    }

    /**
     * @expectedException \Packlink\BusinessLogic\DTO\Exceptions\DtoFactoryRegistrationException
     */
    public function testBadClassRegistration()
    {
        TestFrontDtoFactory::register('foo', NonFrontDto::CLASS_NAME);
    }

    /**
     * @expectedException \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testGetWrongPayload()
    {
        TestFrontDtoFactory::register('foo', FooDto::CLASS_NAME);
        TestFrontDtoFactory::get('foo', array('foo' => 'something', 'whatever' => 123));
    }
}
