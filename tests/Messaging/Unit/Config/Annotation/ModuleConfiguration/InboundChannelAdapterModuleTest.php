<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\InboundChannelAdapterModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\ServiceActivatorModule;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\InboundChannelAdapter\InboundChannelAdapterExample;

/**
 * Class InboundChannelAdapterModuleTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InboundChannelAdapterModuleTest extends AnnotationConfigurationTest
{
    /**
     */
    public function test_creating_inbound_channel_adapter_builder_from_annotation()
    {
        $annotationConfiguration = InboundChannelAdapterModule::create(
            InMemoryAnnotationRegistrationService::createFrom([
                InboundChannelAdapterExample::class
            ])
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerConsumer(
                    InboundChannelAdapterBuilder::create("requestChannel", InboundChannelAdapterExample::class, "doRun")
                        ->withEndpointId("run")
                        ->withRequiredInterceptorNames(["some"])
                )
        );
    }
}