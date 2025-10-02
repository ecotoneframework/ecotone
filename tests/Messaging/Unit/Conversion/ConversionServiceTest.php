<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Conversion;

use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Conversion\ObjectToSerialized\SerializingConverter;
use Ecotone\Messaging\Conversion\SerializedToObject\DeserializingConverter;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

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
}
