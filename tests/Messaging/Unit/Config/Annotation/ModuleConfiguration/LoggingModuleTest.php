<?php
declare(strict_types=1);


namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Doctrine\Common\Annotations\AnnotationException;
use ReflectionException;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\LoggingModule;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessagingException;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithPoller\ServiceActivatorWithPollerExample;

/**
 * Class LoggingModuleTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LoggingModuleTest extends AnnotationConfigurationTest
{
    /**
     * @return mixed
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_registering_poller_for_endpoint()
    {
        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            ServiceActivatorWithPollerExample::class
        ]);
        $annotationConfiguration = LoggingModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $this->assertTrue(true);
    }
}