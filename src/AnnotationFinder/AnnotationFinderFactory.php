<?php

namespace Ecotone\AnnotationFinder;

use Ecotone\AnnotationFinder\AnnotationResolver\AttributeResolver;
use Ecotone\AnnotationFinder\FileSystem\AutoloadFileNamespaceParser;
use Ecotone\AnnotationFinder\FileSystem\FileSystemAnnotationFinder;

/**
 * licence Apache-2.0
 */
class AnnotationFinderFactory
{
    public static function createForAttributes(string $rootProjectPath, array $namespaceToSearchIn, string $environmentName = 'prod', string $directoryToDiscoverNamespaces = '', array $systemClassesToRegister = [], array $userClassesToRegister = [], bool $isRunningForTesting = false): FileSystemAnnotationFinder
    {
        return new FileSystemAnnotationFinder(
            new AttributeResolver(),
            new AutoloadFileNamespaceParser(),
            $rootProjectPath,
            $namespaceToSearchIn,
            $environmentName,
            $directoryToDiscoverNamespaces,
            $systemClassesToRegister,
            $userClassesToRegister,
            $isRunningForTesting
        );
    }
}
