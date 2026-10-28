<?php

declare(strict_types=1);

namespace Ecotone\Modelling\MessageHandling\Distribution\Pulse;

use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Messaging\Config\BootstrapConfiguration;
use Ecotone\Messaging\Config\ConsoleInvocationResolver;

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
final class PulseFrameworkResolver
{
    private const FRAMEWORK_BY_PACKAGE = [
        ModulePackageList::SYMFONY_PACKAGE => 'symfony',
        ModulePackageList::LARAVEL_PACKAGE => 'laravel',
        ModulePackageList::TEMPEST_PACKAGE => 'tempest',
    ];

    public static function resolve(BootstrapConfiguration $bootstrapConfiguration): string
    {
        foreach (self::FRAMEWORK_BY_PACKAGE as $packageName => $framework) {
            if (ConsoleInvocationResolver::isPackageActive($bootstrapConfiguration, $packageName)) {
                return $framework;
            }
        }

        return 'lite';
    }
}
