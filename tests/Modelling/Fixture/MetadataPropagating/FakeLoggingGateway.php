<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\MetadataPropagating;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\PropagateHeaders;
use Ecotone\Messaging\Message;

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
