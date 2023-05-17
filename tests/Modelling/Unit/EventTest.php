<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\Event;

use function PHPUnit\Framework\assertEquals;

use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
final class EventTest extends TestCase
{
    public function test_constructor_wont_overwrite_already_set_metadata(): void
    {
        $metadata = [
            MessageHeaders::MESSAGE_ID => 'uuid',
            MessageHeaders::TIMESTAMP => 123,
        ];

        assertEquals($metadata, (Event::createWithType('type', [], $metadata))->getMetadata());
        assertEquals($metadata, (Event::create(new stdClass(), $metadata))->getMetadata());
    }
}
