<?php

namespace Test\SimplyCodedSoftware\Messaging\Handler\Processor;

use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\HeaderParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\HeaderParameterConverter;
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
            HeaderParameterConverter::create($parameterName, $headerName),
            HeaderParameterConverterBuilder::create($parameterName, $headerName)
                ->build(InMemoryReferenceSearchService::createEmpty())
        );
    }
}