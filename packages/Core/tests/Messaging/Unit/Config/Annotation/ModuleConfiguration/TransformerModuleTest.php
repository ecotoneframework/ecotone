<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\TransformerModule;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Transformer\TransformerWithMethodParameterExample;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TransformerModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_transformer_builder()
    {
        $annotationConfiguration = TransformerModule::create(
            InMemoryAnnotationFinder::createFrom([TransformerWithMethodParameterExample::class]), InterfaceToCallRegistry::createEmpty()
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

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