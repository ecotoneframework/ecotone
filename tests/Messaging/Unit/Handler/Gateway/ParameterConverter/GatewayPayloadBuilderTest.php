<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway\ParameterConverter;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Handler\UnionTypeDescriptor;
use Ecotone\Messaging\Support\MessageBuilder;
use PHPUnit\Framework\TestCase;

class GatewayPayloadBuilderTest extends TestCase
{
    public function test_resolving_class_type_when_parameter_is_non_array()
    {
        $gatewayPayload = GatewayPayloadBuilder::create("some")
                                ->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            MessageBuilder::withPayload(new \stdClass())
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(\stdClass::class)),
            $gatewayPayload->convertToMessage(
                MethodArgument::createWith(
                    InterfaceParameter::createNotNullable("some", TypeDescriptor::create(TypeDescriptor::OBJECT)),
                    new \stdClass()
                ),
                MessageBuilder::withPayload("x")
            )
        );
    }

    public function test_resolving_class_type_when_parameter_is_union_type()
    {
        $gatewayPayload = GatewayPayloadBuilder::create("some")
            ->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            MessageBuilder::withPayload(new \stdClass())
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(\stdClass::class)),
            $gatewayPayload->convertToMessage(
                MethodArgument::createWith(
                    InterfaceParameter::createNotNullable("some", UnionTypeDescriptor::createWith([TypeDescriptor::create(\stdClass::class), TypeDescriptor::createArrayType()])),
                    new \stdClass()
                ),
                MessageBuilder::withPayload("x")
            )
        );
    }

    public function test_resolving_class_type_when_parameter_is_anything()
    {
        $gatewayPayload = GatewayPayloadBuilder::create("some")
            ->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            MessageBuilder::withPayload(new \stdClass())
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(\stdClass::class)),
            $gatewayPayload->convertToMessage(
                MethodArgument::createWith(
                    InterfaceParameter::createNotNullable("some", TypeDescriptor::create(TypeDescriptor::ANYTHING)),
                    new \stdClass()
                ),
                MessageBuilder::withPayload("x")
            )
        );
    }
}