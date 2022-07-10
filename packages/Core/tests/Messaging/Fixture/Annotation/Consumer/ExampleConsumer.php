<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\Consumer;

use Ecotone\Messaging\Attribute\MessageConsumer;
use Ecotone\Messaging\Attribute\MessageEndpoint;

class ExampleConsumer
{
    #[MessageConsumer("someId")]
    public function some(string $body) : void
    {

    }
}