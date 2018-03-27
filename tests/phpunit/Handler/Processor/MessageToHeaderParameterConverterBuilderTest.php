<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor;

use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaderDoesNotExistsException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class HeaderParameterConverterBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageToHeaderParameterConverterBuilderTest extends MessagingTest
{
    public function test_creating_header_parameter_converter()
    {
        $headerName = 'private-token';
        $parameterName = 'token';
        $this->assertEquals(
            MessageToHeaderParameterConverter::create($parameterName, $headerName, true),
            $this->createMessageHeaderParameterConverter($parameterName, $headerName)
        );
    }

    public function test_throwing_exception_if_no_header_available()
    {
        $headerName = 'private-token';
        $parameterName = 'token';
        $headerConverter = $this->createMessageHeaderParameterConverter($parameterName, $headerName);

        $this->expectException(MessageHeaderDoesNotExistsException::class);

        $headerConverter->getArgumentFrom(
            MessageBuilder::withPayload("some")
                ->build()
        );
    }

    public function test_returning_null_if_header_is_no_required_and_not_available()
    {
        $headerName = 'private-token';
        $parameterName = 'token';
        $headerConverter = $this->createMessagingHeaderParameterConverterWithRequired($parameterName, $headerName, false);

        $this->assertNull($headerConverter->getArgumentFrom(
            MessageBuilder::withPayload("some")
                ->build()
        ));
    }

    /**
     * @param $parameterName
     * @param $headerName
     *
     * @return \SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter
     */
    private function createMessageHeaderParameterConverter(string $parameterName, string $headerName): \SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter
    {
        return $this->createMessagingHeaderParameterConverterWithRequired($parameterName, $headerName, true);
    }

    /**
     * @param string $parameterName
     * @param string $headerName
     * @param bool   $isRequired
     *
     * @return MessageToParameterConverter
     */
    private function createMessagingHeaderParameterConverterWithRequired(string $parameterName, string $headerName, bool $isRequired) : MessageToParameterConverter
    {
        return MessageToHeaderParameterConverterBuilder::create($parameterName, $headerName)
            ->setRequired($isRequired)
            ->build(InMemoryReferenceSearchService::createEmpty());
    }
}