<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithPoller\ServiceActivatorWithPollerExample;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\PollerModule;
use SimplyCodedSoftware\Messaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\Messaging\Config\NullObserver;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;

/**
 * Class PollerModuleTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollerModuleTest extends AnnotationConfigurationTest
{
    /**
     * @return mixed
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_poller_for_endpoint()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerPollingMetadata(
                PollingMetadata::create("test-name")
                    ->setCron("* * * * *")
                    ->setInitialDelayInMilliseconds(2000)
                    ->setFixedRateInMilliseconds(130)
                    ->setTransactionFactoryReferenceNames(["transaction"])
                    ->setErrorChannelName("errorChannel")
                    ->setMaxMessagePerPoll(5)
                    ->setTriggerReferenceName("trigger")
                    ->setTaskExecutorName("taskExecutor")
            );

        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            ServiceActivatorWithPollerExample::class
        ]);
        $annotationConfiguration = PollerModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, []);

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }
}