<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator;
use Fixture\Service\ServiceExpectingOneArgument;
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
}