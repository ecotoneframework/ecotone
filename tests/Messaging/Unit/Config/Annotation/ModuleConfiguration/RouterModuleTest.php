<?php

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Router\RouterWithNoResolutionRequiredExample;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Annotation\Router;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\RouterModule;
use SimplyCodedSoftware\Messaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\Messaging\Config\NullObserver;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Router\RouterBuilder;

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
        $annotationConfiguration->prepare($configuration, []);


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