<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Gateway\Converter;

use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Type;

/**
 * licence Apache-2.0
 */
class SerializerHandler
{
    public const MEDIA_TYPE = 'ecotone.serializer.media_type';
    public const TARGET_TYPE = 'ecotone.serializer.target_type';

    private ConversionService $conversionService;

    public function __construct(ConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    public function convertFromPHP($data, array $metadata)
    {
        $targetMediaType = MediaType::parseMediaType($metadata[self::MEDIA_TYPE]);

        return $this->conversionService->convert(
            $data,
            Type::createFromVariable($data),
            MediaType::createApplicationXPHP(),
            $targetMediaType->hasTypeParameter() ? Type::create($targetMediaType->getParameter(MediaType::TYPE_PARAMETER)) : Type::anything(),
            $targetMediaType
        );
    }

    public function convertToPHP($data, array $metadata)
    {
        $sourceMediaType = MediaType::parseMediaType($metadata[self::MEDIA_TYPE]);

        return $this->conversionService->convert(
            $data,
            $sourceMediaType->hasTypeParameter() ? Type::create($sourceMediaType->getParameter(MediaType::TYPE_PARAMETER)) : Type::createFromVariable($data),
            $sourceMediaType,
            Type::create($metadata[self::TARGET_TYPE]),
            MediaType::createApplicationXPHP()
        );
    }
}
