<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Splitter;

use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\Attribute\Splitter;

/**
 * licence Apache-2.0
 */
class SplitterExample
{
    #[Splitter('inputChannel', 'testId', 'outputChannel', ['someReference'])]
    public function split(#[Payload] string $payload): array
    {
        return [];
    }
}
