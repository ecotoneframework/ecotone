<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit;

use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\InvalidMessageHeaderException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageHeadersWithoutTimestampTest extends TestCase
{
    public function test_headers_without_timestamp_are_refused_and_name_the_builder(): void
    {
        $this->expectException(InvalidMessageHeaderException::class);
        $this->expectExceptionMessage('Message headers have no timestamp. Build messages with MessageBuilder, the container service that stamps them from EcotoneClockInterface; in tests use Ecotone\Test\StaticMessageBuilder.');

        MessageHeaders::create(['token' => 'abc']);
    }

    public function test_zero_timestamp_is_refused_as_missing(): void
    {
        $this->expectException(InvalidMessageHeaderException::class);
        $this->expectExceptionMessage('Message headers have no timestamp.');

        MessageHeaders::create([MessageHeaders::TIMESTAMP => 0]);
    }

    public function test_timestamp_given_as_string_is_kept_as_integer(): void
    {
        $this->assertSame(1700000000, MessageHeaders::create([MessageHeaders::TIMESTAMP => '1700000000'])->getTimestamp());
    }
}
