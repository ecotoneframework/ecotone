<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;

interface BookStoreGatewayExample
{
    #[MessageGateway(
        requestChannel: "requestChannel",
        errorChannel: "errorChannel",
        requiredInterceptorNames: ["dbalTransaction"],
        replyTimeoutInMilliseconds: 100,
        replyContentType: "application/json"
    )]
    public function rent(#[Payload("upper(value)")] string $bookNumber, #[Header("rentDate")] string $rentTill, #[Header("cost")] int $cost, #[Headers] array $data): bool;
}