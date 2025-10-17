<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Conversion;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Converter;
use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Conversion\ObjectToSerialized\SerializingConverter;
use Ecotone\Messaging\Conversion\SerializedToObject\DeserializingConverter;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
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
    /**
     * @throws TypeDefinitionException
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_using_php_serializing_converters()
    {
        $conversionService = AutoCollectionConversionService::createWith([
            new DeserializingConverter(),
            new SerializingConverter(),
        ]);


        $serializedObject = new stdClass();
        $serializedObject->name = 'johny';
        $serializedObject->age = 15;

        $result = $conversionService->convert(
            $serializedObject,
            Type::create(Type::OBJECT),
            MediaType::createApplicationXPHP(),
            Type::create(Type::STRING),
            MediaType::createApplicationXPHPSerialized()
        );

        $this->assertEquals(
            $serializedObject,
            $conversionService->convert(
                $result,
                Type::create(Type::STRING),
                MediaType::createApplicationXPHPSerialized(),
                Type::create(stdClass::class),
                MediaType::createApplicationXPHP()
            )
        );
    }

    public function test_not_converting_when_source_is_null()
    {
        $conversionService = AutoCollectionConversionService::createWith([new SerializingConverter()]);

        $this->assertEquals(
            null,
            $conversionService->convert(
                null,
                Type::create(Type::OBJECT),
                MediaType::createApplicationXPHP(),
                Type::create(Type::STRING),
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
        );

        /** @var ConversionService $conversionService */
        $conversionService = $ecotone->getGateway(ConversionService::class);

        $data = new SomeStringableDataOne('some-data-one');
        $this->assertEquals(
            $data->value,
            $conversionService->convert(
                $data,
                Type::create(SomeStringableDataOne::class),
                MediaType::createApplicationXPHP(),
                Type::string(),
                MediaType::createApplicationXPHP()
            )
        );

        $data = new SomeStringableDataThree('some-data-three');
        $this->assertEquals(
            $data->value,
            $conversionService->convert(
                $data,
                Type::create(SomeStringableDataThree::class),
                MediaType::createApplicationXPHP(),
                Type::string(),
                MediaType::createApplicationXPHP()
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
        );

        /** @var ConversionService $conversionService */
        $conversionService = $ecotone->getGateway(ConversionService::class);

        $data = new SomeStringableDataOne('some-data-one');
        $this->assertEquals(
            $data->value,
            $conversionService->convert(
                $data,
                Type::create(SomeStringableDataOne::class),
                MediaType::createApplicationXPHP(),
                Type::string(),
                MediaType::createApplicationXPHP()
            )
        );

        $data = new SomeStringableDataThree('some-data-three');
        $this->assertEquals(
            $data->value,
            $conversionService->convert(
                $data,
                Type::create(SomeStringableDataThree::class),
                MediaType::createApplicationXPHP(),
                Type::string(),
                MediaType::createApplicationXPHP()
            )
        );
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
