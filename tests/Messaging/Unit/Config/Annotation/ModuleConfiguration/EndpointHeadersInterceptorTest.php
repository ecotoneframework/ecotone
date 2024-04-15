<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Lite\Test\TestConfiguration;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\MessageHeaders;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\AddHeaders\AddingMultipleHeaders;

/**
 * Class EndpointHeadersInterceptorTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
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
}
