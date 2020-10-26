<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Transformer;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Annotation\Transformer;

class TransformerWithMethodParameterExample
{
    #[Transformer("inputChannel", "some-id", "outputChannel", ["someReference"])]
    public function send(#[Payload] string $message) : string
    {
        return "";
    }
}