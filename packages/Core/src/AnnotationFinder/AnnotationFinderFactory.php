<?php


namespace Ecotone\AnnotationFinder;


use Ecotone\AnnotationFinder\AnnotationResolver\AttributeResolver;
use Ecotone\AnnotationFinder\AnnotationResolver\CombinedResolver;
use Ecotone\AnnotationFinder\AnnotationResolver\DoctrineAnnotationResolver;
use Ecotone\AnnotationFinder\AnnotationResolver\OnlyAvailableResolver;
use Ecotone\AnnotationFinder\FileSystem\AutoloadFileNamespaceParser;
use Ecotone\AnnotationFinder\FileSystem\FileSystemAnnotationFinder;

class AnnotationFinderFactory
{
    public static function createForAttributes(string $rootProjectPath, array $namespaceToSearchIn, string $environmentName = "prod", string $directoryToDiscoverNamespaces = ""): AnnotationFinder
    {
        return new FileSystemAnnotationFinder(
            new AttributeResolver(),
            new AutoloadFileNamespaceParser(),
            $rootProjectPath,
            $namespaceToSearchIn,
            $environmentName,
            $directoryToDiscoverNamespaces
        );
    }
}