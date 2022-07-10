<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway\ParameterConverter;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersConverter;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use PHPUnit\Framework\TestCase;

class GatewayHeadersConverterBuilderTest extends TestCase
{
    public function test_not_changing_media_type_when_payload_is_not_scalar()
    {
        $gatewayPayload = GatewayHeadersBuilder::create("some")
            ->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            MessageBuilder::withPayload(new \stdClass())
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(\stdClass::class)),
            $gatewayPayload->convertToMessage(
                MethodArgument::createWith(
                    InterfaceParameter::createNotNullable("some", TypeDescriptor::create(TypeDescriptor::OBJECT)),
                    [MessageHeaders::CONTENT_TYPE => MediaType::APPLICATION_JSON]
                ),
                MessageBuilder::withPayload(new \stdClass())
                    ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(\stdClass::class))
            )
        );
    }

    public function test_not_changing_media_type_when_payload_is_incompatible_with_given_type()
    {
        $gatewayPayload = GatewayHeadersBuilder::create("some")
            ->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            MessageBuilder::withPayload(new \stdClass())
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(\stdClass::class)),
            $gatewayPayload->convertToMessage(
                MethodArgument::createWith(
                    InterfaceParameter::createNotNullable("some", TypeDescriptor::create(TypeDescriptor::OBJECT)),
                    [MessageHeaders::CONTENT_TYPE => MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::ARRAY)]
                ),
                MessageBuilder::withPayload(new \stdClass())
                    ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(\stdClass::class))
            )
        );
    }

    public function test_changing_media_type_when_payload_is_compatible_with_given_type()
    {
        $gatewayPayload = GatewayHeadersBuilder::create("some")
            ->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            MessageBuilder::withPayload([new \stdClass()])
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter("array<\stdClass>")),
            $gatewayPayload->convertToMessage(
                MethodArgument::createWith(
                    InterfaceParameter::createNotNullable("some", TypeDescriptor::create(TypeDescriptor::OBJECT)),
                    [MessageHeaders::CONTENT_TYPE => MediaType::createApplicationXPHPWithTypeParameter("array<\stdClass>")]
                ),
                MessageBuilder::withPayload([new \stdClass()])
                    ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::ARRAY))
            )
        );
    }

    public function test_dropping_integration_headers_when_starting_new_flow_using_gateway()
    {
        $gatewayPayload = GatewayHeadersBuilder::create("some")
            ->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            MessageBuilder::withPayload([new \stdClass()]),
            $gatewayPayload->convertToMessage(
                MethodArgument::createWith(
                    InterfaceParameter::createNotNullable("some", TypeDescriptor::create(TypeDescriptor::OBJECT)),
                    [
                        MessageHeaders::ROUTING_SLIP => "X"
                    ]
                ),
                MessageBuilder::withPayload([new \stdClass()])
            )
        );
    }
}