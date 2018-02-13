<?php

namespace Test\SimplyCodedSoftware\Messaging\Handler\Processor;

use Fixture\Handler\DumbMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageToReferenceServiceParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageToReferenceServiceParameterConverterBuilder;
use Test\SimplyCodedSoftware\Messaging\MessagingTest;

/**
 * Class ReferenceServiceParameterConverterTest
 * @package Test\SimplyCodedSoftware\Messaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceServiceParameterConverterBuilderTest extends MessagingTest
{
    public function test_creating_reference_service_parameter_converter()
    {
        $messageHandlerBuilder = DumbMessageHandlerBuilder::createSimple();
        $parameterName = "parameterName";
        $referenceName = "referenceName";
        $reference = new \stdClass();
        $referenceServiceParameterConverter = MessageToReferenceServiceParameterConverterBuilder::create($parameterName, $referenceName, $messageHandlerBuilder);

        $this->assertEquals(
            $referenceName,
            $messageHandlerBuilder->getRequiredReferenceNames()[1]
        );

        $this->assertEquals(
            MessageToReferenceServiceParameterConverter::create($parameterName, $reference),
            $referenceServiceParameterConverter->build(InMemoryReferenceSearchService::createWith([
                $referenceName => $reference
            ]))
       );
    }
}