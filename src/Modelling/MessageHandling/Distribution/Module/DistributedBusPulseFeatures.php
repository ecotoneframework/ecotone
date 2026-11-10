<?php

declare(strict_types=1);

namespace Ecotone\Modelling\MessageHandling\Distribution\Module;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Distributed;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Modelling\MessageHandling\Distribution\Pulse\PulseFeature;

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
final class DistributedBusPulseFeatures
{
    /**
     * @return AnnotatedFinding[]
     */
    public static function distributedHandlersOf(AnnotationFinder $annotationFinder): array
    {
        return array_values(array_filter(
            [...$annotationFinder->findAnnotatedMethods(CommandHandler::class), ...$annotationFinder->findAnnotatedMethods(EventHandler::class)],
            static fn (AnnotatedFinding $registration): bool => $registration->hasAnnotation(Distributed::class),
        ));
    }

    /**
     * @param object[] $serviceExtensions
     * @param AnnotatedFinding[] $distributedRegistrations
     * @return PulseFeature[]
     */
    public static function reportedFor(array $serviceExtensions, array $distributedRegistrations): array
    {
        if (! PulseFeature::isReportedFor($serviceExtensions)) {
            return [];
        }

        $distributedHandlers = array_map(PulseFeature::handlerOf(...), $distributedRegistrations);

        return [new PulseFeature(
            package: ModulePackageList::CORE_PACKAGE,
            name: 'Distributed Bus',
            enabled: $distributedHandlers !== [],
            settings: ['Distributed handlers' => (string) count($distributedHandlers)],
            handlers: $distributedHandlers,
            hint: $distributedHandlers === []
                ? 'No handler of this service is marked #[Distributed], so other services cannot send it commands or events over the Distributed Bus. It can still send to them.'
                : sprintf('Other services can send this service the %d commands and events of the handlers listed here over the Distributed Bus, which routes them by the DistributedServiceMap of each sender. The Topology page shows the whole map.', count($distributedHandlers)),
            configuration: "#[Distributed] next to #[CommandHandler('order.place')] or #[EventHandler('order.placed')], and DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'orders', channelName: 'orders') in the sending service",
        )];
    }
}
