<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\Serialization;

use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\PrecedenceChannelInterceptor;

/**
 * licence Apache-2.0
 */
final class OutboundSerializationChannelBuilder implements ChannelInterceptorBuilder
{
    public function __construct(
        private string $relatedChannel,
        private HeaderMapper $headerMapper,
        private ?MediaType $channelConversionMediaType
    ) {
    }

    public function relatedChannelName(): string
    {
        return $this->relatedChannel;
    }

    public function getPrecedence(): int
    {
        return PrecedenceChannelInterceptor::MESSAGE_SERIALIZATION;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(OutboundSerializationChannelInterceptor::class, [
            new Definition(OutboundMessageConverter::class, [
                $this->headerMapper,
                $this->channelConversionMediaType ?: MediaType::parseMediaType($builder->getServiceConfiguration()->getDefaultSerializationMediaType()),
            ]),
            Reference::to(ConversionService::REFERENCE_NAME),
        ]);
    }
}
