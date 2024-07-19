<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;

use Ecotone\Messaging\Message;

/**
 * licence Apache-2.0
 */
interface MessageReturningGateway
{
    public function execute(string $replyMediaType): Message;

    public function executeWithMetadata(string $data, array $metadata): Message;

    public function executeWithMetadataWithDefault(string $data, array $metadata = []): Message;

    public function executeWithMetadataWithNull(string $data, ?array $metadata): Message;

    public function executeNoParameter(): Message;
}
