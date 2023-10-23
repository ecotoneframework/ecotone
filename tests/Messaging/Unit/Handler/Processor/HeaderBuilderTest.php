<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Config\Container\BoundParameterConverter;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\InMemoryConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\HeadersConversionService;

/**
 * Class HeaderBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class HeaderBuilderTest extends TestCase
{
    /**
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_header_converter()
    {
        $converter = new BoundParameterConverter(
            HeaderBuilder::create('some', 'token'),
            InterfaceToCall::create(HeadersConversionService::class, 'withNullableString')
        );
        $converter = ComponentTestBuilder::create()
            ->build($converter);

        $this->assertEquals(
            123,
            $converter->getArgumentFrom(
                MessageBuilder::withPayload('a')->setHeader('token', 123)->build(),
            )
        );
    }

    public function test_creating_optional_header_converter()
    {
        $converter = HeaderBuilder::createOptional('some', 'token');
        $converter = ComponentTestBuilder::create()->build(new BoundParameterConverter(
            $converter,
            InterfaceToCall::create(HeadersConversionService::class, 'withNullableString')
        ));

        $this->assertEquals(
            null,
            $converter->getArgumentFrom(
                MessageBuilder::withPayload('a')->build(),
            )
        );
    }

    public function test_calling_with_json_conversion()
    {
        $personId = '05c60a00-2285-431a-bc3b-f840b4e81230';
        $converter = HeaderBuilder::create('uuid', 'personId');
        $converter = ComponentTestBuilder::create()
            ->withReference(
                ConversionService::REFERENCE_NAME,
                InMemoryConversionService::createWithConversion(
                    $personId,
                    MediaType::APPLICATION_JSON,
                    TypeDescriptor::STRING,
                    MediaType::APPLICATION_X_PHP,
                    Uuid::class,
                    Uuid::fromString($personId)
                )
            )
            ->build(new BoundParameterConverter(
                $converter,
                InterfaceToCall::create(HeadersConversionService::class, 'withUuid'),
            ));

        $headerResult = $converter->getArgumentFrom(
            MessageBuilder::withPayload('a')
                ->setHeader('personId', $personId)
                ->build(),
        );

        $this->assertInstanceOf(UuidInterface::class, $headerResult);
        $this->assertEquals(Uuid::fromString($personId), $headerResult);
    }

    public function test_choosing_php_conversion_when_non_scalar_payload()
    {
        $data = ['name' => 'johny'];
        $converter = HeaderBuilder::create('uuid', 'personIds');
        $converter = ComponentTestBuilder::create()
            ->withReference(
                ConversionService::REFERENCE_NAME,
                InMemoryConversionService::createWithoutConversion()
                    ->registerConversion(
                        $data,
                        MediaType::APPLICATION_JSON,
                        TypeDescriptor::ARRAY,
                        MediaType::APPLICATION_X_PHP,
                        stdClass::class,
                        '{"name":"johny"}'
                    )
                    ->registerConversion(
                        $data,
                        MediaType::APPLICATION_X_PHP_ARRAY,
                        TypeDescriptor::ARRAY,
                        MediaType::APPLICATION_X_PHP,
                        stdClass::class,
                        new stdClass()
                    )
            )
            ->build(new BoundParameterConverter(
                $converter,
                InterfaceToCall::create(HeadersConversionService::class, 'withStdClass'),
            ));

        $headerResult = $converter->getArgumentFrom(
            MessageBuilder::withPayload('a')
                ->setHeader('personIds', $data)
                ->build(),
        );

        $this->assertEquals(new stdClass(), $headerResult);
    }

    public function test_calling_with_php_to_php_conversion()
    {
        $personId = '05c60a00-2285-431a-bc3b-f840b4e81230';
        $converter = HeaderBuilder::create('uuid', 'personId');
        $converter = ComponentTestBuilder::create()
            ->withReference(
                ConversionService::REFERENCE_NAME,
                InMemoryConversionService::createWithConversion(
                    $personId,
                    MediaType::APPLICATION_X_PHP,
                    TypeDescriptor::STRING,
                    MediaType::APPLICATION_X_PHP,
                    Uuid::class,
                    Uuid::fromString($personId)
                )
            )
            ->build(new BoundParameterConverter(
                $converter,
                InterfaceToCall::create(HeadersConversionService::class, 'withUuid'),
            ));

        $headerResult = $converter->getArgumentFrom(
            MessageBuilder::withPayload('a')
                ->setHeader('personId', $personId)
                ->build(),
        );

        $this->assertInstanceOf(UuidInterface::class, $headerResult);
        $this->assertEquals(Uuid::fromString($personId), $headerResult);
    }

    public function test_passing_default_value_if_exists_and_no_header_found()
    {
        $converter = HeaderBuilder::create('name', 'token');
        $converter = ComponentTestBuilder::create()
            ->build(new BoundParameterConverter(
                $converter,
                InterfaceToCall::create(HeadersConversionService::class, 'withDefaultValue')
            ));

        $this->assertEquals(
            '',
            $converter->getArgumentFrom(
                MessageBuilder::withPayload('a')->build(),
            )
        );
    }
}
