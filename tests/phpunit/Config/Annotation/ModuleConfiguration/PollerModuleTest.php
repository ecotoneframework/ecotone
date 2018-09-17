<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;
use Fixture\Annotation\MessageEndpoint\ServiceActivator\WithPoller\ServiceActivatorWithPollerExample;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\PollerModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\NullObserver;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingMetadata;

/**
 * Class PollerModuleTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollerModuleTest extends AnnotationConfigurationTest
{
    /**
     * @return mixed
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
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
        $annotationConfiguration->prepare($configuration, [], NullObserver::create());

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }
}