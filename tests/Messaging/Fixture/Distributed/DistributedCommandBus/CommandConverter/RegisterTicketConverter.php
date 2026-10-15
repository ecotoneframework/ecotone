<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\CommandConverter;

use Ecotone\Api\Attribute\MediaTypeConverter;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Handler\Type;
use Messaging\Fixture\Distributed\DistributedCommandBus\Receiver\RegisterTicket;

#[MediaTypeConverter]
final class RegisterTicketConverter implements Converter
{
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        if ($targetType->equals(Type::create(RegisterTicket::class))) {
            return new RegisterTicket($source['ticketId']);
        }

        return [
            'ticketId' => $source->ticketId,
        ];
    }

    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return true;
    }
}
