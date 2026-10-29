<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Collector\Config;

use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\Collector\MessageCollectorChannelInterceptor;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\PrecedenceChannelInterceptor;

/**
 * licence Apache-2.0
 */
final class CollectorChannelInterceptorBuilder implements ChannelInterceptorBuilder
{
    public function __construct(private string $collectedChannel, private Reference $collectorStorageReference)
    {
    }

    public function relatedChannelName(): string
    {
        return $this->collectedChannel;
    }

    public function getPrecedence(): int
    {
        return PrecedenceChannelInterceptor::COLLECTOR_PRECEDENCE;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(
            MessageCollectorChannelInterceptor::class,
            [
                $this->collectorStorageReference,
                new Reference(LoggingGateway::class),
            ]
        );
    }

}
