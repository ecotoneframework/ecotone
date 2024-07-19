<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\AddHeaders;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\Endpoint\AddHeader;
use Ecotone\Messaging\Attribute\Endpoint\Delayed;
use Ecotone\Messaging\Attribute\Endpoint\Priority;
use Ecotone\Messaging\Attribute\Endpoint\RemoveHeader;
use Ecotone\Messaging\Attribute\Endpoint\TimeToLive;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * licence Apache-2.0
 */
final class AddingMultipleHeaders
{
    #[Delayed(1000)]
    #[AddHeader('token', '123')]
    #[TimeToLive(1001)]
    #[Priority(1)]
    #[RemoveHeader('user')]
    #[Asynchronous('async')]
    #[CommandHandler('addHeaders', endpointId: 'addHeadersEndpoint')]
    public function test(): void
    {

    }
}
