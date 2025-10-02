<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Attribute\Enterprise;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\Type;

/**
 * licence Enterprise
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
