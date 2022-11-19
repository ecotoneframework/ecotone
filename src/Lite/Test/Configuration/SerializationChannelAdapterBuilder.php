<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Precedence;

final class SerializationChannelAdapterBuilder implements ChannelInterceptorBuilder
{
    public function __construct(private string $relatedChannel, private MediaType $targetMediaType) {}

    public function relatedChannelName(): string
    {
        return $this->relatedChannel;
    }

    public function getRequiredReferenceNames(): array
    {
        return [];
    }

    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [];
    }

    public function getPrecedence(): int
    {
        return Precedence::DEFAULT_PRECEDENCE;
    }

    public function build(ReferenceSearchService $referenceSearchService): ChannelInterceptor
    {
        return new SerializationChannelAdapter(
            $this->targetMediaType,
            $referenceSearchService->get(ConversionService::REFERENCE_NAME)
        );
    }
}