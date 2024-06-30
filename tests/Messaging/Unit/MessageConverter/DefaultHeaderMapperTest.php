<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\MessageConverter;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Test\InMemoryConversionService;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use stdClass;

/**
 * Class DefaultHeaderMapperTest
 * @package Test\Ecotone\Messaging\Unit\Endpoint
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class DefaultHeaderMapperTest extends TestCase
{
    public function test_mapping_simple_headers()
    {
        $defaultHeaderMapper = DefaultHeaderMapper::createCaseInsensitiveHeadersWith(['content-type'], ['set-cookie']);

        $this->assertEquals(
            ['content-type' => 'application/json'],
            $defaultHeaderMapper->mapToMessageHeaders(['content-type' => 'application/json'], $this->getConversionService())
        );

        $this->assertEquals(
            ['set-cookie' => '123'],
            $defaultHeaderMapper->mapFromMessageHeaders(['Set-Cookie' => '123'], $this->getConversionService())
        );
    }

    public function test_mapping_associative_array()
    {
        $defaultHeaderMapper = DefaultHeaderMapper::createCaseInsensitiveHeadersWith([['content-type', 'some']], [['set-cookie', 'some']]);

        $this->assertEquals(
            ['content-type' => 'application/json'],
            $defaultHeaderMapper->mapToMessageHeaders(['content-type' => 'application/json'], $this->getConversionService())
        );

        $this->assertEquals(
            ['set-cookie' => '123'],
            $defaultHeaderMapper->mapFromMessageHeaders(['Set-Cookie' => '123'], $this->getConversionService())
        );
    }

    public function test_not_mapping_if_missing_source_key()
    {
        $defaultHeaderMapper = DefaultHeaderMapper::createCaseInsensitiveHeadersWith(['type' => 'content-type'], []);

        $this->assertEquals(
            [],
            $defaultHeaderMapper->mapToMessageHeaders([], $this->getConversionService())
        );
    }

    public function test_mapping_multiple_keys_at_once()
    {
        $defaultHeaderMapper = DefaultHeaderMapper::createCaseInsensitiveHeadersWith(['*'], []);

        $this->assertEquals(
            [
                'firstkey' => 1,
                'secondkey' => 2,
            ],
            $defaultHeaderMapper->mapToMessageHeaders(
                [
                    'firstKey' => 1,
                    'secondKey' => 2,
                ],
                $this->getConversionService()
            )
        );
    }

    public function test_not_mapping_if_header_is_not_scalar_type()
    {
        $defaultHeaderMapper = DefaultHeaderMapper::createCaseInsensitiveHeadersWith(['*'], ['*']);

        $this->assertEquals(
            [],
            $defaultHeaderMapper->mapToMessageHeaders(['object' => new stdClass()], $this->getConversionService())
        );

        $this->assertEquals(
            [],
            $defaultHeaderMapper->mapFromMessageHeaders(['Set-Cookie' => []], $this->getConversionService())
        );
    }

    public function test_mapping_multiple_keys_at_once_with_prefix()
    {
        $defaultHeaderMapper = DefaultHeaderMapper::createCaseInsensitiveHeadersWith(['x-*'], []);

        $this->assertEquals(
            [
                'x-prefixed' => 3,
            ],
            $defaultHeaderMapper->mapToMessageHeaders(
                [
                    'firstKey' => 1,
                    'secondKey' => 2,
                    'x-prefixed' => 3,
                ],
                $this->getConversionService()
            )
        );
    }

    public function test_mapping_headers_with_json_conversion()
    {
        $personId            = '7660d93e-cdf9-43b4-be59-98a60b233c35';
        $defaultHeaderMapper = DefaultHeaderMapper::createWith(
            [],
            ['personId']
        );

        $this->assertEquals(
            ['personId' => $personId],
            $defaultHeaderMapper->mapFromMessageHeaders(
                ['personId' => Uuid::fromString($personId)],
                InMemoryConversionService::createWithConversion(
                    Uuid::fromString($personId),
                    MediaType::APPLICATION_X_PHP,
                    UuidInterface::class,
                    MediaType::APPLICATION_JSON,
                    TypeDescriptor::STRING,
                    $personId
                )
            )
        );
    }

    public function test_converting_between_php_compound_to_php_scalar_as_first_choose()
    {
        $convertedData            = '7660d93e-cdf9-43b4-be59-98a60b233c35';
        $dataToConvert = Uuid::fromString($convertedData);
        $defaultHeaderMapper = DefaultHeaderMapper::createWith(
            [],
            ['personId']
        );

        $this->assertEquals(
            ['personId' => $convertedData],
            $defaultHeaderMapper->mapFromMessageHeaders(
                ['personId' => $dataToConvert],
                $this->getConversionService()
                    ->registerInPHPConversion($dataToConvert, $convertedData)
                    ->registerConversion(
                        $dataToConvert,
                        MediaType::APPLICATION_X_PHP,
                        UuidInterface::class,
                        MediaType::APPLICATION_JSON,
                        TypeDescriptor::STRING,
                        $convertedData
                    )
            )
        );
    }

    private function getConversionService(): InMemoryConversionService
    {
        return InMemoryConversionService::createWithoutConversion();
    }
}
