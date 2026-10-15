<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;

/**
 * licence Apache-2.0
 */
final class ConsoleInvocationResolver
{
    public static function resolveConsolePrefix(ServiceConfiguration $serviceConfiguration): ?string
    {
        if (self::isPackageActive($serviceConfiguration, ModulePackageList::SYMFONY_PACKAGE)) {
            return 'bin/console';
        }

        if (self::isPackageActive($serviceConfiguration, ModulePackageList::LARAVEL_PACKAGE)) {
            return 'php artisan';
        }

        if (self::isPackageActive($serviceConfiguration, ModulePackageList::TEMPEST_PACKAGE)) {
            return './tempest';
        }

        return null;
    }

    private static function isPackageActive(ServiceConfiguration $serviceConfiguration, string $packageName): bool
    {
        if (! $serviceConfiguration->isModulePackageEnabled($packageName)) {
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
