<?php

namespace Test\Ecotone\JMSConverter\Unit;

use Ecotone\JMSConverter\JMSConverter;
use Ecotone\JMSConverter\JMSConverterBuilder;
use Ecotone\JMSConverter\JMSConverterConfiguration;
use Ecotone\JMSConverter\JMSHandlerAdapter;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDescriptor;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use stdClass;
use Test\Ecotone\JMSConverter\Fixture\Configuration\ArrayConversion\ClassToArrayConverter;
use Test\Ecotone\JMSConverter\Fixture\Configuration\Status\Person;
use Test\Ecotone\JMSConverter\Fixture\Configuration\Status\Status;
use Test\Ecotone\JMSConverter\Fixture\Configuration\Status\StatusConverter;
use Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert\CollectionProperty;
use Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert\PersonAbstractClass;
use Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert\PersonInterface;
use Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert\PropertiesWithDocblockTypes;
use Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert\PropertyWithAnnotationMetadataDefined;
use Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert\PropertyWithNullUnionType;
use Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert\PropertyWithTypeAndMetadataType;
use Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert\PropertyWithUnionType;
use Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert\ThreeLevelNestedObjectProperty;
use Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert\TwoLevelNestedCollectionProperty;
use Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert\TwoLevelNestedObjectProperty;
use Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert\TypedProperty;

/**
 * @internal
 */
class JMSConverterTest extends TestCase
{
    public function test_converting_with_docblock_types()
    {
        $toSerialize = new PropertiesWithDocblockTypes('Johny', 'Silverhand');
        $expectedSerializationString = '{"name":"Johny","surname":"Silverhand"}';

        $this->assertSerializationAndDeserializationWithJSON($toSerialize, $expectedSerializationString);
    }

    public function test_converting_with_annotation_docblock()
    {
        $toSerialize = new PropertyWithAnnotationMetadataDefined('Johny');
        $expectedSerializationString = '{"naming":"Johny"}';

        $this->assertSerializationAndDeserializationWithJSON($toSerialize, $expectedSerializationString);
    }

    public function test_overriding_type_with_metadata()
    {
        $toSerialize = new PropertyWithTypeAndMetadataType(5);
        $expectedSerializationString = '{"data":5}';

        $this->assertSerializationAndDeserializationWithJSON($toSerialize, $expectedSerializationString);
    }

    public function test_converting_with_typed_property()
    {
        $toSerialize = new TypedProperty(3);
        $expectedSerializationString = '{"data":3}';

        $this->assertSerializationAndDeserializationWithJSON($toSerialize, $expectedSerializationString);
    }

    public function test_two_level_object_nesting()
    {
        $toSerialize = new TwoLevelNestedObjectProperty(new PropertyWithTypeAndMetadataType(3));
        $expectedSerializationString = '{"data":{"data":3}}';

        $this->assertSerializationAndDeserializationWithJSON($toSerialize, $expectedSerializationString);
    }

    public function test_three_level_object_nesting()
    {
        $toSerialize = new ThreeLevelNestedObjectProperty(new TwoLevelNestedObjectProperty(new PropertyWithTypeAndMetadataType(3)));
        $expectedSerializationString = '{"data":{"data":{"data":3}}}';

        $this->assertSerializationAndDeserializationWithJSON($toSerialize, $expectedSerializationString);
    }

    public function test_with_collection_type()
    {
        $toSerialize = new CollectionProperty([new PropertyWithTypeAndMetadataType(3), new PropertyWithTypeAndMetadataType(4)]);
        $expectedSerializationString = '{"collection":[{"data":3},{"data":4}]}';

        $this->assertSerializationAndDeserializationWithJSON($toSerialize, $expectedSerializationString);
    }

    public function test_with_nested_collection_type()
    {
        $toSerialize = new TwoLevelNestedCollectionProperty([
            new CollectionProperty([new PropertyWithTypeAndMetadataType(1), new PropertyWithTypeAndMetadataType(2)]),
            new CollectionProperty([new PropertyWithTypeAndMetadataType(3), new PropertyWithTypeAndMetadataType(4)]),
        ]);
        $expectedSerializationString = '{"collection":[{"collection":[{"data":1},{"data":2}]},{"collection":[{"data":3},{"data":4}]}]}';

        $this->assertSerializationAndDeserializationWithJSON($toSerialize, $expectedSerializationString);
    }

    public function test_skipping_nullable_type()
    {
        $toSerialize = new PropertyWithNullUnionType('100');
        $expectedSerializationString = '{"data":"100"}';

        $this->assertSerializationAndDeserializationWithJSON($toSerialize, $expectedSerializationString);
    }

