<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Api\Attribute\DelayedRetry;
use Ecotone\Api\Attribute\ErrorChannel;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Type;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
final class EnterpriseGatewayErrorChannelResolver implements GatewayErrorChannelResolver
{
    public function getErrorChannel(InterfaceToCall $interfaceToCall, array $endpointAnnotations, ?string $errorChannelName): ?string
    {
        if ($errorChannelName) {
            return $errorChannelName;
        }

        $this->assertNotBothErrorChannelAndDelayedRetry($interfaceToCall, $endpointAnnotations);

        foreach ($endpointAnnotations as $endpointAnnotation) {
            if ($endpointAnnotation->getClassName() === ErrorChannel::class) {
                return $endpointAnnotation->instance()->errorChannelName;
            }
            if ($endpointAnnotation->getClassName() === DelayedRetry::class) {
                return DelayedRetry::generateGatewayChannelName($interfaceToCall->getInterfaceName());
            }
        }

        /** @var ErrorChannel[] $errorChannel */
        $errorChannel = $interfaceToCall->getAnnotationsByImportanceOrder(Type::attribute(ErrorChannel::class));
        if ($errorChannel) {
            return $errorChannel[0]->errorChannelName;
        }

        /** @var DelayedRetry[] $delayedRetry */
        $delayedRetry = $interfaceToCall->getAnnotationsByImportanceOrder(Type::attribute(DelayedRetry::class));
        if ($delayedRetry) {
            return DelayedRetry::generateGatewayChannelName($interfaceToCall->getInterfaceName());
        }

        return null;
    }

    public function getErrorChannelRoutingSlip(InterfaceToCall $interfaceToCall, array $endpointAnnotations, string $requestChannelName): ?string
    {
        $this->assertNotBothErrorChannelAndDelayedRetry($interfaceToCall, $endpointAnnotations);

        foreach ($endpointAnnotations as $endpointAnnotation) {
            if ($endpointAnnotation->getClassName() === ErrorChannel::class
                || $endpointAnnotation->getClassName() === DelayedRetry::class) {
                return $requestChannelName;
            }
        }

        if ($interfaceToCall->getAnnotationsByImportanceOrder(Type::attribute(ErrorChannel::class))) {
            return $requestChannelName;
        }
        if ($interfaceToCall->getAnnotationsByImportanceOrder(Type::attribute(DelayedRetry::class))) {
            return $requestChannelName;
        }

        return null;
    }

    private function assertNotBothErrorChannelAndDelayedRetry(InterfaceToCall $interfaceToCall, array $endpointAnnotations): void
    {
        $hasErrorChannel = false;
        $hasDelayedRetry = false;
        foreach ($endpointAnnotations as $endpointAnnotation) {
            if ($endpointAnnotation->getClassName() === ErrorChannel::class) {
                $hasErrorChannel = true;
            } elseif ($endpointAnnotation->getClassName() === DelayedRetry::class) {
                $hasDelayedRetry = true;
            }
        }
        if ($interfaceToCall->getAnnotationsByImportanceOrder(Type::attribute(ErrorChannel::class))) {
            $hasErrorChannel = true;
        }
        if ($interfaceToCall->getAnnotationsByImportanceOrder(Type::attribute(DelayedRetry::class))) {
            $hasDelayedRetry = true;
        }
        if ($hasErrorChannel && $hasDelayedRetry) {
            throw ConfigurationException::create(
                "Gateway `{$interfaceToCall->getInterfaceName()}` declares both #[ErrorChannel] and #[DelayedRetry] — these are mutually exclusive. " .
                'Use #[ErrorChannel] to send failures to a channel you control, OR #[DelayedRetry] to have Ecotone manage the retry+dead-letter flow with a generated channel.'
            );
        }
    }
}
