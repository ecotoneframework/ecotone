<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Attribute\ErrorChannel;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\LicensingException;

/**
 * licence Apache-2.0
 */
final class StandardGatewayErrorChannelResolver implements GatewayErrorChannelResolver
{
    public function getErrorChannel(InterfaceToCall $interfaceToCall, array $endpointAnnotations, ?string $errorChannelName): ?string
    {
        $errorChannelAttributes = $interfaceToCall->getAnnotationsByImportanceOrder(Type::attribute(ErrorChannel::class));
        if ($errorChannelAttributes) {
            throw LicensingException::create('ErrorChannel attribute is available only as part of Ecotone Enterprise');
        }

        return $errorChannelName;
    }

    public function getErrorChannelRoutingSlip(InterfaceToCall $interfaceToCall, array $endpointAnnotations, string $requestChannelName): ?string
    {
        return null;
    }
}
