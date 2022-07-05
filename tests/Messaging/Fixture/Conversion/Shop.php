<?php
declare(strict_types=1);

namespace Ecotone\Tests\Messaging\Fixture\Conversion;

/***
 * Interface Shop
 * @package Ecotone\Tests\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Shop
{
    #[ExampleTestAnnotation]
    public function buy($product) : void;
}