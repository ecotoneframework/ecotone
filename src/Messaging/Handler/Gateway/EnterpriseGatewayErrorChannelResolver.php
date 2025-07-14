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
    public function getErrorChannel(InterfaceToCall $interfaceToCall, array $endpointAnnotations, ?string $errorChannelName): ?string
    {
        if ($errorChannelName) {
            return $errorChannelName;
        }

        foreach ($endpointAnnotations as $endpointAnnotation) {
            if ($endpointAnnotation->getClassName() === ErrorChannel::class) {
                return $endpointAnnotation->instance()->errorChannelName;
            }
        }

        /** @var ErrorChannel[] $errorChannel */
        $errorChannel = $interfaceToCall->getAnnotationsByImportanceOrder(TypeDescriptor::create(ErrorChannel::class));

        return $errorChannel ? $errorChannel[0]->errorChannelName : null;
    }

    public function getErrorChannelRoutingSlip(InterfaceToCall $interfaceToCall, array $endpointAnnotations, string $requestChannelName): ?string
    {
        /** @var ErrorChannel[] $errorChannelAttributes */
        $errorChannelAttributes = $interfaceToCall->getAnnotationsByImportanceOrder(TypeDescriptor::create(ErrorChannel::class));

        foreach ($endpointAnnotations as $endpointAnnotation) {
            if ($endpointAnnotation->getClassName() === ErrorChannel::class) {
                return $requestChannelName;
            }
        }

        if ($errorChannelAttributes) {
            return $requestChannelName;
        }

        return null;
    }
}
