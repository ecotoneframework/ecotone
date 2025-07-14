<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * licence Apache-2.0
 */
interface GatewayErrorChannelResolver
{
    /**
     * Resolves the error channel name for a gateway method
     *
     * @param InterfaceToCall $interfaceToCall The interface method being called
     * @param AttributeDefinition[] $endpointAnnotations The endpoint annotations
     * @param string|null $errorChannelName The error channel name set via withErrorChannel()
     * @return string|null The resolved error channel name or null if no error channel
     */
    public function getErrorChannel(InterfaceToCall $interfaceToCall, array $endpointAnnotations, ?string $errorChannelName): ?string;

    /**
     * Resolves the error channel routing slip for a gateway method
     *
     * @param InterfaceToCall $interfaceToCall The interface method being called
     * @param AttributeDefinition[] $endpointAnnotations The endpoint annotations
     * @param string $requestChannelName The request channel name
     * @return string|null The routing slip channel name or null if no routing slip needed
     */
    public function getErrorChannelRoutingSlip(InterfaceToCall $interfaceToCall, array $endpointAnnotations, string $requestChannelName): ?string;
}
