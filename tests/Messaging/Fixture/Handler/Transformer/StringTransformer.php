<?php

namespace Ecotone\Tests\Messaging\Fixture\Handler\Transformer;

/**
 * Class StringTransformer
 * @package Ecotone\Tests\Messaging\Fixture\Handler\Transformer
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StringTransformer
{
    public function transform() : string
    {
        return "some";
    }
}