<?php

namespace Test\Ecotone\Messaging\Unit\Support;

use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class AssertTest
 * @package Ecotone\Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AssertTest extends TestCase
{
    public function test_throwing_exception_if_empty()
    {
        $this->expectException(InvalidArgumentException::class);

        Assert::notNullAndEmpty([], "");
    }

    public function test_throwing_exception_if_null()
    {
        $this->expectException(InvalidArgumentException::class);

        Assert::notNullAndEmpty(null, "");
    }

    public function test_throwing_exception_if_wrong_type_in_array_passed()
    {
        $this->expectException(InvalidArgumentException::class);

        Assert::allInstanceOfType([new \stdClass()], Assert::class);
    }

    public function test_not_throwing_exception_if_type_is_sub_class()
    {
        Assert::allInstanceOfType([new ExtendedStdClass()], \stdClass::class);

        $this->assertTrue(true);
    }

    public function test_throwing_exception_if_no_object_passed()
    {
        $this->expectException(InvalidArgumentException::class);

        Assert::isObject('test', '');
    }

    public function test_throwing_exception_if_instead_of_object_passed_array()
    {
        $this->expectException(InvalidArgumentException::class);

        Assert::isObject([new \stdClass()], '');
    }

    public function test_testing_if_subclass_of()
    {
        Assert::isSubclassOf(new \stdClass(), \stdClass::class, "");
        Assert::isSubclassOf(new ExtendedStdClass(), \stdClass::class, "");

        $this->assertTrue(true);
    }

    public function test_throwing_exception_if_wrong_subclass_passed()
    {
        $this->expectException(InvalidArgumentException::class);

        Assert::isSubclassOf(new \stdClass(), Assert::class, "");
    }

    public function test_throwing_exception_if_no_interface_passed()
    {
        $this->expectException(InvalidArgumentException::class);

        Assert::isInterface(\stdClass::class, "");
    }

    public function test_asserting_if_is_interface()
    {
        Assert::isInterface(Message::class, '');

        $this->assertTrue(true);
    }
}

class ExtendedStdClass extends \stdClass
{

}