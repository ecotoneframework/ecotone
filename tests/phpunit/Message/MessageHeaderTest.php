<?php

namespace Messaging\Message;

use Messaging\Exception\Message\InvalidMessageHeaderException;
use PHPUnit\Framework\TestCase;

/**
 * Class MessageHeaderTest
 * @package Messaging\Message
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageHeaderTest extends TestCase
{
    public function test_creating_empty_headers()
    {
        $this->assertEquals([], MessageHeaders::createEmpty()->headers());
    }

    public function test_throwing_exception_if_creating_with_empty_header()
    {
        $this->expectException(InvalidMessageHeaderException::class);

        MessageHeaders::createWith([
           '' => 'value'
        ]);
    }

    public function test_throwing_exception_if_header_value_is_object()
    {
        $this->expectException(InvalidMessageHeaderException::class);

        MessageHeaders::createWith([
            "name" => new \stdClass()
        ]);
    }
}