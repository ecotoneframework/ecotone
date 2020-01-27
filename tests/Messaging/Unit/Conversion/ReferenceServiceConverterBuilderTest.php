<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Conversion;

use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingTwoArguments;
use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Conversion\ReferenceServiceConverterBuilder;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleConverterService;

/**
 * Class ReferenceServiceConverterBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceServiceConverterBuilderTest extends TestCase
{
    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_converting_using_reference_service()
    {
        $sourceType = TypeDescriptor::create("array<string>");
        $targetType = TypeDescriptor::create("array<\stdClass>");
        $referenceService = ReferenceServiceConverterBuilder::create(
            ExampleConverterService::class,
            "convert",
            $sourceType,
            $targetType
        )->build(
            InMemoryReferenceSearchService::createWith([
                ExampleConverterService::class => new ExampleConverterService()
            ])
        );

        $this->assertEquals(
            [new \stdClass()],
            $referenceService->convert(
                ["some"],
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

        ReferenceServiceConverterBuilder::create(
            ServiceExpectingTwoArguments::class,
            "withReturnValue",
            TypeDescriptor::create("array<string>"),
            TypeDescriptor::create("array<\stdClass>")
        )->build(
            InMemoryReferenceSearchService::createWith([
                ServiceExpectingTwoArguments::class => ServiceExpectingTwoArguments::create()
            ])
        );
    }

    public function test_throwing_exception_if_converter_containing_union_source_type()
    {
        $this->expectException(InvalidArgumentException::class);

        ReferenceServiceConverterBuilder::create(
            ServiceExpectingTwoArguments::class,
            "withReturnValue",
            TypeDescriptor::create("array|array<string>"),
            TypeDescriptor::create("array<\stdClass>")
        );
    }

    public function test_throwing_exception_if_converter_containing_union_target_type()
    {
        $this->expectException(InvalidArgumentException::class);

        ReferenceServiceConverterBuilder::create(
            ServiceExpectingTwoArguments::class,
            "withReturnValue",
            TypeDescriptor::create("array<string>"),
            TypeDescriptor::create("array|array<\stdClass>")
        );
    }
}