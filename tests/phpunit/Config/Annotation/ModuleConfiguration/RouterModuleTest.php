<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use Fixture\Annotation\MessageEndpoint\Router\RouterWithNoResolutionRequiredExample;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Router;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\RouterModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\NullObserver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\PayloadBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Router\RouterBuilder;

/**
 * Class AnnotationRouterConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RouterModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_creating_router_builder_from_annotation()
    {
        $objectToInvokeReference                            = RouterWithNoResolutionRequiredExample::class;
        $routerAnnotation                                   = new Router();
        $routerAnnotation->inputChannelName                 = "inputChannel";
        $routerAnnotation->isResolutionRequired             = false;
        $messageToPayloadParameterAnnotation                = new Payload();
        $messageToPayloadParameterAnnotation->parameterName = "content";
        $routerAnnotation->parameterConverters              = [
            $messageToPayloadParameterAnnotation
        ];

        $annotationConfiguration = RouterModule::create(
            $this->createAnnotationRegistrationService(
                $objectToInvokeReference,
                "route",
                new MessageEndpoint(),
                $routerAnnotation

            )
        );
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], NullObserver::create());


        $router = RouterBuilder::create("inputChannel", $objectToInvokeReference, "route")
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