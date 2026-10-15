<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Support\LicensingException;
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
final class DecisionModelLicenceTest extends TestCase
{
    public function test_bootstrap_throws_licensing_exception_when_decision_model_used_without_enterprise_licence(): void
    {
        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [ModelForLicenceTest::class, TaggedEventForDecisionModelLicenceTest::class],
            configuration: ServiceConfiguration::createWithDefaults()->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()])
                ->withEnabledModulePackageNames([]),
        );
    }
}

final readonly class TaggedEventForDecisionModelLicenceTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
    ) {
    }
}

#[DecisionModel]
final class ModelForLicenceTest
{
    #[Apply]
    public function when(TaggedEventForDecisionModelLicenceTest $event): void
    {
    }
}
