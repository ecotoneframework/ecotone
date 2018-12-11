<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\ServiceActivator;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceReturningMessage;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\StaticallyCalledService;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class ServiceActivatorBuilderTest
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatorBuilderTest extends MessagingTest
{
    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_building_service_activator()
    {
        $objectToInvokeOnReference = "service-a";
        $objectToInvoke = ServiceExpectingOneArgument::create();

        $serviceActivator = ServiceActivatorBuilder::create($objectToInvokeOnReference, 'withoutReturnValue')
                                ->build(
                                    InMemoryChannelResolver::createEmpty(),
                                    InMemoryReferenceSearchService::createWith([
                                        $objectToInvokeOnReference => $objectToInvoke
                                    ])
                                );

        $serviceActivator->handle(MessageBuilder::withPayload('some')->build());

        $this->assertTrue($objectToInvoke->wasCalled());
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_passing_same_message_as_was_reference_returned()
    {
        $objectToInvokeOnReference = "service-a";
        $replyChannel = QueueChannel::create();
        $message = MessageBuilder::withPayload("some")
                    ->build();
        $objectToInvoke = ServiceReturningMessage::createWith($message);

        $serviceActivator = ServiceActivatorBuilder::create($objectToInvokeOnReference, 'get')
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createWith([
                    $objectToInvokeOnReference => $objectToInvoke
                ])
            );

        $serviceActivator->handle(MessageBuilder::withPayload('someOther')->setReplyChannel($replyChannel)->build());

        $this->assertEquals($message, $replyChannel->receive());
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_activating_statically_called_service()
    {
        $reference = StaticallyCalledService::class;

        $serviceActivator = ServiceActivatorBuilder::create($reference, "run")
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

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_calling_direct_object_reference()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();

        $serviceActivator = ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'withoutReturnValue')
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createEmpty()
            );

        $serviceActivator->handle(MessageBuilder::withPayload('some')->build());

        $this->assertTrue($objectToInvoke->wasCalled());
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_passing_through_on_void()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();

        $serviceActivator = ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'withoutReturnValue')
            ->withPassThroughMessageOnVoidInterface(true)
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createEmpty()
            );


        $replyChannel = QueueChannel::create();
        $message = MessageBuilder::withPayload("test")
            ->setReplyChannel($replyChannel)
            ->build();
        $serviceActivator->handle($message);

        $this->assertEquals(
            $message,
            $replyChannel->receive()
        );
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_ignoring_passing_through_when_service_not_void()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();

        $serviceActivator = ServiceActivatorBuilder::createWithDirectReference($objectToInvoke, 'withReturnValue')
            ->withPassThroughMessageOnVoidInterface(true)
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createEmpty()
            );


        $replyChannel = QueueChannel::create();
        $message = MessageBuilder::withPayload("test")
            ->setReplyChannel($replyChannel)
            ->build();
        $serviceActivator->handle($message);

        $receivedMessage = $replyChannel->receive();

        $this->assertNotNull($receivedMessage);
        $this->assertNotEquals($message,$receivedMessage);
    }

    public function test_converting_to_string()
    {
        $inputChannelName = 'inputChannel';
        $endpointName = "someName";

        $this->assertEquals(
            ServiceActivatorBuilder::create("ref-name", "method-name")
                ->withInputChannelName($inputChannelName)
                ->withEndpointId($endpointName),
            sprintf("Service Activator - %s:%s with name `%s` for input channel `%s`", "ref-name", "method-name", $endpointName, $inputChannelName)
        );
    }
}