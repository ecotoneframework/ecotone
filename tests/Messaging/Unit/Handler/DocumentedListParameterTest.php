<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\BusinessMethod;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Converter;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class DocumentedListParameterTest extends TestCase
{
    public function test_command_handler_receives_a_payload_documented_as_a_list_of_strings(): void
    {
        $handler = $this->listRecordingHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->sendCommandWithRouting('list.payload', ['first', 'second']);

        $this->assertSame([['first', 'second']], $handler->received);
    }

    public function test_command_handler_receives_an_empty_payload_documented_as_a_list_of_strings(): void
    {
        $handler = $this->listRecordingHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->sendCommandWithRouting('list.payload', []);

        $this->assertSame([[]], $handler->received);
    }

    public function test_header_parameter_receives_a_value_documented_as_a_list_of_integers(): void
    {
        $handler = $this->listRecordingHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->sendCommandWithRouting('list.header', metadata: ['versions' => [1, 2, 3]]);

        $this->assertSame([[1, 2, 3]], $handler->received);
    }

    public function test_header_parameter_receives_an_empty_value_documented_as_a_list_of_integers(): void
    {
        $handler = $this->listRecordingHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->sendCommandWithRouting('list.header', metadata: ['versions' => []]);

        $this->assertSame([[]], $handler->received);
    }

    public function test_business_method_passes_lists_as_payload_and_header_to_a_handler_documenting_them_as_lists(): void
    {
        $handler = new class () {
            public array $received = [];

            /**
             * @param string[] $orderIds
             * @param array<string> $tags
             */
            #[CommandHandler('list.tagOrders')]
            public function tagOrders(array $orderIds, #[Header('tags')] array $tags): void
            {
                $this->received[] = ['orderIds' => $orderIds, 'tags' => $tags];
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class, OrderTagging::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->getGateway(OrderTagging::class)->tag(['order-1', 'order-2'], ['vip']);

        $this->assertSame([['orderIds' => ['order-1', 'order-2'], 'tags' => ['vip']]], $handler->received);
    }

    public function test_asynchronous_handler_receives_a_serialized_payload_documented_as_a_list_of_strings(): void
    {
        $handler = new class () {
            public array $received = [];

            /**
             * @param string[] $orderIds
             */
            #[Asynchronous('async')]
            #[CommandHandler('list.asyncPayload', endpointId: 'list.asyncPayload.endpoint')]
            public function asyncPayload(array $orderIds): void
            {
                $this->received[] = $orderIds;
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler],
            ServiceConfiguration::createWithDefaults()
                ->withDefaultSerializationMediaType(MediaType::APPLICATION_X_PHP_SERIALIZED)
                ->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('async'))
                ->withEnabledModulePackageNames([]),
        );

        $ecotone
            ->sendCommandWithRouting('list.asyncPayload', ['order-1', 'order-2'])
            ->run('async');

        $this->assertSame([['order-1', 'order-2']], $handler->received);
    }

    public function test_header_parameter_receives_objects_already_of_the_documented_list_element_type(): void
    {
        $handler = new DocumentedListElementRecorder();
        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->sendCommandWithRouting('list.elements', metadata: ['elements' => [new DocumentedListElement('first')]]);

        $this->assertEquals([[new DocumentedListElement('first')]], $handler->received);
    }

    public function test_header_elements_are_converted_when_they_are_not_of_the_documented_list_element_type(): void
    {
        $handler = new DocumentedListElementRecorder();
        $converter = $this->elementConverter();
        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class, $converter::class], [$handler, $converter], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->sendCommandWithRouting('list.elements', metadata: ['elements' => ['first', 'second']]);

        $this->assertEquals([[new DocumentedListElement('first'), new DocumentedListElement('second')]], $handler->received);
    }

    public function test_header_values_are_converted_when_they_are_not_of_the_documented_keyed_collection_value_type(): void
    {
        $handler = new DocumentedListElementRecorder();
        $converter = $this->elementConverter();
        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class, $converter::class], [$handler, $converter], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->sendCommandWithRouting('list.keyedElements', metadata: ['elements' => ['primary' => 'first', 'secondary' => 'second']]);

        $this->assertEquals(
            [['primary' => new DocumentedListElement('first'), 'secondary' => new DocumentedListElement('second')]],
            $handler->received
        );
    }

    private function listRecordingHandler(): object
    {
        return new class () {
            public array $received = [];

            /**
             * @param string[] $orderIds
             */
            #[CommandHandler('list.payload')]
            public function payload(array $orderIds): void
            {
                $this->received[] = $orderIds;
            }

            /**
             * @param int[] $versions
             */
            #[CommandHandler('list.header')]
            public function header(#[Header('versions')] array $versions): void
            {
                $this->received[] = $versions;
            }
        };
    }

    private function elementConverter(): object
    {
        return new class () {
            #[Converter]
            public function convert(string $id): DocumentedListElement
            {
                return new DocumentedListElement($id);
            }
        };
    }
}

/**
 * licence Apache-2.0
 * @internal
 */
interface OrderTagging
{
    /**
     * @param string[] $orderIds
     * @param string[] $tags
     */
    #[BusinessMethod('list.tagOrders')]
    public function tag(array $orderIds, #[Header('tags')] array $tags): void;
}

/**
 * licence Apache-2.0
 * @internal
 */
final class DocumentedListElement
{
    public function __construct(public readonly string $id)
    {
    }
}

/**
 * licence Apache-2.0
 * @internal
 */
final class DocumentedListElementRecorder
{
    public array $received = [];

    /**
     * @param DocumentedListElement[] $elements
     */
    #[CommandHandler('list.elements')]
    public function elements(#[Header('elements')] array $elements): void
    {
        $this->received[] = $elements;
    }

    /**
     * @param array<string, DocumentedListElement> $elements
     */
    #[CommandHandler('list.keyedElements')]
    public function keyedElements(#[Header('elements')] array $elements): void
    {
        $this->received[] = $elements;
    }
}
