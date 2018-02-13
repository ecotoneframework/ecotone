<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor;

use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverterBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class PayloadParameterConverterBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PayloadParameterConverterBuilderTest extends MessagingTest
{
    public function test_creating_parameter_converter()
    {
        $parameterName = "parameterName";

        $this->assertEquals(
            MessageToPayloadParameterConverter::create($parameterName),
            MessageToPayloadParameterConverterBuilder::create($parameterName)->build(InMemoryReferenceSearchService::createEmpty())
        );
    }
}