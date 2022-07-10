<?php

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\RouterModule;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Router\RouterWithNoResolutionRequiredExample;

/**
 * Class AnnotationRouterConfigurationTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RouterModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Config\ConfigurationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_router_builder_from_annotation()
    {
        $annotationConfiguration = RouterModule::create(
            InMemoryAnnotationFinder::createFrom([RouterWithNoResolutionRequiredExample::class]), InterfaceToCallRegistry::createEmpty()
        );
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());


        $router = RouterBuilder::create(RouterWithNoResolutionRequiredExample::class, "route")
            ->withEndpointId("some-id")
            ->withInputChannelName("inputChannel")
            ->setResolutionRequired(false);
        $router->withMethodParameterConverters([
            PayloadBuilder::create("content")
        ]);

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerMessageHandler($router),
            $configuration
        );
    }
}