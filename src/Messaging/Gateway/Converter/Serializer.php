<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Gateway\Converter;

/**
 * Interface Serializer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Serializer
{
    public function convertFromPHP($data, string $mediaType);
}