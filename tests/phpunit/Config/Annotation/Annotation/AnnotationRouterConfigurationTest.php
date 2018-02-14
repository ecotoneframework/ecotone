<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation;

use Fixture\Annotation\MessageEndpoint\Router\RouterWithNoResolutionRequiredExample;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation\AnnotationRouterConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Router\RouterBuilder;

/**
 * Class AnnotationRouterConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationRouterConfigurationTest extends AnnotationConfigurationTest
{
    public function test_creating_router_builder_from_annotation()
    {
        $configuration = $this->createMessagingSystemConfiguration();
        $this->annotationConfiguration->registerWithin($configuration, InMemoryConfigurationVariableRetrievingService::createEmpty());

        $objectToInvokeReference = RouterWithNoResolutionRequiredExample::class;

        $router = RouterBuilder::create($objectToInvokeReference, "inputChannel", $objectToInvokeReference, "route");
        $router->setResolutionRequired(false);
        $router->withMethodParameterConverters([
            MessageToPayloadParameterConverterBuilder::create("content")
        ]);


        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerMessageHandler($router),
            $configuration
        );
    }

    /**
     * @inheritDoc
     */
    protected function createAnnotationConfiguration(): string
    {
        return AnnotationRouterConfiguration::class;
    }

    /**
     * @inheritDoc
     */
    protected function getPartOfTheNamespaceForTests(): string
    {
        return "MessageEndpoint\Router";
    }
}