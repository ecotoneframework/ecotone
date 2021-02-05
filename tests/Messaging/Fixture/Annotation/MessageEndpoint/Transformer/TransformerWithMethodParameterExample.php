<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Transformer;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Attribute\Transformer;

class TransformerWithMethodParameterExample
{
    #[Transformer("inputChannel", "some-id", "outputChannel", ["someReference"])]
    public function send(#[Payload] string $message) : string
    {
        return "";
    }
}