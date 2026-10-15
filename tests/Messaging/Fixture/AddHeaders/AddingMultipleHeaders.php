<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\AddHeaders;

use Ecotone\Api\Attribute\AddHeader;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Delayed;
use Ecotone\Api\Attribute\Priority;
use Ecotone\Api\Attribute\RemoveHeader;
use Ecotone\Api\Attribute\TimeToLive;

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

    #[Delayed(expression: 'payload.delay')]
    #[AddHeader('token', expression: 'headers["token"]')]
    #[TimeToLive(expression: 'payload.timeToLive')]
    #[Asynchronous('async')]
    #[CommandHandler('addHeadersWithExpression', endpointId: 'addHeadersEndpointWithExpression')]
    public function withExpression(): void
    {

    }

    #[Delayed(1000, shouldReplaceExistingHeader: false)]
    #[TimeToLive(1001, shouldReplaceExistingHeader: false)]
    #[Asynchronous('async')]
    #[CommandHandler('keepHeaders', endpointId: 'keepHeadersEndpoint')]
    public function testKeepHeaders(): void
    {

    }

    #[TimeToLive(1001)]
    #[Asynchronous('async')]
    #[CommandHandler('keepDeliveryDelayHeader', endpointId: 'keepDeliveryDelayHeaderEndpoint')]
    public function testKeepDeliveryDelayHeader(): void
    {

    }

    #[Delayed(1000)]
    #[Asynchronous('async')]
    #[CommandHandler('keepTtlHeader', endpointId: 'keepTtlHeaderEndpoint')]
    public function testKeepTtlHeader(): void
    {

    }

    #[Delayed(expression: 'payload.delay')]
    #[Asynchronous('async')]
    #[CommandHandler('addHeadersWithStringDelayExpression', endpointId: 'addHeadersWithStringDelayExpressionEndpoint')]
    public function withStringDelayExpression(): void
    {

    }
}
