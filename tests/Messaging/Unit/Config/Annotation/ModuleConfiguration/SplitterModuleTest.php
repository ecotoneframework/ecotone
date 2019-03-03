<?php

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Splitter\SplitterExample;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Annotation\Splitter;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\SplitterModule;
use SimplyCodedSoftware\Messaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\Messaging\Config\NullObserver;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Splitter\SplitterBuilder;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SplitterModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_transformer_builder()
    {
        $annotationConfiguration = SplitterModule::create(
            InMemoryAnnotationRegistrationService::createFrom([SplitterExample::class])
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, []);

        $messageHandlerBuilder = SplitterBuilder::create(
            SplitterExample::class,  "split"
        )
            ->withEndpointId("testId")
            ->withInputChannelName("inputChannel")
            ->withOutputMessageChannel("outputChannel");
        $messageHandlerBuilder->withMethodParameterConverters([
            PayloadBuilder::create("payload")
        ]);

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerMessageHandler($messageHandlerBuilder),
            $configuration
        );
    }
}