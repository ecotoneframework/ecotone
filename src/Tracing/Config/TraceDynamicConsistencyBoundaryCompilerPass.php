<?php

declare(strict_types=1);

namespace Ecotone\Tracing\Config;

use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Messaging\Config\Container\Compiler\CompilerPass;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Modelling\DecisionModel\DecisionModelBatchLoader;
use Ecotone\Tracing\MessageSpanAttributes;
use Ecotone\Tracing\TracedDecisionModelBatchLoader;
use Ecotone\Tracing\TracedEventStore;
use Ecotone\Tracing\Tracer;

use function str_starts_with;
use function strlen;
use function substr;

/**
 * licence Apache-2.0
 */
final class TraceDynamicConsistencyBoundaryCompilerPass implements CompilerPass
{
    private const BATCH_LOADER_REFERENCE_PREFIX = 'decisionModel.batchLoader.';

    public function process(ContainerBuilder $builder): void
    {
        foreach ($builder->getDefinitions() as $id => $definition) {
            if (! str_starts_with($id, self::BATCH_LOADER_REFERENCE_PREFIX) || ! $definition instanceof Definition || $definition->getClassName() !== DecisionModelBatchLoader::class) {
                continue;
            }

            $builder->replace($id, new Definition(TracedDecisionModelBatchLoader::class, [
                $definition,
                substr($id, strlen(self::BATCH_LOADER_REFERENCE_PREFIX)),
                new Reference(Tracer::class),
                new Reference(MessageSpanAttributes::class),
            ]));
        }

        if ($builder->has(EventStore::RAW_REFERENCE)) {
            $builder->replace(EventStore::RAW_REFERENCE, new Definition(TracedEventStore::class, [
                $builder->getDefinition(EventStore::RAW_REFERENCE),
                new Reference(Tracer::class),
            ]));
        }
    }
}
