<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Conversion;

use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Conversion\AutoCollectionConversionService;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Conversion\SerializedToObject\DeserializingConverter;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;

/**
 * Class ConversionServiceTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConversionServiceTest extends TestCase
{
    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_using_php_serializing_converters()
    {
        $conversionService = AutoCollectionConversionService::createWith([
            new DeserializingConverter(),
            new \SimplyCodedSoftware\Messaging\Conversion\ObjectToSerialized\SerializingConverter()
        ]);


        $serializedObject = new \stdClass();
        $serializedObject->name = "johny";
        $serializedObject->age = 15;

        $result = $conversionService->convert(
            $serializedObject,
            TypeDescriptor::create(TypeDescriptor::OBJECT),
            MediaType::createApplicationXPHPObject(),
            TypeDescriptor::create(TypeDescriptor::STRING),
            MediaType::createApplicationXPHPSerializedObject()
        );

        $this->assertEquals(
            $serializedObject,
            $conversionService->convert(
                $result,
                TypeDescriptor::create(TypeDescriptor::STRING),
                MediaType::createApplicationXPHPSerializedObject(),
                TypeDescriptor::create(\stdClass::class),
                MediaType::createApplicationXPHPObject()
            )
        );
    }

    public function test_not_converting_when_source_is_null()
    {
        $conversionService = AutoCollectionConversionService::createWith([new \SimplyCodedSoftware\Messaging\Conversion\ObjectToSerialized\SerializingConverter()]);

        $this->assertEquals(
            null,
            $conversionService->convert(
                null,
                TypeDescriptor::create(TypeDescriptor::OBJECT),
                MediaType::createApplicationXPHPObject(),
                TypeDescriptor::create(TypeDescriptor::STRING),
                MediaType::createApplicationXPHPSerializedObject()
            )
        );
    }
}