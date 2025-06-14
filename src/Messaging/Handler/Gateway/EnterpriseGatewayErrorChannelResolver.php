<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Attribute\ErrorChannel;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * licence Enterprise
 */
final class EnterpriseGatewayErrorChannelResolver implements GatewayErrorChannelResolver
{
    public function getErrorChannel(InterfaceToCall $interfaceToCall, ?string $errorChannelName): ?string
    {
        if ($errorChannelName) {
            return $errorChannelName;
        }

        /** @var ErrorChannel[] $errorChannel */
        $errorChannel = $interfaceToCall->getAnnotationsByImportanceOrder(TypeDescriptor::create(ErrorChannel::class));

        return $errorChannel ? $errorChannel[0]->errorChannelName : null;
    }

    public function getErrorChannelRoutingSlip(InterfaceToCall $interfaceToCall, string $requestChannelName): ?string
    {
        /** @var ErrorChannel[] $errorChannelAttributes */
        $errorChannelAttributes = $interfaceToCall->getAnnotationsByImportanceOrder(TypeDescriptor::create(ErrorChannel::class));

        if ($errorChannelAttributes) {
            return $requestChannelName;
        }

        return null;
    }
}
