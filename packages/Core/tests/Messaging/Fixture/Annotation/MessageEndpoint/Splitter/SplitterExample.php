<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Splitter;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Attribute\Splitter;

class SplitterExample
{
    #[Splitter("inputChannel", "testId", "outputChannel", ["someReference"])]
    public function split(#[Payload] string $payload) : array
    {
        return [];
    }
}