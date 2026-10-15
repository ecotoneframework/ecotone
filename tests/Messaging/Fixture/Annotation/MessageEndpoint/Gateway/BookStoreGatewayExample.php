<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway;

use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Headers;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\Payload;

/**
 * licence Apache-2.0
 */
interface BookStoreGatewayExample
{
    #[MessageGateway(
        requestChannel: 'requestChannel',
        errorChannel: 'errorChannel',
        requiredInterceptorNames: ['dbalTransaction'],
        replyTimeoutInMilliseconds: 100,
        replyContentType: 'application/json'
    )]
    public function rent(#[Payload('upper(value)')] string $bookNumber, #[Header('rentDate')] string $rentTill, #[Header('cost')] int $cost, #[Headers] array $data): bool;
}
