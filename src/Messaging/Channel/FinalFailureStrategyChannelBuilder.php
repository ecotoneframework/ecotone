<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Api\ExtensionObject\FinalFailureStrategy;
use Ecotone\Api\ExtensionObject\MessageChannelBuilder;

/**
 * licence Apache-2.0
 */
interface FinalFailureStrategyChannelBuilder extends MessageChannelBuilder
{
    public function getFinalFailureStrategy(): FinalFailureStrategy;

    public function getDefaultFinalFailureStrategy(): FinalFailureStrategy;
}
