<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Conversion;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Conversion\ReferenceServiceConverterBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleConverterService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingTwoArguments;

/**
 * Class ReferenceServiceConverterBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class ReferenceServiceConverterBuilderTest extends TestCase
{
    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_converting_using_reference_service()
    {
        $sourceType = TypeDescriptor::create('array<string>');
        $targetType = TypeDescriptor::create("array<\stdClass>");
        $referenceService = ComponentTestBuilder::create()
            ->withReference(ExampleConverterService::class, new ExampleConverterService())
            ->build(ReferenceServiceConverterBuilder::create(
                ExampleConverterService::class,
                'convert',
                $sourceType,
                $targetType
            ));

        $this->assertEquals(
            [new stdClass()],
            $referenceService->convert(
                ['some'],
                $sourceType,
                MediaType::createApplicationXPHP(),
                $targetType,
                MediaType::createApplicationXPHP()
            )
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_if_there_is_more_parameters_than_one_in_converter_reference()
    {
        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create()
            ->withReference(ServiceExpectingTwoArguments::class, ServiceExpectingTwoArguments::create())
            ->build(ReferenceServiceConverterBuilder::create(
                ServiceExpectingTwoArguments::class,
                'withReturnValue',
                TypeDescriptor::create('array<string>'),
                TypeDescriptor::create("array<\stdClass>")
            ));
    }

    public function test_throwing_exception_if_converter_containing_union_source_type()
    {
        $this->expectException(InvalidArgumentException::class);

        ReferenceServiceConverterBuilder::create(
            ServiceExpectingTwoArguments::class,
            'withReturnValue',
            TypeDescriptor::create('array|array<string>'),
            TypeDescriptor::create("array<\stdClass>")
        );
    }

    public function test_throwing_exception_if_converter_containing_union_target_type()
    {
        $this->expectException(InvalidArgumentException::class);

        ReferenceServiceConverterBuilder::create(
            ServiceExpectingTwoArguments::class,
            'withReturnValue',
            TypeDescriptor::create('array<string>'),
            TypeDescriptor::create("array|array<\stdClass>")
        );
    }
}
