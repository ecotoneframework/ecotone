<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\MessageConverter;

use PHPUnit\Framework\TestCase;

/**
 * Class DefaultHeaderMapperTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DefaultHeaderMapperTest extends TestCase
{
    public function __test_mapping_simple_headers()
    {
        $defaultHeaderMapper = \SimplyCodedSoftware\Messaging\MessageConverter\DefaultHeaderMapper::createCaseInsensitiveHeadersWith(["content-type"], ["set-cookie"]);

        $this->assertEquals(
            ["content-type" => "application/json"],
            $defaultHeaderMapper->mapToMessageHeaders(["content-type" => "application/json"])
        );

        $this->assertEquals(
            ["set-cookie" => "123"],
            $defaultHeaderMapper->mapFromMessageHeaders(["Set-Cookie" => "123"])
        );
    }

    public function __test_mapping_associative_array()
    {
        $defaultHeaderMapper = \SimplyCodedSoftware\Messaging\MessageConverter\DefaultHeaderMapper::createCaseInsensitiveHeadersWith([["content-type", "some"]], [["set-cookie", "some"]]);

        $this->assertEquals(
            ["content-type" => "application/json"],
            $defaultHeaderMapper->mapToMessageHeaders(["content-type" => "application/json"])
        );

        $this->assertEquals(
            ["set-cookie" => "123"],
            $defaultHeaderMapper->mapFromMessageHeaders(["Set-Cookie" => "123"])
        );
    }

    public function __test_not_mapping_if_missing_source_key()
    {
        $defaultHeaderMapper = \SimplyCodedSoftware\Messaging\MessageConverter\DefaultHeaderMapper::createCaseInsensitiveHeadersWith(["type" => "content-type"], []);

        $this->assertEquals(
            [],
            $defaultHeaderMapper->mapToMessageHeaders([])
        );
    }

    public function test_mapping_multiple_keys_at_once()
    {
        $defaultHeaderMapper = \SimplyCodedSoftware\Messaging\MessageConverter\DefaultHeaderMapper::createCaseInsensitiveHeadersWith(["*"], []);

        $this->assertEquals(
            [
                "firstkey" => 1,
                "secondkey" => 2
            ],
            $defaultHeaderMapper->mapToMessageHeaders([
                "firstKey" => 1,
                "secondKey" => 2
            ])
        );
    }

    public function __test_not_mapping_if_header_is_not_scalar_type()
    {
        $defaultHeaderMapper = \SimplyCodedSoftware\Messaging\MessageConverter\DefaultHeaderMapper::createCaseInsensitiveHeadersWith(["*"], ["*"]);

        $this->assertEquals(
            [],
            $defaultHeaderMapper->mapToMessageHeaders(["object" => new \stdClass()])
        );

        $this->assertEquals(
            [],
            $defaultHeaderMapper->mapFromMessageHeaders(["Set-Cookie" => []])
        );
    }

    public function __test_mapping_multiple_keys_at_once_with_prefix()
    {
        $defaultHeaderMapper = \SimplyCodedSoftware\Messaging\MessageConverter\DefaultHeaderMapper::createCaseInsensitiveHeadersWith(["x-*"], []);

        $this->assertEquals(
            [
                "x-prefixed" => 3
            ],
            $defaultHeaderMapper->mapToMessageHeaders([
                "firstKey" => 1,
                "secondKey" => 2,
                "x-prefixed" => 3
            ])
        );
    }
}