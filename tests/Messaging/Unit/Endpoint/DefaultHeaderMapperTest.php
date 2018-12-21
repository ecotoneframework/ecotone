<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Endpoint;

use PHPUnit\Framework\TestCase;

/**
 * Class DefaultHeaderMapperTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DefaultHeaderMapperTest extends TestCase
{
    public function test_mapping_simple_headers()
    {
        $defaultHeaderMapper = \SimplyCodedSoftware\Messaging\Conversion\MessageConverter\DefaultHeaderMapper::createWith(["type" => "content-type"]);

        $this->assertEquals(
            ["content-type" => "application/json"],
            $defaultHeaderMapper->map(["type" => "application/json"])
        );
    }

    public function test_not_mapping_if_missing_source_key()
    {
        $defaultHeaderMapper = \SimplyCodedSoftware\Messaging\Conversion\MessageConverter\DefaultHeaderMapper::createWith(["type" => "content-type"]);

        $this->assertEquals(
            [],
            $defaultHeaderMapper->map([])
        );
    }

    public function test_mapping_multiple_keys_at_once()
    {
        $defaultHeaderMapper = \SimplyCodedSoftware\Messaging\Conversion\MessageConverter\DefaultHeaderMapper::createWith(["*" => "*"]);

        $this->assertEquals(
            [
                "firstKey" => 1,
                "secondKey" => 2
            ],
            $defaultHeaderMapper->map([
                "firstKey" => 1,
                "secondKey" => 2
            ])
        );
    }

    public function test_mapping_multiple_keys_at_once_with_prefix()
    {
        $defaultHeaderMapper = \SimplyCodedSoftware\Messaging\Conversion\MessageConverter\DefaultHeaderMapper::createWith(["x-*" => "*"]);

        $this->assertEquals(
            [
                "x-prefixed" => 3
            ],
            $defaultHeaderMapper->map([
                "firstKey" => 1,
                "secondKey" => 2,
                "x-prefixed" => 3
            ])
        );
    }
}