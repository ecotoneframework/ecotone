<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Service\Gateway;

use Ecotone\Messaging\Attribute\ErrorChannel;
use Ecotone\Modelling\CommandBus;

/**
 * Recommended pattern: extend CommandBus and place #[ErrorChannel] on the
 * gateway interface. This wires the framework's ErrorChannelInterceptor at
 * the gateway boundary, so any interceptor stack registered on the gateway
 * (e.g. #[WithTransactional]) wraps the handler call. On failure, those
 * gateway-level interceptors fully unwind their effects (transaction rollback,
 * etc.) BEFORE the error message is captured to the configured error channel.
 *
 * The same pattern applies to EventBus, QueryBus, MessagePublisher and any
 * #[BusinessMethod] interface — placement must be on the entry-point.
 */
#[ErrorChannel('someErrorChannel')]
interface ErrorChannelCommandBus extends CommandBus
{
}
