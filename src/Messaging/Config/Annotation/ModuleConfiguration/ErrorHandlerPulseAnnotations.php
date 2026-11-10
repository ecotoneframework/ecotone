<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\DelayedRetry;
use Ecotone\Api\Attribute\ErrorChannel;
use Ecotone\Api\ExtensionObject\ErrorHandlerConfiguration;
use Ecotone\Api\ExtensionObject\RetryTemplateBuilder;
use Ecotone\Messaging\Attribute\EndpointAnnotation;
use Ecotone\Messaging\Attribute\IdentifiedAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;

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
 *
 * @internal
 */
final class ErrorHandlerPulseAnnotations
{
    /**
     * @param list<array{string, ErrorHandlerConfiguration}> $endpointDelayedRetries
     * @param list<array{class-string, ErrorHandlerConfiguration}> $gatewayDelayedRetries
     * @param list<array{string, string}> $endpointErrorChannels
     */
    private function __construct(
        public readonly array $endpointDelayedRetries,
        public readonly array $gatewayDelayedRetries,
        public readonly array $endpointErrorChannels,
    ) {
    }

    public static function of(AnnotationFinder $annotationFinder): self
    {
        $endpointMethods = $annotationFinder->findAnnotatedMethods(EndpointAnnotation::class);
        $endpointDelayedRetries = [];
        $endpointErrorChannels = [];
        foreach ($annotationFinder->findAnnotatedMethods(Asynchronous::class) as $asynchronousMethod) {
            $delayedRetry = null;
            foreach ($asynchronousMethod->getAnnotationForMethod()->asynchronousExecution as $endpointAnnotation) {
                if ($endpointAnnotation instanceof DelayedRetry) {
                    $delayedRetry = $endpointAnnotation;
                } elseif ($endpointAnnotation instanceof ErrorChannel && $asynchronousMethod->hasMethodAnnotation(IdentifiedAnnotation::class)) {
                    $endpointErrorChannels[] = [$asynchronousMethod->getMethodAnnotationsWithType(IdentifiedAnnotation::class)[0]->getEndpointId(), $endpointAnnotation->errorChannelName];
                }
            }
            if ($delayedRetry === null) {
                continue;
            }
            foreach ($endpointMethods as $endpoint) {
                if ($endpoint->getClassName() === $asynchronousMethod->getClassName() && $endpoint->getMethodName() === $asynchronousMethod->getMethodName()) {
                    $endpointId = $endpoint->getAnnotationForMethod()->getEndpointId();
                    $endpointDelayedRetries[] = [$endpointId, self::configurationOf(DelayedRetry::generateChannelName($endpointId), $delayedRetry)];
                    break;
                }
            }
        }
        $gatewayDelayedRetries = [];
        foreach ($annotationFinder->findAnnotatedClasses(DelayedRetry::class) as $gatewayInterface) {
            $gatewayDelayedRetries[] = [$gatewayInterface, self::configurationOf(DelayedRetry::generateGatewayChannelName($gatewayInterface), AnnotatedDefinitionReference::getSingleAnnotationForClass($annotationFinder, $gatewayInterface, DelayedRetry::class))];
        }

        return new self(endpointDelayedRetries: $endpointDelayedRetries, gatewayDelayedRetries: $gatewayDelayedRetries, endpointErrorChannels: $endpointErrorChannels);
    }

    private static function configurationOf(string $errorChannelName, DelayedRetry $delayedRetry): ErrorHandlerConfiguration
    {
        $retryTemplate = new RetryTemplateBuilder($delayedRetry->initialDelayInMilliseconds, $delayedRetry->multiplier, $delayedRetry->maxDelayInMilliseconds, $delayedRetry->maxRetries);

        return $delayedRetry->deadLetterChannel === null
            ? ErrorHandlerConfiguration::create($errorChannelName, $retryTemplate)
            : ErrorHandlerConfiguration::createWithDeadLetterChannel($errorChannelName, $retryTemplate, $delayedRetry->deadLetterChannel);
    }
}
