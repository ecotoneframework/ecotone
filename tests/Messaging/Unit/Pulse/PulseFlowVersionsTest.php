<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Pulse;

use DateTimeImmutable;
use DateTimeZone;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\ExtensionObject\DistributedServiceMap;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Messaging\Flow\FlowVersionsConfiguration;
use Ecotone\Api\Messaging\Flow\MessageFlowGraph;
use Ecotone\Api\Pulse\PulseConfiguration;
use Ecotone\Api\Scheduling\TimeSpan;
use Ecotone\Test\LicenceTesting;
use Exception;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 *
 * @internal
 */
final class PulseFlowVersionsTest extends TestCase
{
    private string $location;
    private array $channels;

    protected function setUp(): void
    {
        $this->channels = [SimpleMessageChannelBuilder::createQueueChannel('order_channel'), SimpleMessageChannelBuilder::createQueueChannel('pulse_channel')];
        $this->location = sys_get_temp_dir() . '/ecotone-pulse-flow-versions-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (! is_dir($this->location)) {
            return;
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->location, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->location);
    }

    public function test_pulse_is_answered_with_the_stored_flow_versions_from_the_earliest_to_the_latest(): void
    {
        $pulse = $this->pulseReplies();
        $orderService = $this->orderService([FlowVersionsConfiguration::createWithLocation($this->location)]);
        $pulseService = $this->pulseService($pulse);
        $orderService->runConsoleCommand('ecotone:flow:store', ['label' => '1.0.0']);
        $orderService->advanceTimeBy(new TimeSpan(hours: 1));
        $orderService->runConsoleCommand('ecotone:flow:store', ['label' => '1.1.0']);

        $this->ask($pulseService, $orderService, 'ecotone.pulse.flow_versions.list', '{}', 'query-1');

        self::assertSame(
            [
                'queryId' => 'query-1',
                'configured' => true,
                'versions' => [
                    ['label' => '1.0.0', 'storedAt' => '2026-10-20T12:00:00+00:00', 'nodes' => 4, 'edges' => 2],
                    ['label' => '1.1.0', 'storedAt' => '2026-10-20T13:00:00+00:00', 'nodes' => 4, 'edges' => 2],
                ],
            ],
            json_decode($pulse->replies[0], true, flags: JSON_THROW_ON_ERROR),
        );
    }

    public function test_pulse_is_answered_with_the_graph_of_one_stored_flow_version(): void
    {
        $pulse = $this->pulseReplies();
        $orderService = $this->orderService([FlowVersionsConfiguration::createWithLocation($this->location)]);
        $pulseService = $this->pulseService($pulse);
        $orderService->runConsoleCommand('ecotone:flow:store', ['label' => '1.0.0']);

        $this->ask($pulseService, $orderService, 'ecotone.pulse.flow_versions.version', '{"label": "1.0.0"}', 'query-2');

        $reply = json_decode($pulse->replies[0], true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(['queryId', 'label', 'storedAt', 'graph'], array_keys($reply));
        self::assertSame('query-2', $reply['queryId']);
        self::assertSame('1.0.0', $reply['label']);
        self::assertSame('2026-10-20T12:00:00+00:00', $reply['storedAt']);
        self::assertFalse($orderService->getMessageFlowGraph()->compareTo(MessageFlowGraph::fromArray($reply['graph']))->hasChanges());
    }

    public function test_a_service_without_a_location_answers_the_list_as_not_configured(): void
    {
        $pulse = $this->pulseReplies();
        $orderService = $this->orderService([]);
        $pulseService = $this->pulseService($pulse);

        $this->ask($pulseService, $orderService, 'ecotone.pulse.flow_versions.list', '{}', 'query-3');

        self::assertSame(['queryId' => 'query-3', 'configured' => false, 'versions' => []], json_decode($pulse->replies[0], true, flags: JSON_THROW_ON_ERROR));
    }

    public function test_a_service_without_a_location_refuses_a_version_query_naming_the_configuration(): void
    {
        $pulse = $this->pulseReplies();
        $orderService = $this->orderService([]);
        $pulseService = $this->pulseService($pulse);

        $this->ask($pulseService, $orderService, 'ecotone.pulse.flow_versions.version', '{"label": "1.0.0"}', 'query-4');

        $reply = json_decode($pulse->replies[0], true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('ecotone.pulse.flow_versions.version', $reply['requestKind']);
        self::assertSame('query-4', $reply['queryId']);
        self::assertSame("Service order_service was asked by Pulse for a flow version, but it keeps none, because no location is configured there. Return FlowVersionsConfiguration::createWithLocation(__DIR__ . '/../flow-versions') from a #[ServiceContext] method of order_service, or stop sending flow version queries to this service.", $reply['message']);
    }

    public function test_a_version_query_for_a_label_that_was_never_stored_is_refused_listing_the_stored_ones(): void
    {
        $pulse = $this->pulseReplies();
        $orderService = $this->orderService([FlowVersionsConfiguration::createWithLocation($this->location)]);
        $pulseService = $this->pulseService($pulse);
        $orderService->runConsoleCommand('ecotone:flow:store', ['label' => '1.0.0']);

        $this->ask($pulseService, $orderService, 'ecotone.pulse.flow_versions.version', '{"label": "0.9.0"}', 'query-5');

        self::assertSame(
            "Flow version `0.9.0` is not stored in {$this->location}. Stored versions: `1.0.0`. Pass one of them, or store the current flow first with `ecotone:flow:store 0.9.0`.",
            json_decode($pulse->replies[0], true, flags: JSON_THROW_ON_ERROR)['message'],
        );
    }

    public function test_a_version_query_without_a_label_is_refused_with_an_example(): void
    {
        $pulse = $this->pulseReplies();
        $orderService = $this->orderService([FlowVersionsConfiguration::createWithLocation($this->location)]);
        $pulseService = $this->pulseService($pulse);

        $this->ask($pulseService, $orderService, 'ecotone.pulse.flow_versions.version', '{}', 'query-6');

        self::assertSame(
            'A Pulse flow version query needs a `label`, but `{}` was sent. Send {"label": "1.4.0"}, taking the label from the flow version list of the service.',
            json_decode($pulse->replies[0], true, flags: JSON_THROW_ON_ERROR)['message'],
        );
    }

    public function test_a_flow_version_query_without_a_query_id_is_refused_naming_the_header(): void
    {
        $pulse = $this->pulseReplies();
        $orderService = $this->orderService([FlowVersionsConfiguration::createWithLocation($this->location)]);
        $pulseService = $this->pulseService($pulse);

        $pulseService->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.flow_versions.list', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.reply_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
        $pulseService->run('pulse_channel', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertSame(
            "Service order_service was asked by Pulse for flow versions without the query id in header `ecotone_pulse_query_id`, so Pulse could not match the result to its question. Send the request with metadata: ['ecotone_pulse_query_id' => '<unique id>'] on DistributedBus::sendMessage().",
            json_decode($pulse->replies[0], true, flags: JSON_THROW_ON_ERROR)['message'],
        );
    }

    public function test_flow_versions_are_answered_only_to_the_configured_pulse_service(): void
    {
        $pulse = $this->pulseReplies();
        $orderService = $this->orderService([FlowVersionsConfiguration::createWithLocation($this->location)]);
        $pulseService = $this->pulseService($pulse);
        $intruderReplies = $this->pulseReplies();
        $intruder = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$intruderReplies::class],
            containerOrAvailableServices: [$intruderReplies],
            extensionObjects: [
                ...$this->channels,
                DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'order_service', channelName: 'order_channel'),
                ServiceConfiguration::createWithDefaults()->withServiceName('intruder'),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $intruder->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.flow_versions.list', '{}', MediaType::APPLICATION_JSON, metadata: ['ecotone_pulse_query_id' => 'query-7'], replyRoutingKey: 'ecotone.pulse.reply_received');
        $refusal = null;
        try {
            $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
        } catch (Exception $exception) {
            $refusal = $exception->getMessage();
        }
        $pulseService->run('pulse_channel', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertStringContainsString('but it only answers the configured Pulse service ecotone_pulse', (string) $refusal);
        self::assertSame([], $pulse->replies);
        self::assertSame([], $intruderReplies->replies);
    }

    private function ask(FlowTestSupport $pulseService, FlowTestSupport $orderService, string $routingKey, string $payload, string $queryId): void
    {
        $pulseService->getDistributedBus()->sendMessage('order_service', $routingKey, $payload, MediaType::APPLICATION_JSON, metadata: ['ecotone_pulse_query_id' => $queryId], replyRoutingKey: 'ecotone.pulse.reply_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
        $pulseService->run('pulse_channel', ExecutionPollingMetadata::createWithTestingSetup());
    }

    private function pulseReplies(): object
    {
        return new class () {
            public array $replies = [];

            #[InternalHandler('ecotone.pulse.reply_received')]
            public function received(string $reply): void
            {
                $this->replies[] = $reply;
            }
        };
    }

    private function orderService(array $extensionObjects): FlowTestSupport
    {
        $orders = new class () {
            #[CommandHandler('order.place')]
            public function place(string $orderId): void
            {
            }
        };

        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$orders::class],
            containerOrAvailableServices: [$orders],
            extensionObjects: [
                ...$this->channels,
                ...$extensionObjects,
                DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'ecotone_pulse', channelName: 'pulse_channel'),
                ServiceConfiguration::createWithDefaults()->withServiceName('order_service'),
                PulseConfiguration::createWithDefaults('ecotone_pulse'),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
            initialTime: new DateTimeImmutable('2026-10-20 12:00:00', new DateTimeZone('UTC')),
        );
    }

    private function pulseService(object $handlers): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handlers::class],
            containerOrAvailableServices: [$handlers],
            extensionObjects: [
                ...$this->channels,
                DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'order_service', channelName: 'order_channel'),
                ServiceConfiguration::createWithDefaults()->withServiceName('ecotone_pulse'),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}
