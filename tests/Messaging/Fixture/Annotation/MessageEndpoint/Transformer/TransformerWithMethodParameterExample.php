<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Transformer;

use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Attribute\Transformer;

/**
 * licence Apache-2.0
 */
class TransformerWithMethodParameterExample
{
    #[Transformer('inputChannel', 'some-id', 'outputChannel', ['someReference'])]
    public function send(#[Payload] string $message): string
    {
        return '';
    }
}
