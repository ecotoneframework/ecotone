<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Gateway\Converter;

/**
 * Interface Serializer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @TODO 2.0 change namespace and name to SerializerGateway
 */
interface Serializer
{
    public function convertFromPHP($data, string $targetMediaType);

    public function convertToPHP($data, string $sourceMediaType, string $targetType);
}