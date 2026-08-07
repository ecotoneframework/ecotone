<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit;

use Ecotone\Messaging\BatchMessage;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * licence Apache-2.0
 * @internal
 */
final class BatchMessageTest extends TestCase
{
    public function test_appending_payloads_with_and_without_headers(): void
    {
        $orderPlaced = new stdClass();

        $batch = BatchMessage::constructEmpty()
            ->append('first payload')
            ->append($orderPlaced, ['priority' => 5]);

        $this->assertSame(
            [
                ['payload' => 'first payload', 'headers' => []],
                ['payload' => $orderPlaced, 'headers' => ['priority' => 5]],
            ],
            $batch->getEntries(),
        );
    }

    public function test_counting_appended_messages(): void
    {
        $this->assertCount(0, BatchMessage::constructEmpty());
        $this->assertCount(2, BatchMessage::constructEmpty()->append('one')->append('two'));
    }

    public function test_appending_returns_new_instance_keeping_original_untouched(): void
    {
        $original = BatchMessage::constructEmpty()->append('first payload');

        $extended = $original->append('second payload');

        $this->assertCount(1, $original);
        $this->assertCount(2, $extended);
    }

    public function test_constructing_from_entries(): void
    {
        $batch = BatchMessage::fromEntries([
            ['payload' => 'first payload', 'headers' => []],
            ['payload' => 'second payload', 'headers' => ['priority' => 5]],
        ]);

        $this->assertSame(
            [
                ['payload' => 'first payload', 'headers' => []],
                ['payload' => 'second payload', 'headers' => ['priority' => 5]],
            ],
            $batch->getEntries(),
        );
    }
}
