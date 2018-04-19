<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor;

use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToStaticValueParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class MessageToStaticValueParameterBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageToStaticValueParameterBuilderTest extends TestCase
{
    public function test_getting_static_value_for_parameter()
    {
        $parameterName = "some";
        $staticValue   = new \stdClass();
        $converter     = MessageToStaticValueParameterConverterBuilder::create($parameterName, $staticValue);


        $messageToParameterConverter = $converter->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            $staticValue,
            $messageToParameterConverter->getArgumentFrom(MessageBuilder::withPayload("some")->build())
        );
    }
}