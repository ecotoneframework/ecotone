<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Endpoint;

use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class PollingMetadataTestingSetupTest extends TestCase
{
    public function test_testing_setup_handles_one_hundred_messages_on_both_polling_metadata_types(): void
    {
        self::assertSame(100, PollingMetadata::create('endpoint')->withTestingSetup()->handledMessageLimit);
        self::assertSame(100, ExecutionPollingMetadata::createWithDefaults()->withTestingSetup()->handledMessageLimit);
        self::assertSame(100, ExecutionPollingMetadata::createWithTestingSetup()->handledMessageLimit);
    }

    public function test_testing_setup_takes_an_explicit_message_limit(): void
    {
        self::assertSame(1, PollingMetadata::create('endpoint')->withTestingSetup(handledMessageLimit: 1)->handledMessageLimit);
    }
}
