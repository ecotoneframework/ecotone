<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Api\Attribute\Converter;
use stdClass;

/**
 * licence Apache-2.0
 */
final class StdClassConverter
{
    #[Converter]
    public function from(string $data): stdClass
    {
        $stdClass = new stdClass();
        $stdClass->data = $data;

        return $stdClass;
    }
}
