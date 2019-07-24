<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\TransformerModule;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Transformer\TransformerBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Transformer\TransformerWithMethodParameterExample;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TransformerModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_transformer_builder()
    {
        $annotationConfiguration = TransformerModule::create(
            InMemoryAnnotationRegistrationService::createFrom([TransformerWithMethodParameterExample::class])
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $messageHandlerBuilder = TransformerBuilder::create(
            TransformerWithMethodParameterExample::class, "send"
        )
            ->withEndpointId("some-id")
            ->withInputChannelName("inputChannel")
            ->withOutputMessageChannel("outputChannel")
            ->withRequiredInterceptorNames(["someReference"]);
        $messageHandlerBuilder->withMethodParameterConverters([
            PayloadBuilder::create("message")
        ]);

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerMessageHandler($messageHandlerBuilder),
            $configuration
        );
    }
}