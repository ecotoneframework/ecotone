<?php

declare(strict_types=1);

namespace Ecotone\AnnotationFinder\FileSystem;

/**
 * Class GetUsedPathsFromAutoload
 * @package Ecotone\Messaging\Config\Annotation
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class AutoloadFileNamespaceParser implements AutoloadNamespaceParser
{
    /**
     * @param array $autoloadData
     * @param string $catalogToLoad
     * @return array
     */
    public function getNamespacesForGivenCatalog(array $autoloadData, string $catalogToLoad): array
    {
        $namespaces = [];
        if (isset($autoloadData['psr-4'])) {
            foreach ($autoloadData['psr-4'] as $autoloadNamespace => $paths) {
                if (! is_array($paths)) {
                    $paths = [$paths];
                }

                foreach ($paths as $path) {
                    if (substr($path, 0, strlen($catalogToLoad)) === $catalogToLoad) {
                        $namespaces[] = ltrim(rtrim($autoloadNamespace, '\\'), '\\');
                    }
                }
            }
        }
        if (isset($autoloadData['psr-0'])) {
            foreach ($autoloadData['psr-0'] as $autoloadNamespace => $paths) {
                if (! is_array($paths)) {
                    $paths = [$paths];
                }

                foreach ($paths as $path) {
                    if (substr($path, 0, strlen($catalogToLoad)) === $catalogToLoad) {
                        $namespaces[] = ltrim(rtrim($autoloadNamespace, '\\'), '\\');
                    }
                }
            }
        }

        return array_unique(array_filter($namespaces, function (string $namespace) {
            return trim($namespace) !== '';
        }));
    }

    /**
     * @param array $requiredNamespaces
     * @param array $autoload
     *
     * @param bool $autoloadPsr4
     * @return array
     */
    public function getFor(array $requiredNamespaces, array $autoload, bool $autoloadPsr4): array
    {
        $paths = [];
        foreach ($requiredNamespaces as $requiredNamespace) {
            $requiredNamespace = trim($requiredNamespace, '\\');
            $requiredNamespaceSplit = explode('\\', $requiredNamespace);

            foreach ($autoload as $namespace => $namespacePaths) {
                $namespace = trim($namespace, '\\');
                foreach ($namespacePaths as $namespacePath) {
                    $suffixPath = $namespacePath . '\\';
                    $namespaceSplit = explode('\\', $namespace);
                    $isPartOfRequiredNamespace = true;
                    foreach ($requiredNamespaceSplit as $index => $requiredNamespacePart) {
                        if (! isset($namespaceSplit[$index])) {
                            $suffixPath .= $requiredNamespacePart . '\\';
                            continue;
                        }

                        if ($requiredNamespacePart !== $namespaceSplit[$index]) {
                            $isPartOfRequiredNamespace = false;
                        }
                    }
                    if (! $isPartOfRequiredNamespace) {
                        continue;
                    }

                    if (! $autoloadPsr4) {
                        $suffixPath = $namespacePath . '\\' . $namespace;
                    }

                    $paths[] = str_replace('\\', '/', rtrim($suffixPath, '\\'));
                }
            }
        }

        return array_unique($paths);
    }
}
