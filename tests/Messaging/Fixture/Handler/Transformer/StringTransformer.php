<?php

namespace Tests\Ecotone\Messaging\Fixture\Handler\Transformer;

/**
 * Class StringTransformer
 * @package Tests\Ecotone\Messaging\Fixture\Handler\Transformer
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StringTransformer
{
    public function transform() : string
    {
        return "some";
    }
}