<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor;

use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageParameterConverterBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class MessageParameterConverterBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageParameterConverterBuilderTest extends MessagingTest
{
    public function test_creating_parameter_converter()
    {
        $parameterName = "parameterName";
        $this->assertEquals(
            MessageParameterConverter::create($parameterName),
            MessageParameterConverterBuilder::create($parameterName)->build(InMemoryReferenceSearchService::createEmpty())
        );
    }
}