    public function test_converting_with_ignoring_null()
    {
        $toSerialize = ['test' => null];
        $expectedSerializationString = '[]';

        $this->assertEquals(
            $expectedSerializationString,
            $this->serializeToJson($toSerialize, [], JMSConverterConfiguration::createWithDefaults()->withDefaultNullSerialization(false))
        );
    }

    public function test_converting_with_keeping_null()
    {
        $toSerialize = ['test' => null];
        $expectedSerializationString = '{"test":null}';

        $this->assertEquals(
            $expectedSerializationString,
            $this->serializeToJson($toSerialize, [], JMSConverterConfiguration::createWithDefaults()->withDefaultNullSerialization(true))
        );
    }

    public function test_throwing_exception_if_converted_type_is_union_type()
    {
        $toSerialize = new PropertyWithUnionType([]);
        $expectedSerializationString = '{"data":[]}';

        $this->expectException(InvalidArgumentException::class);

        $this->assertSerializationAndDeserializationWithJSON($toSerialize, $expectedSerializationString);
    }

    public function test_serializing_with_metadata_cache()
    {
        $toSerialize = new PropertyWithTypeAndMetadataType(5);
        $converter = (new JMSConverterBuilder([], JMSConverterConfiguration::createWithDefaults(), '/tmp/' . Uuid::uuid4()->toString()))->build(InMemoryReferenceSearchService::createWith([]));

        $serialized = $converter->convert($toSerialize, TypeDescriptor::createFromVariable($toSerialize), MediaType::createApplicationXPHP(), TypeDescriptor::createStringType(), MediaType::createApplicationJson());

        $this->assertEquals(
            $toSerialize,
            $converter->convert($serialized, TypeDescriptor::createStringType(), MediaType::createApplicationJson(), TypeDescriptor::createFromVariable($toSerialize), MediaType::createApplicationXPHP(), TypeDescriptor::createFromVariable($toSerialize))
        );
    }

    public function test_converting_with_jms_handlers_using_simple_type_to_class_mapping()
    {
        $toSerialize = new Person(new Status('active'));
        $expectedSerializationString = '{"status":"active"}';

        $this->assertSerializationAndDeserializationWithJSON($toSerialize, $expectedSerializationString, [
            JMSHandlerAdapter::create(
                TypeDescriptor::create(Status::class),
                TypeDescriptor::createStringType(),
                StatusConverter::class,
                'convertFrom'
            ),
            JMSHandlerAdapter::create(
                TypeDescriptor::createStringType(),
                TypeDescriptor::create(Status::class),
                StatusConverter::class,
                'convertTo'
            ),
        ]);
    }

    public function test_converting_with_jms_handlers_using_array_to_class_mapping()
    {
        $toSerialize = new stdClass();
        $toSerialize->data = 'someInformation';
        $expectedSerializationString = '{"data":"someInformation"}';

        $this->assertSerializationAndDeserializationWithJSON($toSerialize, $expectedSerializationString, [
            JMSHandlerAdapter::create(
                TypeDescriptor::createArrayType(),
                TypeDescriptor::create(stdClass::class),
                ClassToArrayConverter::class,
                'convertFrom'
            ),
            JMSHandlerAdapter::create(
                TypeDescriptor::create(stdClass::class),
                TypeDescriptor::createArrayType(),
                ClassToArrayConverter::class,
                'convertTo'
            ),
        ]);
    }

    public function test_converting_array_of_objects_to_array()
    {
        $toSerialize = [new Status('active'), new Status('archived')];
        $expectedSerialized = ['active', 'archived'];

        $jmsHandlerAdapters = [
            JMSHandlerAdapter::create(
                TypeDescriptor::create(Status::class),
                TypeDescriptor::createStringType(),
                StatusConverter::class,
                'convertFrom'
            ),
            JMSHandlerAdapter::create(
                TypeDescriptor::createStringType(),
                TypeDescriptor::create(Status::class),
                StatusConverter::class,
                'convertTo'
            ),
        ];

        $this->assertEquals($expectedSerialized, $this->serializeToArray($toSerialize, $jmsHandlerAdapters));
    }

    public function test_converting_array_of_objects_to_json()
    {
        $toSerialize = [new Status('active'), new Status('archived')];
        $expectedSerializationString = '["active","archived"]';

        $jmsHandlerAdapters = [
            JMSHandlerAdapter::create(
                TypeDescriptor::create(Status::class),
                TypeDescriptor::createStringType(),
                StatusConverter::class,
                'convertFrom'
            ),
            JMSHandlerAdapter::create(
                TypeDescriptor::createStringType(),
                TypeDescriptor::create(Status::class),
                StatusConverter::class,
                'convertTo'
            ),
        ];

        $serialized = $this->serializeToJson($toSerialize, $jmsHandlerAdapters);
        $this->assertEquals($expectedSerializationString, $serialized);
        $this->assertEquals($toSerialize, $this->deserialize($serialized, "array<Test\Ecotone\JMSConverter\Fixture\Configuration\Status\Status>", $jmsHandlerAdapters));
    }

