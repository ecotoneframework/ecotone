<?php

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\RouterModule;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Router\RouterBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Router\RouterWithNoResolutionRequiredExample;

/**
 * Class AnnotationRouterConfigurationTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RouterModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_router_builder_from_annotation()
    {
        $annotationConfiguration = RouterModule::create(
            InMemoryAnnotationRegistrationService::createFrom([RouterWithNoResolutionRequiredExample::class])
        );
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());


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