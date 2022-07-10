<?php

namespace Test\Ecotone\Modelling\Fixture\RepositoryShortcut;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Modelling\Attribute\AggregateIdentifier;

interface TwitterService
{
    #[MessageGateway("getContent")]
    public function getContent(#[AggregateIdentifier] string $twitId): string;

    #[MessageGateway("changeContent")]
    public function changeContent(#[AggregateIdentifier] string $twitId, #[Payload] string $content): void;
}