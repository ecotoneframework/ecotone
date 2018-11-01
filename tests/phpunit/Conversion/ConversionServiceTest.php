<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Conversion;

use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\ConversionService;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\DeserializingConverter;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\MediaType;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\SerializingConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDescriptor;

/**
 * Class ConversionServiceTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConversionServiceTest extends TestCase
{
    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function test_using_php_serializing_converters()
    {
        $conversionService = ConversionService::createWith([
            new DeserializingConverter(),
            new SerializingConverter()
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
        $conversionService = ConversionService::createWith([new SerializingConverter()]);

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