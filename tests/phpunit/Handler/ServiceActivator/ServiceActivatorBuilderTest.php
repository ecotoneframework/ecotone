<?php

namespace Messaging\Handler\ServiceActivator;
use Fixture\Service\ServiceExpectingOneArgument;
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

        $serviceActivator = $serviceActivatorBuilder->build();

        $serviceActivator->handle(MessageBuilder::withPayload('some')->build());

        $this->assertTrue($objectToInvokeOn->wasCalled());
    }

    public function future_test_throwing_exception_if_required_reply_and_response_channel_was_provided()
    {
        $objectToInvokeOn = ServiceExpectingOneArgument::create();
        $serviceActivatorBuilder = ServiceActivatorBuilder::create($objectToInvokeOn, 'withoutReturnValue');
        $serviceActivatorBuilder->withRequiredReply(true);

        $this->expectException(InvalidArgumentException::class);

        $serviceActivatorBuilder->build();
    }
}