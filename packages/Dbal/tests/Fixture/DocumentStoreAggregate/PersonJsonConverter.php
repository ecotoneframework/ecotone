<?php

namespace Test\Ecotone\Dbal\Fixture\DocumentStoreAggregate;

use Ecotone\Messaging\Attribute\MediaTypeConverter;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;

use function json_decode;
use function json_encode;

#[MediaTypeConverter]
final class PersonJsonConverter implements Converter
{
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        if ($sourceMediaType->isCompatibleWith(MediaType::createApplicationXPHP())) {
            /** @var Person $source */
            return json_encode([
                'personId' => $source->getPersonId(),
                'name' => $source->getName(),
            ]);
        }

        $data = json_decode($source, true);
        return Person::register(new RegisterPerson($data['personId'], $data['name']));
    }

    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return true;
    }
}
