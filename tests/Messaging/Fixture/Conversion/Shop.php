<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Conversion;

/***
 * Interface Shop
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Shop
{
    /**
     * @param \stdClass|object $product
     * @ExampleTestAnnotation
     */
    public function buy($product) : void;
}