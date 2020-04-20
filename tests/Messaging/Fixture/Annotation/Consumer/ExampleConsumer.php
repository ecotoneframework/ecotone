<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\Consumer;

use Ecotone\Messaging\Annotation\MessageConsumer;
use Ecotone\Messaging\Annotation\MessageEndpoint;

/**
 * @MessageEndpoint()
 */
class ExampleConsumer
{
    /**
     * @MessageConsumer(endpointId="someId")
     */
    public function some(string $body) : void
    {

    }
}