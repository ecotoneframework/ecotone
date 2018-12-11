<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Conversion;

use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceExpectingTwoArguments;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Conversion\ReferenceServiceConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Converter\ExampleConverterService;

/**
 * Class ReferenceServiceConverterBuilderTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceServiceConverterBuilderTest extends TestCase
{
    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
                MediaType::createApplicationXPHPObject(),
                $targetType,
                MediaType::createApplicationXPHPObject()
            )
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
}