<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ErrorHandlerModule;
use Ecotone\Messaging\Handler\ErrorHandler\ErrorHandler;
use Ecotone\Messaging\Handler\ErrorHandler\ErrorHandlerConfiguration;
use Ecotone\Messaging\Handler\ErrorHandler\RetryTemplateBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use ReflectionException;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\PollerModule;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\MessagingException;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithPoller\ServiceActivatorWithPollerExample;

/**
 * Class PollerModuleTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ErrorHandlerModuleTest extends AnnotationConfigurationTest
{
    /**
     * @return mixed
     * @throws MessagingException
     */
    public function test_registering_error()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(
                    new ErrorHandler(RetryTemplateBuilder::fixedBackOff(1)->build()), "handle"
                )
                    ->withEndpointId("error_handler.errorChannel")
                    ->withInputChannelName("errorChannel")
            )
            ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel("errorChannel"));

        $annotationRegistrationService = InMemoryAnnotationFinder::createEmpty();
        $annotationConfiguration = ErrorHandlerModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [
            ErrorHandlerConfiguration::create(
                "errorChannel",
                RetryTemplateBuilder::fixedBackOff(1)
            )
        ], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }

    /**
     * @return mixed
     * @throws MessagingException
     */
    public function test_registering_error_handler_with_dead_letter_channel()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
                                    ->registerMessageHandler(
                                        ServiceActivatorBuilder::createWithDirectReference(
                                            new ErrorHandler(RetryTemplateBuilder::fixedBackOff(1)->build()), "handle"
                                        )
                                            ->withEndpointId("error_handler.errorChannel")
                                            ->withInputChannelName("errorChannel")
                                            ->withOutputMessageChannel("deadLetterChannel")
                                    )
                                    ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel("errorChannel"))
                                    ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel("deadLetterChannel"));

        $annotationRegistrationService = InMemoryAnnotationFinder::createEmpty();
        $annotationConfiguration = ErrorHandlerModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [
            ErrorHandlerConfiguration::createWithDeadLetterChannel(
                "errorChannel",
                RetryTemplateBuilder::fixedBackOff(1),
                "deadLetterChannel"
            )
        ], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }
}