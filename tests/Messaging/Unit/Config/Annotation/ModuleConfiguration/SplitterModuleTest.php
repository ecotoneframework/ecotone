<?php

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\SplitterModule;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Splitter\SplitterBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Splitter\SplitterExample;

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
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $messageHandlerBuilder = SplitterBuilder::create(
            SplitterExample::class, "split"
        )
            ->withEndpointId("testId")
            ->withInputChannelName("inputChannel")
            ->withOutputMessageChannel("outputChannel")
            ->withRequiredInterceptorNames(["someReference"]);
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