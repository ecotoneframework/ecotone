<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Transformer;
use Test\Ecotone\Messaging\Fixture\Dto\OrderExample;
use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;

/**
 * Class PropertyEditorAccessorTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PropertyReaderAccessorTest extends TestCase
{
    public function test_reading_value_from_array_at_first_level()
    {
        $path = "token";
        $fromData = [
            "token" => 123,
            "password" => "secret"
        ];

        $this->compareReader(123, $path, $fromData);
    }

    public function test_reading_value_from_array_at_second_level()
    {
        $path = "token['secret']";

        $fromData = [
            "token" => [
                "secret" => 1
            ]
        ];

        $this->compareReader(1, $path, $fromData);
    }

    public function test_reading_value_from_array_at_third_level()
    {
        $path = "token['secret'][some]";

        $fromData = [
            "token" => [
                "secret" => [
                    "some" => 0
                ]
            ]
        ];

        $this->compareReader(0, $path, $fromData);
    }

    public function test_reading_from_object_from_public_method()
    {
        $path = "buyerName";

        $fromData = OrderExample::createWith(100, 1, "Johny");

        $this->compareReader("Johny", $path, $fromData);
    }

    public function test_reading_not_existing_key_returning_null()
    {
        $path = "buyerName";

        $fromData = OrderExample::createWith(100, 1, "Johny");

        $this->compareReader("Johny", $path, $fromData);
        $this->assertEquals(
            true,
            (new PropertyReaderAccessor())->hasPropertyValue(PropertyPath::createWith($path), $fromData)
        );
    }

    public function test_reading_from_object_via_reflection_if_no_public_method()
    {
        $path = "notExistingKey";
        $fromData = [
            "token" => 123
        ];

        $this->assertEquals(
            false,
            (new PropertyReaderAccessor())->hasPropertyValue(PropertyPath::createWith($path), $fromData)
        );
    }

    /**
     * @param $path
     * @param $fromData
     */
    private function compareReader($expectedValue, $path, $fromData): void
    {
        $this->assertEquals(
            $expectedValue,
            (new PropertyReaderAccessor())->getPropertyValue(PropertyPath::createWith($path), $fromData)
        );
    }
}