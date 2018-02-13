<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor;

use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverter;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class HeaderParameterConverterBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor
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