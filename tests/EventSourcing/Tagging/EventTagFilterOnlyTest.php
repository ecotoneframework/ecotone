<?php

declare(strict_types=1);

namespace Test\Ecotone\EventSourcing\Tagging;

use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

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
final class EventTagFilterOnlyTest extends TestCase
{
    public function test_filter_only_tag_is_indexed_for_reads_but_never_causes_a_conflict(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [TenantScopedEventForFilterOnlyTest::class],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withFilterOnlyTags(['tenant'])])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        /** @var EventStore $eventStore */
        $eventStore = $ecotone->getGateway(EventStore::class);

        $loadedEvents = $eventStore->loadByCriteria(EventCriteria::tag('tenant', 'acme'));

        $eventStore->appendTo('ecotone_event_stream', [new TenantScopedEventForFilterOnlyTest('acme', 'x')]);
        $eventStore->appendTo('ecotone_event_stream', [new TenantScopedEventForFilterOnlyTest('acme', 'y')]);

        $eventStore->appendTo(
            'ecotone_event_stream',
            [new TenantScopedEventForFilterOnlyTest('acme', 'z')],
            $loadedEvents->appendCondition,
        );

        $this->assertCount(3, $eventStore->loadByCriteria(EventCriteria::tag('tenant', 'acme'))->events);
    }

    public function test_declaring_a_filter_only_tag_no_event_carries_is_rejected_at_bootstrap_naming_it_and_the_known_tags(): void
    {
        try {
            EcotoneLite::bootstrapFlowTesting(
                classesToResolve: [TenantScopedEventForFilterOnlyTest::class],
                configuration: ServiceConfiguration::createWithDefaults()
                    ->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withFilterOnlyTags(['tennant'])])
                    ->withEnabledModulePackageNames([]),
                licenceKey: LicenceTesting::VALID_LICENCE,
            );
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString("'tennant'", $exception->getMessage());
            $this->assertStringContainsString('tenant, itemId', $exception->getMessage());
        }
    }
}

final readonly class TenantScopedEventForFilterOnlyTest
{
    public function __construct(
        #[EventTag('tenant')] public string $tenantId,
        #[EventTag('itemId')] public string $itemId,
    ) {
    }
}
