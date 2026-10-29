<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

/**
 * Combined Message Channel whose source acts as an outbox published by a dedicated forwarding endpoint.
 * Implementations own the storage specific configuration, while Messaging core guards that the source
 * channel is claimed by a forwarding module and not consumed or reused as a plain combined channel.
 *
 * licence Enterprise
 */
interface OutboxForwardingChannel
{
    public function getReferenceName(): string;

    public function getSourceChannelName(): string;

    public function getEmbeddedSourceChannelBuilder(): ?MessageChannelBuilder;
}
