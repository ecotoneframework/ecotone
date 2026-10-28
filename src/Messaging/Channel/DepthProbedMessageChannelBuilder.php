<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Api\ExtensionObject\MessageChannelBuilder;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface DepthProbedMessageChannelBuilder extends MessageChannelBuilder
{
    public function depthProbeGroup(): string;

    public function depthProbeDestination(): string;

    public function depthProbeDefinition(): Definition;
}
