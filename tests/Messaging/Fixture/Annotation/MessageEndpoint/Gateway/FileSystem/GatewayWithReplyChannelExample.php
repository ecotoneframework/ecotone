<?php
declare(strict_types=1);

namespace Ecotone\Tests\Messaging\Fixture\Annotation\MessageEndpoint\Gateway\FileSystem;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\Parameter\Payload;

interface GatewayWithReplyChannelExample
{
    #[MessageGateway("requestChannel", requiredInterceptorNames: ["dbalTransaction"])]
    public function buy(#[Payload] string $orderId): bool;
}