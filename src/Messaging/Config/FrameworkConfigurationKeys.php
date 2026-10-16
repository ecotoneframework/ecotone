<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

/**
 * licence Apache-2.0
 */
final class FrameworkConfigurationKeys
{
    public const ENABLED_MODULE_PACKAGE_NAMES = 'enabledModulePackageNames';
    private const SKIPPED_MODULE_PACKAGE_NAMES = 'skippedModulePackageNames';
    private const MODULE_PACKAGES = 'modulePackages';
    private const MOVED_TO_SERVICE_CONFIGURATION = [
        'serviceName' => "withServiceName('my_service')",
        'defaultSerializationMediaType' => "withDefaultSerializationMediaType('application/json')",
        'defaultErrorChannel' => "withDefaultErrorChannel('errorChannel')",
        'defaultMemoryLimit' => 'withConsumerMemoryLimit(256)',
        'defaultConnectionExceptionRetry' => 'withConnectionRetryTemplate(RetryTemplateBuilder::exponentialBackOff(1000, 3)->maxRetries(5))',
        'headersInLogContext' => "withHeadersInLogContext(['userId'])",
    ];
    private const FAIL_FAST = 'failFast';
    private const CACHE_CONFIGURATION = 'cacheConfiguration';

    /**
     * @param array<int|string> $givenKeys
     * @param string[] $recognisedKeys
     */
    public static function refuseUnrecognisedKeys(array $givenKeys, array $recognisedKeys, string $configurationLocation): void
    {
        foreach ($givenKeys as $givenKey) {
            if (! in_array((string) $givenKey, $recognisedKeys, true)) {
                throw ConfigurationException::create(self::refusalFor(
                    unrecognisedKey: (string) $givenKey,
                    recognisedKeys: $recognisedKeys,
                    configurationLocation: $configurationLocation,
                ));
            }
        }
    }

    /**
     * @param string[] $recognisedKeys
     */
    private static function refusalFor(string $unrecognisedKey, array $recognisedKeys, string $configurationLocation): string
    {
        sort($recognisedKeys);
        $recognisedKeyList = implode(', ', $recognisedKeys);
        $enabledKey = self::ENABLED_MODULE_PACKAGE_NAMES;
        $readKeys = "The keys Ecotone reads in {$configurationLocation} are: {$recognisedKeyList}.";

        if (isset(self::MOVED_TO_SERVICE_CONFIGURATION[$unrecognisedKey])) {
            $builderCall = self::MOVED_TO_SERVICE_CONFIGURATION[$unrecognisedKey];

            return "Ecotone does not recognise the `{$unrecognisedKey}` key in {$configurationLocation}. "
                . "Since Ecotone 2.0 it is set on ServiceConfiguration, returned from a #[ServiceContext] method: ServiceConfiguration::createWithDefaults()->{$builderCall}. "
                . "Move it there and remove the key. {$readKeys}";
        }

        return match ($unrecognisedKey) {
            self::SKIPPED_MODULE_PACKAGE_NAMES => "Ecotone does not recognise the `{$unrecognisedKey}` key in {$configurationLocation}. "
                . "Since Ecotone 2.0 it is `{$enabledKey}`, and its meaning is inverted: `{$unrecognisedKey}` listed the module packages to skip, `{$enabledKey}` lists the module packages to load, so moving the same list over unchanged would load exactly the packages you meant to skip. "
                . "Either list the module packages to load under `{$enabledKey}` as ModulePackageList constants (Core, which includes Asynchronous, always loads), or remove the key to load every installed module package. "
                . $readKeys,
            self::MODULE_PACKAGES => "Ecotone does not recognise the `{$unrecognisedKey}` key in {$configurationLocation}. "
                . "It is now `{$enabledKey}`. `{$unrecognisedKey}` already listed the module packages to load, so rename the key and keep its list - unless that list was carried over from 1.x `" . self::SKIPPED_MODULE_PACKAGE_NAMES . '`, which listed the module packages to skip and has to be inverted into the module packages to load. '
                . 'Or remove the key to load every installed module package. '
                . $readKeys,
            self::FAIL_FAST => "Ecotone does not recognise the `{$unrecognisedKey}` key in {$configurationLocation}. "
                . 'Since Ecotone 2.0 it has no effect: every configuration mistake is refused at bootstrap. Remove the key. '
                . $readKeys,
            self::CACHE_CONFIGURATION => "Ecotone does not recognise the `{$unrecognisedKey}` key in {$configurationLocation}. "
                . 'Since Ecotone 2.0 the container cache mode is derived: the dumped container is reused as is in the production environment or when the framework caches its own configuration, and rebuilt whenever a scanned file changes otherwise. Remove the key. '
                . $readKeys,
            default => "Ecotone does not recognise the `{$unrecognisedKey}` key in {$configurationLocation}, so it would have no effect. "
                . "Remove it, or correct it to one of the keys Ecotone reads in {$configurationLocation}: {$recognisedKeyList}.",
        };
    }
}
