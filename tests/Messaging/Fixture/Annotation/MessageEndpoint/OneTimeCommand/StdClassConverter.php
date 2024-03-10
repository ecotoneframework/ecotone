<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Attribute\Converter;
use stdClass;

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
