<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\PollerModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ScheduledModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ServiceActivatorModule;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\InboundChannelAdapter\SchedulerExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\InboundChannelAdapter\SchedulerWithPollerExample;

class PollerModuleTest extends AnnotationConfigurationTest
{
    public function test_creating_inbound_channel_adapter_builder_from_annotation()
    {
        $annotationConfiguration = PollerModule::create(
            InMemoryAnnotationFinder::createFrom([
                SchedulerWithPollerExample::class
            ]), InterfaceToCallRegistry::createEmpty()
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerPollingMetadata(
                    PollingMetadata::create("run")
                        ->setCron("*****")
                        ->setErrorChannelName("errorChannel")
                        ->setInitialDelayInMilliseconds(100)
                        ->setMemoryLimitInMegaBytes(100)
                        ->setHandledMessageLimit(10)
                        ->setExecutionTimeLimitInMilliseconds(100)
                )
        );
    }
}