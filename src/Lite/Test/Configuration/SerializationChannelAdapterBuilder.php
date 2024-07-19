<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Precedence;

/**
 * licence Apache-2.0
 */
final class SerializationChannelAdapterBuilder implements ChannelInterceptorBuilder
{
    public function __construct(private string $relatedChannel, private MediaType $targetMediaType)
    {
    }

    public function relatedChannelName(): string
    {
        return $this->relatedChannel;
    }

    public function getPrecedence(): int
    {
        return Precedence::DEFAULT_PRECEDENCE;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(SerializationChannelAdapter::class, [
            $this->targetMediaType,
            Reference::to(ConversionService::REFERENCE_NAME),
        ]);
    }
}
