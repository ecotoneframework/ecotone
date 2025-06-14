<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\AroundInterceptorExample;

/**
 * Class MethodInterceptorModuleTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class MethodInterceptorModuleTest extends AnnotationConfigurationTestCase
{
    public function test_intercepting_with_around_message_endpoint(): void
    {
        $ecootneLite = EcotoneLite::bootstrapFlowTesting(
            [AroundInterceptorExample::class],
            [$service = new AroundInterceptorExample()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        $ecootneLite->sendCommandWithRoutingKey('doSomethingAsync', new stdClass());
        $this->assertNull($service->payload);
        $this->assertNull($service->consumerName);

        $ecootneLite->run('async');
        $this->assertEquals($service->payload, new stdClass());
        $this->assertEquals($service->consumerName, 'async');
    }
}
