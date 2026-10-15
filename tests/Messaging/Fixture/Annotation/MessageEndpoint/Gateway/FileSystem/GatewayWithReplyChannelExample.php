<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway\FileSystem;

use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\Payload;

/**
 * licence Apache-2.0
 */
interface GatewayWithReplyChannelExample
{
    #[MessageGateway('requestChannel', requiredInterceptorNames: ['dbalTransaction'])]
    public function buy(#[Payload] string $orderId): bool;
}
