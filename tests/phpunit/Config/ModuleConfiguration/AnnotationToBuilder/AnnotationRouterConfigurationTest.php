<?php

namespace Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;

use Fixture\Annotation\MessageEndpoint\Router\RouterWithNoResolutionRequiredExample;
use Fixture\Annotation\MessageEndpoint\Transformer\TransformerWithMethodParameterExample;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationConfiguration;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder\AnnotationRouterConfiguration;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\PayloadParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Router\RouterBuilder;

/**
 * Class AnnotationRouterConfigurationTest
 * @package Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationRouterConfigurationTest extends AnnotationConfigurationTest
{
    public function test_creating_router_builder_from_annotation()
    {
        $configuration = $this->createMessagingSystemConfiguration();
        $this->annotationConfiguration->registerWithin($configuration);

        $objectToInvokeReference = RouterWithNoResolutionRequiredExample::class;

        $router = RouterBuilder::create($objectToInvokeReference, "inputChannel", $objectToInvokeReference, "route");
        $router->setResolutionRequired(false);
        $router->withMethodParameterConverters([
            PayloadParameterConverterBuilder::create("content")
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
    protected function createAnnotationConfiguration(): AnnotationConfiguration
    {
        return new AnnotationRouterConfiguration();
    }

    /**
     * @inheritDoc
     */
    protected function getPartOfTheNamespaceForTests(): string
    {
        return "MessageEndpoint\Router";
    }
}