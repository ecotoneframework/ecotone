<?php

namespace Messaging\Handler\ServiceActivator;
use Fixture\Service\ServiceExpectingOneArgument;
use Messaging\Config\InMemoryChannelResolver;
use Messaging\MessagingTest;
use Messaging\Support\InvalidArgumentException;
use Messaging\Support\MessageBuilder;

/**
 * Class ServiceActivatorBuilderTest
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatorBuilderTest extends MessagingTest
{
    public function test_building_service_activator()
    {
        $objectToInvokeOn = ServiceExpectingOneArgument::create();
        $serviceActivatorBuilder = ServiceActivatorBuilder::create($objectToInvokeOn, 'withoutReturnValue');
        $serviceActivatorBuilder->setChannelResolver(InMemoryChannelResolver::createEmpty());

        $serviceActivator = $serviceActivatorBuilder->build();

        $serviceActivator->handle(MessageBuilder::withPayload('some')->build());

        $this->assertTrue($objectToInvokeOn->wasCalled());
    }

    public function future_test_throwing_exception_if_required_reply_and_response_channel_was_provided()
    {
        $objectToInvokeOn = ServiceExpectingOneArgument::create();
        $serviceActivatorBuilder = ServiceActivatorBuilder::create($objectToInvokeOn, 'withoutReturnValue');
        $serviceActivatorBuilder->setChannelResolver(InMemoryChannelResolver::createEmpty());
        $serviceActivatorBuilder->withRequiredReply(true);

        $this->expectException(InvalidArgumentException::class);

        $serviceActivatorBuilder->build();
    }
}