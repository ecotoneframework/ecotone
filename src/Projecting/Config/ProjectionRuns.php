<?php

declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Projecting\ProjectionBackfill;
use Ecotone\Api\Projecting\ProjectionRebuild;
use Ecotone\Messaging\Attribute\EndpointAnnotation;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Projecting\ProjectionOperationEndpoint;
use Ecotone\Projecting\ProjectionRun;

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
final class ProjectionRuns
{
    /**
     * @return list<ProjectionRun>
     */
    public static function fromAnnotations(AnnotationFinder $annotationFinder): array
    {
        $runs = [];
        foreach ($annotationFinder->findAnnotatedClasses(Projection::class) as $projectionClassName) {
            $projectionName = $annotationFinder->getAttributeForClass($projectionClassName, Projection::class)->name;
            foreach ([ProjectionOperationEndpoint::REBUILD => ProjectionRebuild::class, ProjectionOperationEndpoint::BACKFILL => ProjectionBackfill::class] as $operation => $attributeClassName) {
                $attribute = $annotationFinder->findAttributeForClass($projectionClassName, $attributeClassName);
                if ($attribute?->asyncChannelName === null) {
                    continue;
                }
                $runs[] = new ProjectionRun(
                    projectionName: $projectionName,
                    operation: $operation,
                    asyncChannelName: $attribute->asyncChannelName,
                    batchCompletionChannelName: ProjectionOperationEndpoint::batchCompletionChannelOf($attribute->batchCompletionChannelName, $attribute->asyncChannelName),
                );
            }
        }

        return $runs;
    }

    /**
     * @param list<ProjectionRun> $runs
     */
    public static function refuseEndpointIdsTakenByHandlers(AnnotationFinder $annotationFinder, array $runs): void
    {
        if ($runs === []) {
            return;
        }
        $runsByEndpointId = [];
        foreach ($runs as $run) {
            $runsByEndpointId[$run->endpointId()] = $run;
        }
        foreach ([...$annotationFinder->findAnnotatedMethods(EndpointAnnotation::class), ...$annotationFinder->findAnnotatedMethods(EventHandler::class)] as $registration) {
            $endpointId = $registration->getAnnotationForMethod()->getEndpointId();
            if (! isset($runsByEndpointId[$endpointId])) {
                continue;
            }
            $run = $runsByEndpointId[$endpointId];
            throw ConfigurationException::create("Projection '{$run->projectionName}' tracks its asynchronous {$run->operation} under endpointId '{$endpointId}', which the handler {$registration->getClassName()}::{$registration->getMethodName()} also uses. Give that handler another endpointId, rename the projection, or remove asyncChannelName from {$run->attributeName()}.");
        }
    }
}
