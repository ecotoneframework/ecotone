<?php

declare(strict_types=1);

namespace Ecotone\Tracing\Config;

use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\PrecedenceChannelInterceptor;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Tracing\MessageSpanAttributes;
use Ecotone\Tracing\Tracer;
use Ecotone\Tracing\TracingChannelInterceptor;

/**
 * licence Apache-2.0
 */
final class TracingChannelInterceptorBuilder implements ChannelInterceptorBuilder
{
    public function __construct(private string $channelName)
    {
    }

    public function relatedChannelName(): string
    {
        return $this->channelName;
    }

    public function getPrecedence(): int
    {
        return PrecedenceChannelInterceptor::TRACING_PRECEDENCE;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(TracingChannelInterceptor::class, [
            $this->channelName,
            new Reference(Tracer::class),
            new Reference(MessageBuilder::class),
            new Reference(MessageSpanAttributes::class),
        ]);
    }
}
