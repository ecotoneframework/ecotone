<?php

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\SplitterModule;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Splitter\SplitterBuilder;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Splitter\SplitterExample;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SplitterModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Config\ConfigurationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_transformer_builder()
    {
        $annotationConfiguration = SplitterModule::create(
            InMemoryAnnotationFinder::createFrom([SplitterExample::class]), InterfaceToCallRegistry::createEmpty()
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

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