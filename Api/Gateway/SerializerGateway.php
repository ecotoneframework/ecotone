<?php

declare(strict_types=1);

namespace Ecotone\Api\Gateway;

/**
 * licence Apache-2.0
 */
interface SerializerGateway
{
    public function convertFromPHP($data, string $targetMediaType);

    public function convertToPHP($data, string $sourceMediaType, string $targetType);
}
