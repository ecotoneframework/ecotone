<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Conversion;

use Ecotone\Api\Attribute\Converter;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Gateway\SerializerGateway;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Type;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

/**
 * Class ConversionServiceTest
 * @package Test\Ecotone\Messaging\Unit\Conversion
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class ConversionServiceTest extends TestCase
{
    public function test_using_php_serializing_converters()
    {
        $serializer = EcotoneLite::bootstrapFlowTesting(enabledModules: [])->getGateway(SerializerGateway::class);

        $serializedObject = new stdClass();
        $serializedObject->name = 'johny';
        $serializedObject->age = 15;

        $result = $serializer->convertFromPHP(
            $serializedObject,
            MediaType::createApplicationXPHPSerialized()->addParameter('type', 'string')->toString()
        );

        $this->assertEquals(
            $serializedObject,
            $serializer->convertToPHP(
                $result,
                MediaType::APPLICATION_X_PHP_SERIALIZED,
                stdClass::class
            )
        );
    }

    public function test_not_converting_when_source_is_null()
    {
        $conversionService = EcotoneLite::bootstrapFlowTesting(enabledModules: [])->getGateway(ConversionService::class);

        $this->assertEquals(
            null,
            $conversionService->convert(
                null,
                Type::create('object'),
                MediaType::createApplicationXPHP(),
                Type::create('string'),
                MediaType::createApplicationXPHPSerialized()
            )
        );
    }

    public function test_it_converting_object_to_string_using_correct_converter(): void
    {
        $converterOne = new class () {
            #[Converter]
            public function convert(SomeStringableDataOne $uuid): string
            {
                return $uuid->value;
            }
        };
        $converterTwo = new class () {
            #[Converter]
            public function convert(SomeStringableDataTwo $uuid): string
            {
                return $uuid->value;
            }
        };
        $converterThree = new class () {
            #[Converter]
            public function convert(SomeStringableDataThree $uuid): string
            {
                return $uuid->value;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [
                $converterOne::class,
                $converterTwo::class,
                $converterThree::class,
            ],
            containerOrAvailableServices: [
                $converterOne,
                $converterTwo,
                $converterThree,
            ],
            enabledModules: [],
        );

        $serializer = $ecotone->getGateway(SerializerGateway::class);

        $data = new SomeStringableDataOne('some-data-one');
        $this->assertEquals(
            $data->value,
            $serializer->convertToPHP(
                $data,
                MediaType::APPLICATION_X_PHP,
                'string'
            )
        );

        $data = new SomeStringableDataThree('some-data-three');
        $this->assertEquals(
            $data->value,
            $serializer->convertToPHP(
                $data,
                MediaType::APPLICATION_X_PHP,
                'string'
            )
        );
    }

    public function test_it_converting_object_to_string_using_correct_converter_using_static_method(): void
    {
        $converterOne = new class () {
            #[Converter]
            public static function convert(SomeStringableDataOne $uuid): string
            {
                return $uuid->value;
            }
        };
        $converterTwo = new class () {
            #[Converter]
            public static function convert(SomeStringableDataTwo $uuid): string
            {
                return $uuid->value;
            }
        };
        $converterThree = new class () {
            #[Converter]
            public static function convert(SomeStringableDataThree $uuid): string
            {
                return $uuid->value;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [
                $converterOne::class,
                $converterTwo::class,
                $converterThree::class,
            ],
            containerOrAvailableServices: [],
            enabledModules: [],
        );

        $serializer = $ecotone->getGateway(SerializerGateway::class);

        $data = new SomeStringableDataOne('some-data-one');
        $this->assertEquals(
            $data->value,
            $serializer->convertToPHP(
                $data,
                MediaType::APPLICATION_X_PHP,
                'string'
            )
        );

        $data = new SomeStringableDataThree('some-data-three');
        $this->assertEquals(
            $data->value,
            $serializer->convertToPHP(
                $data,
                MediaType::APPLICATION_X_PHP,
                'string'
            )
        );
    }

    #[DataProvider('provideConvertors')]
    public function test_it_wrong_conversion_calling(array $convertors): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: $convertors,
            containerOrAvailableServices: [],
            enabledModules: [],
        );

        $serializer = $ecotone->getGateway(SerializerGateway::class);

        $data = 'some-data';

        $this->assertEquals(
            $data,
            $serializer->convertToPHP(
                new SomeStringableDataTwo($data),
                MediaType::APPLICATION_X_PHP,
                'string'
            )
        );
    }

    public static function provideConvertors(): iterable
    {
        $converterOne = new class () {
            #[Converter]
            public static function convert(string $value): SomeStringableDataOne
            {
                // Should not be called
                return new SomeStringableDataOne('other-value');
            }
        };

        $converterTwo = new class () {
            #[Converter]
            public static function convertToString(SomeStringableDataTwo $value): string
            {
                return $value->value;
            }
        };

        yield 'Passed' => [[
            $converterTwo::class,
            $converterOne::class,
        ]];

        yield 'Failed' => [[
            $converterOne::class,
            $converterTwo::class,
        ]];
    }
}

class SomeStringableDataOne implements Stringable
{
    public function __construct(public string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

class SomeStringableDataTwo implements Stringable
{
    public function __construct(public string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

class SomeStringableDataThree implements Stringable
{
    public function __construct(public string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
