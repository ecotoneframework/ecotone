<?php
declare(strict_types=1);

namespace Tests\Ecotone\Messaging\Fixture\Conversion;

/***
 * Interface Shop
 * @package Tests\Ecotone\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Shop
{
    #[ExampleTestAnnotation]
    public function buy($product) : void;
}