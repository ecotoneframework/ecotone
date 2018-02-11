<?php

namespace Test\SimplyCodedSoftware\Messaging\Handler\Processor;

use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\MessageParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageParameterConverter;
use Test\SimplyCodedSoftware\Messaging\MessagingTest;

/**
 * Class MessageParameterConverterBuilderTest
 * @package Test\SimplyCodedSoftware\Messaging\Handler\Processor
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