<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Transformer;
use Test\SimplyCodedSoftware\Messaging\Fixture\Dto\OrderExample;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyEditorAccessor;
use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyReaderAccessor;

/**
 * Class PropertyEditorAccessorTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Handler\Transformer
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

    public function test_reading_from_object_via_reflection_if_no_public_method()
    {
        $path = "orderId";

        $fromData = OrderExample::createWith(100, 1, "Johny");

        $this->compareReader(100, $path, $fromData);
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