    public function test_converting_json_to_array()
    {
        $toSerialize = ['name' => 'johny', 'surname' => 'franco'];
        $expectedSerializationString = '{"name":"johny","surname":"franco"}';

        $serialized = $this->getJMSConverter([])->convert($toSerialize, TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP(), TypeDescriptor::createStringType(), MediaType::createApplicationJson());
        $this->assertEquals($expectedSerializationString, $serialized);
        $this->assertEquals($toSerialize, $this->getJMSConverter([])->convert($serialized, TypeDescriptor::createStringType(), MediaType::createApplicationJson(), TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP()));
    }

    public function test_converting_from_array_to_object()
    {
        $toSerialize = new TwoLevelNestedObjectProperty(new PropertyWithTypeAndMetadataType(3));
        $expectedSerializationObject = ['data' => ['data' => 3]];

        $serialized = $this->getJMSConverter([])->convert($toSerialize, TypeDescriptor::createFromVariable($toSerialize), MediaType::createApplicationXPHP(), TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP());
        $this->assertEquals($expectedSerializationObject, $serialized);
        $this->assertEquals($toSerialize, $this->getJMSConverter([])->convert($serialized, TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP(), TypeDescriptor::createFromVariable($toSerialize), MediaType::createApplicationXPHP()));
    }

    public function test_converting_with_nulls()
    {
        $toSerialize = new TwoLevelNestedObjectProperty(new PropertyWithTypeAndMetadataType(null));
        $expectedSerializationObject = ['data' => ['data' => null]];

        $serialized = $this->getJMSConverter([])->convert($toSerialize, TypeDescriptor::createFromVariable($toSerialize), MediaType::createApplicationXPHP(), TypeDescriptor::createArrayType(), MediaType::createWithParameters('application', 'x-php', [JMSConverter::SERIALIZE_NULL_PARAMETER => 'true']));
        $this->assertEquals($expectedSerializationObject, $serialized);
        $this->assertEquals($toSerialize, $this->getJMSConverter([])->convert($serialized, TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP(), TypeDescriptor::createFromVariable($toSerialize), MediaType::createApplicationXPHP()));
    }

    public function test_converting_to_xml()
    {
        $toSerialize = new Person(new Status('active'));
        $expectedSerializationString = '<?xml version="1.0" encoding="UTF-8"?>
<result>
  <status>
    <type><![CDATA[active]]></type>
  </status>
</result>
';

        $serialized = $this->getJMSConverter([])->convert($toSerialize, TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP(), TypeDescriptor::createStringType(), MediaType::createApplicationXml());
        $this->assertEquals($expectedSerializationString, $serialized);
        $this->assertEquals($toSerialize, $this->getJMSConverter([])->convert($serialized, TypeDescriptor::createStringType(), MediaType::createApplicationXml(), TypeDescriptor::create(Person::class), MediaType::createApplicationXPHP()));
    }

