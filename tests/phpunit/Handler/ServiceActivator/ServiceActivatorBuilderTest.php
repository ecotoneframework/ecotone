<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator;
use Fixture\Service\ServiceExpectingOneArgument;
use Fixture\Service\StaticallyCalledService;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class ServiceActivatorBuilderTest
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatorBuilderTest extends MessagingTest
{
    public function test_building_service_activator()
    {
        $objectToInvokeOnReference = "service-a";
        $objectToInvoke = ServiceExpectingOneArgument::create();

        $serviceActivator = ServiceActivatorBuilder::create("some", $objectToInvokeOnReference, 'withoutReturnValue')
                                ->build(
                                    InMemoryChannelResolver::createEmpty(),
                                    InMemoryReferenceSearchService::createWith([
                                        $objectToInvokeOnReference => $objectToInvoke
                                    ])
                                );

        $serviceActivator->handle(MessageBuilder::withPayload('some')->build());

        $this->assertTrue($objectToInvoke->wasCalled());
    }

    public function test_activating_statically_called_service()
    {
        $reference = StaticallyCalledService::class;

        $serviceActivator = ServiceActivatorBuilder::create("some", $reference, "run")
                                ->build(InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createEmpty());


        $payload = "Hello World";
        $replyChannel = QueueChannel::create();
        $serviceActivator->handle(
            MessageBuilder::withPayload($payload)
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(
            $payload,
            $replyChannel->receive()->getPayload()
        );
    }

    public function test_calling_direct_object_reference()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();

        $serviceActivator = ServiceActivatorBuilder::createWithDirectReference("some", $objectToInvoke, 'withoutReturnValue')
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createEmpty()
            );

        $serviceActivator->handle(MessageBuilder::withPayload('some')->build());

        $this->assertTrue($objectToInvoke->wasCalled());
    }
}