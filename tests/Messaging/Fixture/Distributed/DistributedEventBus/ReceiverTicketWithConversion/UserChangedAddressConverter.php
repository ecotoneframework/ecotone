<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicketWithConversion;

use Ecotone\Api\Attribute\MediaTypeConverter;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Handler\Type;

#[MediaTypeConverter]
final class UserChangedAddressConverter implements Converter
{
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        if ($targetType->equals(Type::create(UserChangedAddress::class))) {
            return new UserChangedAddress($source['userId']);
        }

        return [
            'userId' => $source->userId,
        ];
    }

    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return true;
    }
}
