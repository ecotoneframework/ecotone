<?php

namespace Test\Ecotone\Modelling\Fixture\RepositoryShortcut;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Modelling\Attribute\Identifier;

/**
 * licence Apache-2.0
 */
interface TwitterService
{
    #[MessageGateway('getContent')]
    public function getContent(#[Identifier] string $twitId): string;

    #[MessageGateway('changeContent')]
    public function changeContent(#[Identifier] string $twitId, #[Payload] string $content): void;
}
