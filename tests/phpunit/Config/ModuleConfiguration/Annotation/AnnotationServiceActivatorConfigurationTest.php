<?php

namespace Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\Annotation;

use Fixture\Annotation\FileSystem\DumbModuleConfiguration;
use Fixture\Configuration\DumbConfigurationObserver;
use Fixture\Configuration\DumbModuleConfigurationRetrievingService;
use SimplyCodedSoftware\Messaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationConfiguration;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationModuleConfigurationRetrievingService;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationServiceActivatorConfiguration;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\HeaderParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\MessageParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\PayloadParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\ReferenceServiceParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\HeaderParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class AnnotationServiceActivatorConfigurationTest
 * @package Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationServiceActivatorConfigurationTest extends AnnotationConfigurationTest
{
    public function test_creating_service_activator_builder_from_annotation()
    {
        $configuration = $this->createMessagingSystemConfiguration();
        $this->annotationConfiguration->registerWithin($configuration);
        $serviceActivatorBuilder = ServiceActivatorBuilder::create("message_sender", "sendMessage");
        $serviceActivatorBuilder->withMethodParameterConverters([
            HeaderParameterConverterBuilder::create("to", "sendTo"),
            PayloadParameterConverterBuilder::create("content"),
            MessageParameterConverterBuilder::create("message"),
            ReferenceServiceParameterConverterBuilder::create("object", "reference", $serviceActivatorBuilder)
        ]);
        $serviceActivatorBuilder->registerRequiredReference("reference");

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerMessageHandler(
                    $serviceActivatorBuilder
                        ->withConsumerName("message_sender")
                        ->withInputMessageChannel("inputChannel")
                        ->withOutputChannel('outputChannel')
                        ->withRequiredReply(true)
                )
        );
    }

    /**
     * @inheritDoc
     */
    protected function createAnnotationConfiguration(): AnnotationConfiguration
    {
        return new AnnotationServiceActivatorConfiguration();
    }

    /**
     * @inheritDoc
     */
    protected function getPartOfTheNamespaceForTests(): string
    {
        return "MessageEndpoint\ServiceActivator\AllConfigurationDefined";
    }
}