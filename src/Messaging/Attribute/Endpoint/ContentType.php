<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute\Endpoint;

use Attribute;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\MessageHeaders;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class ContentType extends AddHeader
{
    public function __construct(string $contentType, private bool $replaceIfExists = false)
    {
        MediaType::parseMediaType($contentType);

        parent::__construct(MessageHeaders::CONTENT_TYPE, $contentType);
    }

    public function shouldReplaceExistingHeader(): bool
    {
        return $this->replaceIfExists;
    }
}
