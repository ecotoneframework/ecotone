<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
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
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('async'))
                ->withEnabledModulePackageNames([])
        );

        $ecootneLite->sendCommandWithRouting('doSomethingAsync', new stdClass());
        $this->assertNull($service->payload);
        $this->assertNull($service->consumerName);

        $ecootneLite->run('async');
        $this->assertEquals($service->payload, new stdClass());
        $this->assertEquals($service->consumerName, 'async');
    }
}
