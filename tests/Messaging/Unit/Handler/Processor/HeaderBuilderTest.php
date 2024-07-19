<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Test\ComponentTestBuilder;
use Ecotone\Test\InMemoryConversionService;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\HeadersConversionServiceConcrete;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;

/**
 * Class HeaderBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class HeaderBuilderTest extends TestCase
{
    /**
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_header_converter()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        HeaderBuilder::create('value', 'token'),
                    ])
            )
            ->build();

        $this->assertEquals(
            100,
            $messaging->sendDirectToChannel($inputChannel, metadata: ['token' => 100])
        );
    }

    public function test_creating_optional_header_converter()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        HeaderBuilder::createOptional('value', 'token'),
                    ])
            )
            ->build();

        $this->assertNull(
            $messaging->sendDirectToChannel($inputChannel, metadata: [])
        );
    }

    public function test_calling_with_json_conversion()
    {
        $personId = '05c60a00-2285-431a-bc3b-f840b4e81230';
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(HeadersConversionServiceConcrete::create(), 'withUuid')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        HeaderBuilder::create('value', 'token'),
                    ])
            )
            ->build();

        $headerResult = $messaging->sendDirectToChannel($inputChannel, metadata: ['token' => $personId]);
        $this->assertInstanceOf(UuidInterface::class, $headerResult);
        $this->assertEquals(Uuid::fromString($personId), $headerResult);
    }

    public function test_choosing_php_conversion_when_non_scalar_payload()
    {
        $data = ['name' => 'johny'];
        $messaging = ComponentTestBuilder::create()
            ->withConverter(
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
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(HeadersConversionServiceConcrete::create(), 'withStdClass')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        HeaderBuilder::create('value', 'personId'),
                    ])
            )
            ->build();

        $headerResult = $messaging->sendDirectToChannel($inputChannel, metadata: ['personId' => $data]);
        $this->assertInstanceOf(stdClass::class, $headerResult);
    }

    public function test_calling_with_php_to_php_conversion()
    {
        $data = '05c60a00-2285-431a-bc3b-f840b4e81230';
        $messaging = ComponentTestBuilder::create()
            ->withConverter(
                InMemoryConversionService::createWithConversion(
                    $data,
                    MediaType::APPLICATION_X_PHP,
                    TypeDescriptor::STRING,
                    MediaType::APPLICATION_X_PHP,
                    Uuid::class,
                    Uuid::fromString($data)
                )
            )
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(HeadersConversionServiceConcrete::create(), 'withUuid')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        HeaderBuilder::create('value', 'personId'),
                    ])
            )
            ->build();

        $headerResult = $messaging->sendDirectToChannel($inputChannel, metadata: ['personId' => $data]);
        $this->assertInstanceOf(UuidInterface::class, $headerResult);
    }

    public function test_passing_default_value_if_exists_and_no_header_found()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(HeadersConversionServiceConcrete::create(), 'withDefaultValue')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        HeaderBuilder::createOptional('value', 'token'),
                    ])
            )
            ->build();

        $this->assertSame(
            '',
            $messaging->sendDirectToChannel($inputChannel)
        );
    }
}
