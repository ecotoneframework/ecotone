<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Api\Attribute\Enterprise;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ReferenceSearchService;
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
 *
 * Enterprise features are licenced under separate Enterprise licence.
 * Therefore to make us of those features you've to obtain a licence.
 * For more details contact us at: sales@simplycodedsoftware.com
 */
final class LicenceDecider
{
    public function __construct(private bool $isOnEnterpriseLicence)
    {

    }

    public function hasEnterpriseLicence(): bool
    {
        return $this->isOnEnterpriseLicence;
    }

    public function isEnabledSpecificallyFor(InterfaceToCall|ClassDefinition $interfaceToCall): bool
    {
        if (! $this->hasEnterpriseLicence()) {
            return false;
        }

        $type = Type::attribute(Enterprise::class);
        if ($interfaceToCall instanceof ClassDefinition) {
            return $interfaceToCall->hasClassAnnotation($type);
        }

        return $interfaceToCall->hasAnnotation($type);
    }

    public static function prepareDefinition(string $className, string $openCoreServiceReference, string $enterpriseServiceReference): Definition
    {
        return new Definition(
            $className,
            [
                Reference::to(self::class),
                $openCoreServiceReference,
                $enterpriseServiceReference,
                Reference::to(ReferenceSearchService::class),
            ],
            factory: [self::class, 'decide']
        );
    }

    public static function decide(self $enterpriseModeDecider, string $openCoreService, string $enterpriseService, ReferenceSearchService $referenceSearchService): object
    {
        if ($enterpriseModeDecider->hasEnterpriseLicence()) {
            return $referenceSearchService->get($enterpriseService);
        }

        return $referenceSearchService->get($openCoreService);
    }
}
