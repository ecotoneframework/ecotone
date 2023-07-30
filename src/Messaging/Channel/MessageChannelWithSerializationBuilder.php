<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\MessageConverter\HeaderMapper;

interface MessageChannelWithSerializationBuilder extends MessageChannelBuilder
{
    public function getConversionMediaType(): ?MediaType;

    public function getHeaderMapper(): HeaderMapper;
}
