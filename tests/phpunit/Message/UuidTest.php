<?php

namespace Messaging\Message;

use Messaging\Exception\Message\InvalidMessageHeaderException;
use PHPUnit\Framework\TestCase;

/**
 * Class UuidTest
 * @package Messaging\Message
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class UuidTest extends TestCase
{
    public function test_creating_with_correct_uuid()
    {
        $uuidAsString = 'af74c2ff-f6b2-4ee1-96c8-9f78a6fb6fbf';
        $uuid = Uuid::create($uuidAsString);

        $this->assertEquals($uuidAsString, $uuid->toString());
    }

    public function test_throwing_exception_if_wrong_uuid_passed()
    {
        $this->expectException(InvalidMessageHeaderException::class);

         Uuid::create('af74c2ff-f6b2-4ee1-96c8-9f78a6fb6fdsada');
    }
}