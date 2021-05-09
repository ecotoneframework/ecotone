<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\InMemoryConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\MessageBuilder;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Test\Ecotone\Messaging\Fixture\Service\CallableService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceWithDefaultArgument;
use Test\Ecotone\Messaging\Fixture\Service\ServiceWithUuidArgument;

/**
 * Class HeaderBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderBuilderTest extends TestCase
{
    /**
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_header_converter()
    {
        $converter = HeaderBuilder::create("x", "token");
        $converter = $converter->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            123,
            $converter->getArgumentFrom(
                InterfaceToCall::create(CallableService::class, "wasCalled"),
                InterfaceParameter::createNullable("x", TypeDescriptor::createWithDocBlock("string",  "")),
                MessageBuilder::withPayload("a")->setHeader("token", 123)->build(),
                []
            )
        );
    }

    public function test_creating_optional_header_converter()
    {
        $converter = HeaderBuilder::createOptional("x", "token");
        $converter = $converter->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            null,
            $converter->getArgumentFrom(
                InterfaceToCall::create(CallableService::class, "wasCalled"),
                InterfaceParameter::createNullable("x", TypeDescriptor::createWithDocBlock("string",  "")),
                MessageBuilder::withPayload("a")->build(),
                []
            )
        );
    }

    public function test_calling_with_json_conversion()
    {
        $personId = "05c60a00-2285-431a-bc3b-f840b4e81230";
        $converter = HeaderBuilder::create("x", "personId");
        $converter = $converter->build(InMemoryReferenceSearchService::createWith([
            ConversionService::REFERENCE_NAME => InMemoryConversionService::createWithConversion(
                $personId,
                MediaType::APPLICATION_JSON,
                TypeDescriptor::STRING,
                MediaType::APPLICATION_X_PHP,
                UuidInterface::class,
                Uuid::fromString($personId)
            )
        ]));

        $headerResult = $converter->getArgumentFrom(
            InterfaceToCall::create(ServiceWithUuidArgument::class, "execute"),
            InterfaceParameter::createNotNullable("x", TypeDescriptor::createWithDocBlock(UuidInterface::class, "")),
            MessageBuilder::withPayload("a")
                ->setHeader("personId",  $personId)
                ->build(),
            []
        );

        $this->assertInstanceOf(UuidInterface::class, $headerResult);
        $this->assertEquals(Uuid::fromString($personId), $headerResult);
    }

    public function test_calling_with_php_to_php_conversion()
    {
        $personId = "05c60a00-2285-431a-bc3b-f840b4e81230";
        $converter = HeaderBuilder::create("x", "personId");
        $converter = $converter->build(InMemoryReferenceSearchService::createWith([
            ConversionService::REFERENCE_NAME => InMemoryConversionService::createWithConversion(
                $personId,
                MediaType::APPLICATION_X_PHP,
                TypeDescriptor::STRING,
                MediaType::APPLICATION_X_PHP,
                Uuid::class,
                Uuid::fromString($personId)
            )
        ]));

        $headerResult = $converter->getArgumentFrom(
            InterfaceToCall::create(ServiceWithUuidArgument::class, "execute"),
            InterfaceParameter::createNotNullable("x", TypeDescriptor::createWithDocBlock(Uuid::class, "")),
            MessageBuilder::withPayload("a")
                ->setHeader("personId",  $personId)
                ->build(),
            []
        );

        $this->assertInstanceOf(UuidInterface::class, $headerResult);
        $this->assertEquals(Uuid::fromString($personId), $headerResult);
    }

    public function test_passing_default_value_if_exists_and_no_header_found()
    {
        $converter = HeaderBuilder::create("name", "token");
        $converter = $converter->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            "",
            $converter->getArgumentFrom(
                InterfaceToCall::create(ServiceWithDefaultArgument::class, "execute"),
                InterfaceParameter::create("name", TypeDescriptor::createWithDocBlock("string",  ""), false, true, "", false, []),
                MessageBuilder::withPayload("a")->build(),
                []
            )
        );
    }
}