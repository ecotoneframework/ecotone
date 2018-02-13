<?php

namespace Test\SimplyCodedSoftware\Messaging\Handler\Processor;

use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverter;
use Test\SimplyCodedSoftware\Messaging\MessagingTest;

/**
 * Class HeaderParameterConverterBuilderTest
 * @package Test\SimplyCodedSoftware\Messaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderParameterConverterBuilderTest extends MessagingTest
{
    public function test_creating_header_parameter_converter()
    {
        $headerName = 'private-token';
        $parameterName = 'token';
        $this->assertEquals(
            MessageToHeaderParameterConverter::create($parameterName, $headerName),
            MessageToHeaderParameterConverterBuilder::create($parameterName, $headerName)
                ->build(InMemoryReferenceSearchService::createEmpty())
        );
    }
}