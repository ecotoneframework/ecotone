<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Support\Assert;

final class ExtensionObjectResolver
{
    private function __construct(){}

    /**
     * @template T
     * @param class-string<T> $className
     * @param object[] $extensionObjects
     * @return T
     */
    public static function resolveUnique(string $className, array $extensionObjects, object $default): object
    {
        $resolvedObject = null;
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof $className) {
                Assert::null($resolvedObject, sprintf("Extension object: `%s` needs to be unique, however was registered twice.", $className));
                $resolvedObject = $extensionObject;
            }
        }

        return $resolvedObject ?? $default;
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @param object[] $extensionObjects
     * @return T[]
     */
    public static function resolve(string $className, array $extensionObjects): array
    {
        $resolvedObjects = [];
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof $className) {
                $resolvedObjects[] = $extensionObject;
            }
        }

        return $resolvedObjects;
    }
}