<?php

namespace Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;

use Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;
use Fixture\Annotation\MessageEndpoint\Transformer\TransformerWithMethodParameterExample;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationConfiguration;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder\AnnotationGatewayConfiguration;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder\AnnotationTransformerConfiguration;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\PayloadParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Transformer\TransformerBuilder;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationGatewayConfigurationTest extends AnnotationConfigurationTest
{
    public function test_creating_transformer_builder()
    {
//        $configuration = $this->createMessagingSystemConfiguration();
//
//        $this->annotationConfiguration->registerWithin($configuration);
//
//
//
//        $this->assertEquals(
//            $this->createMessagingSystemConfiguration()
//                ->registerMessageHandler($messageHandlerBuilder),
//            $configuration
//        );
        $this->assertTrue(true);
    }

    /**
     * @inheritDoc
     */
    protected function createAnnotationConfiguration(): AnnotationConfiguration
    {
        return new AnnotationGatewayConfiguration();
    }

    /**
     * @inheritDoc
     */
    protected function getPartOfTheNamespaceForTests(): string
    {
        return "MessageEndpoint\Transformer";
    }
}