<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use DateTimeImmutable;
use Ecotone\Lite\EcotoneLite;
use Ecotone\Lite\Test\TestConfiguration;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Scheduling\TimeSpan;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\Messaging\Fixture\AddHeaders\AddingMultipleHeaders;

/**
 * Class EndpointHeadersInterceptorTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class EndpointHeadersInterceptorTest extends TestCase
{
    public function test_adding_multiple_headers()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [
                AddingMultipleHeaders::class,
            ],
            [
                AddingMultipleHeaders::class => new AddingMultipleHeaders(),
            ],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            testConfiguration: TestConfiguration::createWithDefaults()->withSpyOnChannel('async')
        );

        $headers = $ecotoneLite
            ->sendCommandWithRoutingKey('addHeaders', metadata: [
                'user' => '1233',
            ])
            ->getRecordedEcotoneMessagesFrom('async')[0]->getHeaders()->headers();

        $this->assertEquals(1001, $headers[MessageHeaders::TIME_TO_LIVE]);
        $this->assertEquals(1000, $headers[MessageHeaders::DELIVERY_DELAY]);
        $this->assertEquals(1, $headers[MessageHeaders::PRIORITY]);
        $this->assertEquals(123, $headers['token']);
        $this->assertArrayNotHasKey('user', $headers);
    }

    public function test_evaluating_with_expressions()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [
                AddingMultipleHeaders::class,
            ],
            [
                AddingMultipleHeaders::class => new AddingMultipleHeaders(),
            ],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            testConfiguration: TestConfiguration::createWithDefaults()->withSpyOnChannel('async')
        );

        $command = new stdClass();
        $command->delay = (new DateTimeImmutable())->modify('+1 day');
        $command->timeToLive = 1001;

        $headers = $ecotoneLite
            ->sendCommandWithRoutingKey(
                'addHeadersWithExpression',
                command: $command,
                metadata: [
                    'token' => 123,
                ]
            )
            ->getRecordedEcotoneMessagesFrom('async')[0]->getHeaders()->headers();

        $this->assertEquals(1001, $headers[MessageHeaders::TIME_TO_LIVE]);
        $this->assertNotEmpty($headers[MessageHeaders::DELIVERY_DELAY]);
        $this->assertEquals(123, $headers['token']);
    }

    public function test_throwing_exception_when_wrong_type_passed_to_delivery_delay(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [
                AddingMultipleHeaders::class,
            ],
            [
                AddingMultipleHeaders::class => new AddingMultipleHeaders(),
            ],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            testConfiguration: TestConfiguration::createWithDefaults()->withSpyOnChannel('async')
        );

        $command = new stdClass();
        $command->delay = new stdClass();
        $command->timeToLive = 1001;

        $this->expectException(ConfigurationException::class);

        $ecotoneLite
            ->sendCommandWithRoutingKey(
                'addHeadersWithExpression',
                command: $command,
                metadata: [
                    'token' => 123,
                ]
            );
    }

    public function test_throwing_exception_when_wrong_type_passed_to_delivery_time_to_live(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [
                AddingMultipleHeaders::class,
            ],
            [
                AddingMultipleHeaders::class => new AddingMultipleHeaders(),
            ],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            testConfiguration: TestConfiguration::createWithDefaults()->withSpyOnChannel('async')
        );

        $command = new stdClass();
        $command->delay = TimeSpan::withSeconds(1);
        $command->timeToLive = new stdClass();

        $this->expectException(ConfigurationException::class);

        $ecotoneLite
            ->sendCommandWithRoutingKey(
                'addHeadersWithExpression',
                command: $command,
                metadata: [
                    'token' => 123,
                ]
            );
    }

    public function test_delivery_delay_and_time_to_live_without_existing_headers_replace()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [
                AddingMultipleHeaders::class,
            ],
            [
                AddingMultipleHeaders::class => new AddingMultipleHeaders(),
            ],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            testConfiguration: TestConfiguration::createWithDefaults()->withSpyOnChannel('async')
        );

        $headers = $ecotoneLite
            ->sendCommandWithRoutingKey('keepHeaders', metadata: [
                MessageHeaders::DELIVERY_DELAY => $deliveryDelay = 1,
                MessageHeaders::TIME_TO_LIVE => $timeToLive = TimeSpan::withSeconds(2),
            ])
            ->getRecordedEcotoneMessagesFrom('async')[0]->getHeaders()->headers();

        $this->assertEquals($deliveryDelay, $headers[MessageHeaders::DELIVERY_DELAY]);
        $this->assertEquals($timeToLive->toMilliseconds(), $headers[MessageHeaders::TIME_TO_LIVE]);
    }

    public function test_delivery_delay_and_time_to_live_add_headers_when_headers_are_missing()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [
                AddingMultipleHeaders::class,
            ],
            [
                AddingMultipleHeaders::class => new AddingMultipleHeaders(),
            ],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            testConfiguration: TestConfiguration::createWithDefaults()->withSpyOnChannel('async')
        );

        $headers = $ecotoneLite
            ->sendCommandWithRoutingKey('keepHeaders')
            ->getRecordedEcotoneMessagesFrom('async')[0]->getHeaders()->headers();

        $this->assertEquals(1000, $headers[MessageHeaders::DELIVERY_DELAY]);
        $this->assertEquals(1001, $headers[MessageHeaders::TIME_TO_LIVE]);
    }

    public function test_delivery_delay_with_time_to_live_attribute()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [
                AddingMultipleHeaders::class,
            ],
            [
                AddingMultipleHeaders::class => new AddingMultipleHeaders(),
            ],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            testConfiguration: TestConfiguration::createWithDefaults()->withSpyOnChannel('async')
        );

        $headers = $ecotoneLite
            ->sendCommandWithRoutingKey('keepDeliveryDelayHeader', metadata: [
                MessageHeaders::DELIVERY_DELAY => $deliveryDelay = 1,
            ])
            ->getRecordedEcotoneMessagesFrom('async')[0]->getHeaders()->headers();

        $this->assertEquals($deliveryDelay, $headers[MessageHeaders::DELIVERY_DELAY]);
        $this->assertEquals(1001, $headers[MessageHeaders::TIME_TO_LIVE]);
    }

    public function test_time_to_live_with_delayed_attribute()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [
                AddingMultipleHeaders::class,
            ],
            [
                AddingMultipleHeaders::class => new AddingMultipleHeaders(),
            ],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            testConfiguration: TestConfiguration::createWithDefaults()->withSpyOnChannel('async')
        );

        $headers = $ecotoneLite
            ->sendCommandWithRoutingKey('keepTtlHeader', metadata: [
                MessageHeaders::TIME_TO_LIVE => $timeToLive = 1,
            ])
            ->getRecordedEcotoneMessagesFrom('async')[0]->getHeaders()->headers();

        $this->assertEquals(1000, $headers[MessageHeaders::DELIVERY_DELAY]);
        $this->assertEquals($timeToLive, $headers[MessageHeaders::TIME_TO_LIVE]);
    }

    public function test_delayed_attribute_with_string_containing_utc_offset(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [
                AddingMultipleHeaders::class,
            ],
            [
                AddingMultipleHeaders::class => new AddingMultipleHeaders(),
            ],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            testConfiguration: TestConfiguration::createWithDefaults()->withSpyOnChannel('async')
        );

        $command = new stdClass();
        $command->delay = '2030-01-01 12:00:00+02:00';
        $command->timeToLive = 1001;

        $headers = $ecotoneLite
            ->sendCommandWithRoutingKey(
                'addHeadersWithExpression',
                command: $command,
                metadata: [
                    'token' => 123,
                ]
            )
            ->getRecordedEcotoneMessagesFrom('async')[0]->getHeaders()->headers();

        $this->assertIsInt($headers[MessageHeaders::DELIVERY_DELAY]);
        $this->assertGreaterThan(0, $headers[MessageHeaders::DELIVERY_DELAY]);
    }

    public function test_throwing_exception_when_string_without_utc_offset_passed_to_delivery_delay(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [AddingMultipleHeaders::class],
            [AddingMultipleHeaders::class => new AddingMultipleHeaders()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            testConfiguration: TestConfiguration::createWithDefaults()->withSpyOnChannel('async')
        );

        $command = new stdClass();
        $command->delay = '2025-01-01 12:00:00';

        $this->expectException(ConfigurationException::class);

        $ecotoneLite->sendCommandWithRoutingKey('addHeadersWithStringDelayExpression', command: $command);
    }
}
