<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\Consumer;

use Ecotone\Messaging\Attribute\MessageConsumer;

/**
 * licence Apache-2.0
 */
class ExampleConsumer
{
    #[MessageConsumer('someId')]
    public function some(string $body): void
    {
    }
}
