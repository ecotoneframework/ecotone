<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config\InstantRetry;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\InstantRetry;
use Ecotone\Messaging\Attribute\IdentifiedAnnotation;
use Ecotone\Messaging\Attribute\MessageConsumer;
use Ecotone\Modelling\Config\DatabaseTransaction\AsynchronousHandlersWithoutTransaction;

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
final class InstantRetryPulseAnnotations
{
    /**
     * @param array<string, InstantRetry> $commandBusOverrides
     * @param list<array{string, InstantRetry}> $endpointOverrides
     * @param list<string> $handlersWithoutTransaction
     */
    private function __construct(
        public readonly array $commandBusOverrides,
        public readonly array $endpointOverrides,
        public readonly array $handlersWithoutTransaction,
    ) {
    }

    public static function of(AnnotationFinder $annotationFinder): self
    {
        $commandBusOverrides = [];
        foreach ($annotationFinder->findAnnotatedClasses(InstantRetry::class) as $commandBusInterface) {
            $commandBusOverrides[$commandBusInterface] = $annotationFinder->getAttributeForClass($commandBusInterface, InstantRetry::class);
        }
        $endpointOverrides = [];
        foreach ($annotationFinder->findAnnotatedMethods(InstantRetry::class) as $annotatedMethod) {
            if ($annotatedMethod->hasMethodAnnotation(MessageConsumer::class)) {
                $endpointOverrides[] = [$annotatedMethod->getMethodAnnotationsWithType(MessageConsumer::class)[0]->getEndpointId(), $annotatedMethod->getAnnotationForMethod()];
            }
        }
        $handlersWithoutTransaction = [];
        foreach (AsynchronousHandlersWithoutTransaction::in($annotationFinder) as $annotatedMethod) {
            if ($annotatedMethod->hasMethodAnnotation(IdentifiedAnnotation::class)) {
                $handlersWithoutTransaction[] = $annotatedMethod->getMethodAnnotationsWithType(IdentifiedAnnotation::class)[0]->getEndpointId();
            }
        }

        return new self(commandBusOverrides: $commandBusOverrides, endpointOverrides: $endpointOverrides, handlersWithoutTransaction: $handlersWithoutTransaction);
    }
}
