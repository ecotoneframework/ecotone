<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Messaging\Handler\ErrorHandler\RetryTemplateBuilder;
use ReflectionException;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\PollerModule;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\MessagingException;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithPoller\ServiceActivatorWithPollerExample;

/**
 * Class PollerModuleTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollerModuleTest extends AnnotationConfigurationTest
{
    /**
     * @return mixed
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_registering_poller_for_endpoint()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerPollingMetadata(
                PollingMetadata::create("test-name")
                    ->setCron("* * * * *")
                    ->setInitialDelayInMilliseconds(2000)
                    ->setFixedRateInMilliseconds(130)
                    ->setErrorChannelName("errorChannel")
                    ->setMaxMessagePerPoll(5)
                    ->setHandledMessageLimit(10)
                    ->setMemoryLimitInMegaBytes(100)
                    ->setExecutionTimeLimitInMilliseconds(200)
                    ->setConnectionRetryTemplate(RetryTemplateBuilder::exponentialBackoffWithMaxDelay(100, 2, 3))
            );

        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            ServiceActivatorWithPollerExample::class
        ]);
        $annotationConfiguration = PollerModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }
}