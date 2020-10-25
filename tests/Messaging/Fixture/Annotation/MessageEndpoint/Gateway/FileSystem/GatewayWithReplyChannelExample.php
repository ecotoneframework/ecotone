<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway\FileSystem;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Payload;

interface GatewayWithReplyChannelExample
{
    #[MessageGateway("requestChannel", requiredInterceptorNames: ["dbalTransaction"])]
    public function buy(#[Payload] string $orderId): bool;
}