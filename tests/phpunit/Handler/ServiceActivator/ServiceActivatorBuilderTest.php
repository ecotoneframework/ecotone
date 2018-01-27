<?php

namespace Test\SimplyCodedSoftware\Messaging\Handler\ServiceActivator;
use Fixture\Service\ServiceExpectingOneArgument;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\MessagingTest;

/**
 * Class ServiceActivatorBuilderTest
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatorBuilderTest extends MessagingTest
{
    public function test_building_service_activator()
    {
        $objectToInvokeOnReference = "service-a";
        $objectToInvoke = ServiceExpectingOneArgument::create();

        $serviceActivator = ServiceActivatorBuilder::create($objectToInvokeOnReference, 'withoutReturnValue')
                                ->setChannelResolver(InMemoryChannelResolver::createEmpty())
                                ->setReferenceSearchService(InMemoryReferenceSearchService::createWith([
                                    $objectToInvokeOnReference => $objectToInvoke
                                ]))
                                ->build();

        $serviceActivator->handle(MessageBuilder::withPayload('some')->build());

        $this->assertTrue($objectToInvoke->wasCalled());
    }
}