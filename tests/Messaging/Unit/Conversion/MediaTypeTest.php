<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Conversion;

use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class MediaTypeTest
 * @package Test\Ecotone\Messaging\Unit\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MediaTypeTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_parsing_media_type_from_string()
    {
        $this->assertEquals(
            MediaType::create("application", "json"), MediaType::parseMediaType(MediaType::APPLICATION_JSON)
        );

        $this->assertEquals(
            MediaType::create("application", "vnc.custom.json"), MediaType::parseMediaType("application/vnc.custom.json")
        );

        $this->assertEquals(
            MediaType::createWithParameters("application", "x-php-object", [
                "type" => "array<\stdClass>",
                "charset" => "utf-8"
            ]),
            MediaType::parseMediaType("application/x-php-object;type=array<\stdClass>;charset=utf-8")
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_converting_to_string()
    {
        $this->assertEquals(
            "application/x-php-object;type=array<\stdClass>;charset=utf-8",
            (string)MediaType::createWithParameters("application", "x-php-object", [
                "type" => "array<\stdClass>",
                "charset" => "utf-8"
            ])->toString()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_retrieving_parameters()
    {
        $mediaType = MediaType::parseMediaType("application/json;charset=utf-8");

        $this->assertEquals($mediaType->getParameters(), ["charset" => "utf-8"]);
        $this->assertTrue($mediaType->hasParameter("charset"));
        $this->assertEquals($mediaType->getParameter("charset"), "utf-8");
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_if_retrieving_not_existing_parameter()
    {
        $mediaType = MediaType::parseMediaType("application/json");

        $this->expectException(InvalidArgumentException::class);

        $mediaType->getParameter("some");
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_when_there_is_no_primary_type()
    {
        $this->expectException(InvalidArgumentException::class);

        MediaType::create("", "bap");
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_when_there_is_no_subtype_defined()
    {
        $this->expectException(InvalidArgumentException::class);

        MediaType::create("test", "");
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_if_parsed_mime_type_has_no_subtype()
    {
        $this->expectException(InvalidArgumentException::class);

        MediaType::parseMediaType("test");
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_if_parsed_mime_type_has_no_type()
    {
        $this->expectException(InvalidArgumentException::class);

        MediaType::parseMediaType("/test");
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_media_types_compatibility()
    {
        $this->assertTrue(
            MediaType::parseMediaType(MediaType::APPLICATION_JSON)
                ->isCompatibleWith(MediaType::parseMediaType(MediaType::APPLICATION_JSON))
        );

        $this->assertTrue(
            MediaType::parseMediaType(MediaType::APPLICATION_JSON)
                ->isCompatibleWith(MediaType::parseMediaType("application/*"))
        );

        $this->assertTrue(
            MediaType::parseMediaType("*/json")
                ->isCompatibleWith(MediaType::parseMediaType(MediaType::APPLICATION_JSON))
        );

        $this->assertFalse(
            MediaType::parseMediaType(MediaType::APPLICATION_XML)
                ->isCompatibleWith(MediaType::parseMediaType(MediaType::APPLICATION_JSON))
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_x_php_object_with_type_parameter()
    {
        $this->assertEquals(
            "array<string>",
            MediaType::createApplicationXPHPWithTypeParameter("array<string>")->getParameter("type")
        );
    }
}