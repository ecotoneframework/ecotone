<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Api\ExtensionObject\ModulePackageList;

/**
 * licence Apache-2.0
 */
final class ConsoleInvocationResolver
{
    public static function resolveConsolePrefix(BootstrapConfiguration $bootstrapConfiguration): ?string
    {
        if (self::isPackageActive($bootstrapConfiguration, ModulePackageList::SYMFONY_PACKAGE)) {
            return 'bin/console';
        }

        if (self::isPackageActive($bootstrapConfiguration, ModulePackageList::LARAVEL_PACKAGE)) {
            return 'php artisan';
        }

        if (self::isPackageActive($bootstrapConfiguration, ModulePackageList::TEMPEST_PACKAGE)) {
            return './tempest';
        }

        return null;
    }

    private static function isPackageActive(BootstrapConfiguration $bootstrapConfiguration, string $packageName): bool
    {
        if (! $bootstrapConfiguration->isModulePackageEnabled($packageName)) {
            return false;
        }

        foreach (ModuleClassList::forPackage($packageName) as $moduleClass) {
            if (class_exists($moduleClass) || interface_exists($moduleClass)) {
                return true;
            }
        }

        return false;
    }
}
