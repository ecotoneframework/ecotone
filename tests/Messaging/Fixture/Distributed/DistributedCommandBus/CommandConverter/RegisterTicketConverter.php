<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\CommandConverter;

use Ecotone\Messaging\Attribute\MediaTypeConverter;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Messaging\Fixture\Distributed\DistributedCommandBus\Receiver\RegisterTicket;

#[MediaTypeConverter]
final class RegisterTicketConverter implements Converter
{
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        if ($targetType->equals(TypeDescriptor::create(RegisterTicket::class))) {
            return new RegisterTicket($source['ticketId']);
        }

        return [
            'ticketId' => $source->ticketId,
        ];
    }

    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return true;
    }
}
