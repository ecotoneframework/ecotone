<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\MetadataPropagating;

use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\PropagateHeaders;
use Ecotone\Api\Messaging\Message;

/**
 * licence Apache-2.0
 */
interface FakeLoggingGateway
{
    #[MessageGateway('beforeLog')]
    #[PropagateHeaders(false)]
    public function logBefore(Message $message): void;

    #[MessageGateway('afterLog')]
    #[PropagateHeaders(false)]
    public function logAfter(Message $message): void;
}
