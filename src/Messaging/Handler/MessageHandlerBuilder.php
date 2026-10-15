<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Stringable;

/**
 * Interface MessageHandlerBuilder
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface MessageHandlerBuilder extends CompilableBuilder, Stringable
{
    /**
     * @param string $inputChannelName
     *
     * @return static
     */
    public function withInputChannelName(string $inputChannelName);

    /**
     * @return string|null
     */
    public function getEndpointId(): ?string;

    /**
     * @param string $endpointId
     *
     * @return static
     */
    public function withEndpointId(string $endpointId);

    /**
     * @return string
     */
    public function getInputMessageChannelName(): string;
}
