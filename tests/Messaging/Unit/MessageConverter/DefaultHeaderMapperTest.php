<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\MessageConverter;

use Ecotone\Messaging\Conversion\InMemoryConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use stdClass;

/**
 * Class DefaultHeaderMapperTest
 * @package Test\Ecotone\Messaging\Unit\Endpoint
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DefaultHeaderMapperTest extends TestCase
{
    public function test_mapping_simple_headers()
    {
        $defaultHeaderMapper = DefaultHeaderMapper::createCaseInsensitiveHeadersWith(["content-type"], ["set-cookie"], InMemoryConversionService::createWithoutConversion());

        $this->assertEquals(
            ["content-type" => "application/json"],
            $defaultHeaderMapper->mapToMessageHeaders(["content-type" => "application/json"])
        );

        $this->assertEquals(
            ["set-cookie" => "123"],
            $defaultHeaderMapper->mapFromMessageHeaders(["Set-Cookie" => "123"])
        );
    }

    public function test_mapping_associative_array()
    {
        $defaultHeaderMapper = DefaultHeaderMapper::createCaseInsensitiveHeadersWith([["content-type", "some"]], [["set-cookie", "some"]], InMemoryConversionService::createWithoutConversion());

        $this->assertEquals(
            ["content-type" => "application/json"],
            $defaultHeaderMapper->mapToMessageHeaders(["content-type" => "application/json"])
        );

        $this->assertEquals(
            ["set-cookie" => "123"],
            $defaultHeaderMapper->mapFromMessageHeaders(["Set-Cookie" => "123"])
        );
    }

    public function test_not_mapping_if_missing_source_key()
    {
        $defaultHeaderMapper = DefaultHeaderMapper::createCaseInsensitiveHeadersWith(["type" => "content-type"], [], InMemoryConversionService::createWithoutConversion());

        $this->assertEquals(
            [],
            $defaultHeaderMapper->mapToMessageHeaders([])
        );
    }

    public function test_mapping_multiple_keys_at_once()
    {
        $defaultHeaderMapper = DefaultHeaderMapper::createCaseInsensitiveHeadersWith(["*"], [], InMemoryConversionService::createWithoutConversion());

        $this->assertEquals(
            [
                "firstkey" => 1,
                "secondkey" => 2
            ],
            $defaultHeaderMapper->mapToMessageHeaders(
                [
                    "firstKey" => 1,
                    "secondKey" => 2
                ]
            )
        );
    }

    public function test_not_mapping_if_header_is_not_scalar_type()
    {
        $defaultHeaderMapper = DefaultHeaderMapper::createCaseInsensitiveHeadersWith(["*"], ["*"], InMemoryConversionService::createWithoutConversion());

        $this->assertEquals(
            [],
            $defaultHeaderMapper->mapToMessageHeaders(["object" => new stdClass()])
        );

        $this->assertEquals(
            [],
            $defaultHeaderMapper->mapFromMessageHeaders(["Set-Cookie" => []])
        );
    }

    public function test_mapping_multiple_keys_at_once_with_prefix()
    {
        $defaultHeaderMapper = DefaultHeaderMapper::createCaseInsensitiveHeadersWith(["x-*"], [], InMemoryConversionService::createWithoutConversion());

        $this->assertEquals(
            [
                "x-prefixed" => 3
            ],
            $defaultHeaderMapper->mapToMessageHeaders(
                [
                    "firstKey" => 1,
                    "secondKey" => 2,
                    "x-prefixed" => 3
                ]
            )
        );
    }

    public function test_mapping_headers_with_json_conversion()
    {
        $personId            = "7660d93e-cdf9-43b4-be59-98a60b233c35";
        $defaultHeaderMapper = DefaultHeaderMapper::createWith(
            [],
            ["personId"],
            InMemoryConversionService::createWithConversion(
                Uuid::fromString($personId),
                MediaType::APPLICATION_X_PHP,
                UuidInterface::class,
                MediaType::APPLICATION_JSON,
                TypeDescriptor::STRING,
                $personId
            )
        );

        $this->assertEquals(
            ["personId" => $personId],
            $defaultHeaderMapper->mapFromMessageHeaders(["personId" => Uuid::fromString($personId)])
        );
    }

    public function test_converting_between_php_compound_to_php_scalar_as_first_choose()
    {
        $convertedData            = "7660d93e-cdf9-43b4-be59-98a60b233c35";
        $dataToConvert = Uuid::fromString($convertedData);
        $defaultHeaderMapper = DefaultHeaderMapper::createWith(
            [],
            ["personId"],
            InMemoryConversionService::createWithoutConversion()
                ->registerInPHPConversion($dataToConvert, $convertedData)
                ->registerConversion(
                    $dataToConvert,
                    MediaType::APPLICATION_X_PHP,
                    UuidInterface::class,
                    MediaType::APPLICATION_JSON,
                    TypeDescriptor::STRING,
                    $convertedData
                )
        );

        $this->assertEquals(
            ["personId" => $convertedData],
            $defaultHeaderMapper->mapFromMessageHeaders(["personId" => $dataToConvert])
        );
    }
}