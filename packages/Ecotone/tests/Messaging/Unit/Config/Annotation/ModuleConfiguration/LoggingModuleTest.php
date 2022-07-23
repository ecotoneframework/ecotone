<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\LoggingModule;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\MessagingException;
use ReflectionException;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithPoller\ServiceActivatorWithPollerExample;

/**
 * Class LoggingModuleTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
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
        $annotationRegistrationService = InMemoryAnnotationFinder::createFrom([
            ServiceActivatorWithPollerExample::class,
        ]);
        $annotationConfiguration = LoggingModule::create($annotationRegistrationService, InterfaceToCallRegistry::createEmpty());
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertTrue(true);
    }
}
