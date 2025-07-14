<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Enterprise
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ErrorChannel
{
    /**
     * @param string $errorChannelName Name of the error channel to send Message too
     */
    public function __construct(
        public readonly string   $errorChannelName,
    ) {
        Assert::notNullAndEmpty($errorChannelName, 'Channel name can not be empty string');
    }
}