    public function test_matching_conversion_from_array_to_xml()
    {
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP(), TypeDescriptor::createStringType(), MediaType::createApplicationXml())
        );
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createStringType(), MediaType::createApplicationXml(), TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP())
        );
    }

    public function test_matching_conversion_from_object_to_xml()
    {
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::create(Person::class), MediaType::createApplicationXPHP(), TypeDescriptor::createStringType(), MediaType::createApplicationXml())
        );
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createStringType(), MediaType::createApplicationXml(), TypeDescriptor::create(Person::class), MediaType::createApplicationXPHP())
        );
    }

    public function test_matching_conversion_from_array_to_json()
    {
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP(), TypeDescriptor::createStringType(), MediaType::createApplicationJson())
        );
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createStringType(), MediaType::createApplicationJson(), TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP())
        );
    }

    public function test_matching_conversion_from_php_object_to_php_array()
    {
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::create(Person::class), MediaType::createApplicationXPHP(), TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP())
        );
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP(), TypeDescriptor::create(Person::class), MediaType::createApplicationXPHP())
        );
    }

    public function test_matching_conversion_from_json_object_to_php_object()
    {
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::create(Person::class), MediaType::createApplicationXPHP(), TypeDescriptor::createStringType(), MediaType::createApplicationJson())
        );
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createStringType(), MediaType::createApplicationJson(), TypeDescriptor::create(Person::class), MediaType::createApplicationXPHP())
        );
    }

    public function test_matching_conversion_from_php_interface_to_json()
    {
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::create(PersonInterface::class), MediaType::createApplicationXPHP(), TypeDescriptor::createStringType(), MediaType::createApplicationJson())
        );
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createStringType(), MediaType::createApplicationJson(), TypeDescriptor::create(PersonInterface::class), MediaType::createApplicationXPHP())
        );
    }

    public function test_matching_conversion_from_php_abstract_class_to_json()
    {
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::create(PersonAbstractClass::class), MediaType::createApplicationXPHP(), TypeDescriptor::createStringType(), MediaType::createApplicationJson())
        );
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createStringType(), MediaType::createApplicationJson(), TypeDescriptor::create(PersonAbstractClass::class), MediaType::createApplicationXPHP())
        );
    }

    public function test_matching_conversion_from_php_collection_to_array()
    {
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createCollection(Person::class), MediaType::createApplicationXPHP(), TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP())
        );
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP(), TypeDescriptor::createCollection(Person::class), MediaType::createApplicationXPHP())
        );
    }

    public function test_matching_conversion_from_php_array_to_php_array()
    {
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP(), TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP())
        );
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP(), TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP())
        );
    }

    public function test_matching_conversion_from_php_collection_of_objects_to_array()
    {
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createCollection('object'), MediaType::createApplicationXPHP(), TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP())
        );
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP(), TypeDescriptor::createCollection('object'), MediaType::createApplicationXPHP())
        );
    }

    public function test_not_matching_conversion_from_object_to_format_different_than_xml_and_json()
    {
        $this->assertFalse(
            $this->getJMSConverter([])->matches(TypeDescriptor::create(Person::class), MediaType::createApplicationXPHP(), TypeDescriptor::createStringType(), MediaType::createApplicationOcetStream())
        );
        $this->assertFalse(
            $this->getJMSConverter([])->matches(TypeDescriptor::createStringType(), MediaType::createApplicationOcetStream(), TypeDescriptor::create(Person::class), MediaType::createApplicationXPHP())
        );
    }

    public function test_matching_conversion_from_array_to_object_and_opposite()
    {
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP(), TypeDescriptor::create(stdClass::class), MediaType::createApplicationXPHP())
        );
        $this->assertTrue(
            $this->getJMSConverter([])->matches(TypeDescriptor::create(stdClass::class), MediaType::createApplicationXPHP(), TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP())
        );
    }

    private function assertSerializationAndDeserializationWithJSON(object|array $toSerialize, string $expectedSerializationString, $jmsHandlerAdapters = [], ?JMSConverterConfiguration $configuration = null): void
    {
        $serialized = $this->serializeToJson($toSerialize, $jmsHandlerAdapters, $configuration);
        $this->assertEquals($expectedSerializationString, $serialized);
        $this->assertEquals($toSerialize, $this->deserialize($serialized, is_array($toSerialize) ? TypeDescriptor::ARRAY : get_class($toSerialize), $jmsHandlerAdapters));
    }

    private function serializeToJson($data, array $jmsHandlerAdapters, ?JMSConverterConfiguration $configuration = null)
    {
        return $this->getJMSConverter($jmsHandlerAdapters, $configuration)->convert($data, TypeDescriptor::createFromVariable($data), MediaType::createApplicationXPHP(), TypeDescriptor::createStringType(), MediaType::createApplicationJson());
    }

    private function serializeToArray($data, array $jmsHandlerAdapters)
    {
        return $this->getJMSConverter($jmsHandlerAdapters)->convert($data, TypeDescriptor::createFromVariable($data), MediaType::createApplicationXPHP(), TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP());
    }

    private function deserialize(string $data, string $type, array $jmsHandlerAdapters)
    {
        return $this->getJMSConverter($jmsHandlerAdapters)->convert($data, TypeDescriptor::createStringType(), MediaType::createApplicationJson(), TypeDescriptor::create($type), MediaType::createApplicationXPHP(), TypeDescriptor::create($type));
    }

    private function getJMSConverter(array $jmsHandlerAdapters, ?JMSConverterConfiguration $configuration = null): Converter
    {
        return (new JMSConverterBuilder($jmsHandlerAdapters, $configuration ? $configuration : JMSConverterConfiguration::createWithDefaults(), null))->build(InMemoryReferenceSearchService::createWith([
            StatusConverter::class => new StatusConverter(),
            ClassToArrayConverter::class => new ClassToArrayConverter(),
        ]));
    }
}
