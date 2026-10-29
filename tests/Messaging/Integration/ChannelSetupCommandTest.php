<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Integration;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Gateway\ConsoleCommandRunner;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ChannelSetupCommand with non-managed channels.
 *
 * licence Apache-2.0
 * @internal
 */
final class ChannelSetupCommandTest extends TestCase
{
    private const IN_MEMORY_CHANNEL = 'in_memory_test_channel';

    public function test_listing_non_managed_channel_with_correct_status(): void
    {
        $ecotone = $this->bootstrapEcotone();

        $runner = $ecotone->getGateway(ConsoleCommandRunner::class);
        $result = $runner->execute('ecotone:migration:channel:setup', []);

        self::assertNotNull($result);
        $rows = $result->getRows();

        // Find the in-memory channel in the results
        $inMemoryChannelRow = null;
        foreach ($rows as $row) {
            if ($row[0] === self::IN_MEMORY_CHANNEL) {
                $inMemoryChannelRow = $row;
                break;
            }
        }

        self::assertNotNull($inMemoryChannelRow, 'In-memory channel should be listed');
        self::assertEquals([self::IN_MEMORY_CHANNEL, 'Not managed by channel migration'], $inMemoryChannelRow);
    }

    public function test_throwing_exception_when_trying_to_setup_non_managed_channel(): void
    {
        $ecotone = $this->bootstrapEcotone();

        $runner = $ecotone->getGateway(ConsoleCommandRunner::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Channel 'in_memory_test_channel' is not managed by the migration system");

        $runner->execute('ecotone:migration:channel:setup', [
            'channel' => [self::IN_MEMORY_CHANNEL],
            'initialize' => true,
        ]);
    }

    public function test_throwing_exception_when_trying_to_delete_non_managed_channel(): void
    {
        $ecotone = $this->bootstrapEcotone();

        $runner = $ecotone->getGateway(ConsoleCommandRunner::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Channel 'in_memory_test_channel' is not managed by the migration system");

        $runner->execute('ecotone:migration:channel:delete', [
            'channel' => [self::IN_MEMORY_CHANNEL],
            'force' => true,
        ]);
    }

    public function test_channel_setup_respects_string_false_for_initialize(): void
    {
        $ecotone = $this->bootstrapEcotone();

        $runner = $ecotone->getGateway(ConsoleCommandRunner::class);

        // Pass "false" as a string for initialize parameter
        $result = $runner->execute('ecotone:migration:channel:setup', ['initialize' => 'false']);

        // Should show status, not initialize
        self::assertNotNull($result);
        self::assertEquals(['Channel', 'Initialized'], $result->getColumnHeaders());
    }

    public function test_channel_delete_respects_string_false_for_force(): void
    {
        $ecotone = $this->bootstrapEcotone();

        $runner = $ecotone->getGateway(ConsoleCommandRunner::class);

        // Pass "false" as a string for force parameter
        $result = $runner->execute('ecotone:migration:channel:delete', ['force' => 'false']);

        // Should show warning, not delete
        self::assertNotNull($result);
        self::assertEquals(['Channel', 'Warning'], $result->getColumnHeaders());
    }

    private function bootstrapEcotone(): \Ecotone\Lite\Test\FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            containerOrAvailableServices: [],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel(self::IN_MEMORY_CHANNEL),
                ]),
            pathToRootCatalog: __DIR__ . '/../../',
        );
    }
}
