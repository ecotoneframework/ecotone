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
}
