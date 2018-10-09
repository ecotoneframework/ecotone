<?php
declare(strict_types=1);

namespace Fixture\Conversion;

/***
 * Interface Shop
 * @package Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Shop
{
    /**
     * @param \stdClass|object $productId
     */
    public function buy($productId) : void;
}