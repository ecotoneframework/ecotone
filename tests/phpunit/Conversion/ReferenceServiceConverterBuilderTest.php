<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Conversion;

use Fixture\Annotation\Converter\ExampleConverterService;
use Fixture\Service\ServiceExpectingNoArguments;
use Fixture\Service\ServiceExpectingTwoArguments;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\MediaType;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\ReferenceServiceConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class ReferenceServiceConverterBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceServiceConverterBuilderTest extends TestCase
{
    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_converting_using_reference_service()
    {
        $sourceType = TypeDescriptor::create("array<string>", false);
        $targetType = TypeDescriptor::create("array<\stdClass>", false);
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_throwing_exception_if_there_is_more_parameters_than_one_in_converter_reference()
    {
        $this->expectException(InvalidArgumentException::class);

        ReferenceServiceConverterBuilder::create(
            ServiceExpectingTwoArguments::class,
            "withReturnValue",
            TypeDescriptor::create("array<string>", false),
            TypeDescriptor::create("array<\stdClass>", false)
        )->build(
            InMemoryReferenceSearchService::createWith([
                ServiceExpectingTwoArguments::class => ServiceExpectingTwoArguments::create()
            ])
        );
    }
}