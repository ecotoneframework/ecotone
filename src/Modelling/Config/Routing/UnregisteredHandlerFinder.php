<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config\Routing;

use Composer\Autoload\ClassLoader;
use Ecotone\Messaging\Attribute\EndpointAnnotation;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionNamedType;
use SplFileInfo;
use Throwable;

/**
 * licence Apache-2.0
 */
final class UnregisteredHandlerFinder
{
    private array $inspectedFiles = [];

    /**
     * @param class-string<EndpointAnnotation> $handlerAttribute
     * @return array{class-string, string}|null
     */
    public function find(string $routingKeyOrClass, string $handlerAttribute): ?array
    {
        try {
            foreach ($this->directoriesToSearch($routingKeyOrClass) as [$namespacePrefix, $directory]) {
                $handler = $this->findInDirectory($routingKeyOrClass, $handlerAttribute, $namespacePrefix, $directory);
                if ($handler !== null) {
                    return $handler;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * @return iterable<array{string, string}>
     */
    private function directoriesToSearch(string $routingKeyOrClass): iterable
    {
        $applicationPrefixes = $this->applicationPsr4Prefixes();

        if (! class_exists($routingKeyOrClass)) {
            foreach ($applicationPrefixes as [$prefix, $directory]) {
                yield [$prefix, $directory];
            }

            return;
        }

        $namespace = substr($routingKeyOrClass, 0, (int) strrpos($routingKeyOrClass, '\\'));
        while ($namespace !== '') {
            foreach ($applicationPrefixes as [$prefix, $directory]) {
                $namespaceWithSeparator = $namespace . '\\';
                if (str_starts_with($namespaceWithSeparator, $prefix)) {
                    $subDirectory = $directory . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, substr($namespaceWithSeparator, strlen($prefix)));
                    if (is_dir($subDirectory)) {
                        yield [$prefix, $directory];
                        yield [$namespaceWithSeparator, $subDirectory];
                    }
                }
            }
            $namespace = str_contains($namespace, '\\') ? substr($namespace, 0, (int) strrpos($namespace, '\\')) : '';
        }
    }

    /**
     * @return array<array{string, string}>
     */
    private function applicationPsr4Prefixes(): array
    {
        $prefixes = [];
        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            foreach ($loader->getPrefixesPsr4() as $prefix => $directories) {
                foreach ($directories as $directory) {
                    $realDirectory = realpath($directory);
                    if ($realDirectory === false || str_contains($realDirectory, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
                        continue;
                    }
                    $prefixes[] = [$prefix, $realDirectory];
                }
            }
        }

        usort($prefixes, fn (array $first, array $second) => strlen($second[0]) <=> strlen($first[0]));

        return $prefixes;
    }

    /**
     * @param class-string<EndpointAnnotation> $handlerAttribute
     * @return array{class-string, string}|null
     */
    private function findInDirectory(string $routingKeyOrClass, string $handlerAttribute, string $namespacePrefix, string $directory): ?array
    {
        $attributeShortName = substr($handlerAttribute, (int) strrpos($handlerAttribute, '\\') + 1);
        $searchedText = class_exists($routingKeyOrClass)
            ? substr($routingKeyOrClass, (int) strrpos($routingKeyOrClass, '\\') + 1)
            : $routingKeyOrClass;

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            $path = $file->getPathname();
            if ($file->getExtension() !== 'php' || isset($this->inspectedFiles[$path])) {
                continue;
            }
            $this->inspectedFiles[$path] = true;

            $content = (string) file_get_contents($path);
            if (! str_contains($content, $attributeShortName) || ! str_contains($content, $searchedText)) {
                continue;
            }

            $className = $namespacePrefix . str_replace(DIRECTORY_SEPARATOR, '\\', substr($path, strlen($directory) + 1, -4));
            $handlerMethod = $this->handlerMethodIn($className, $routingKeyOrClass, $handlerAttribute);
            if ($handlerMethod !== null) {
                return [$className, $handlerMethod];
            }
        }

        return null;
    }

    /**
     * @param class-string<EndpointAnnotation> $handlerAttribute
     */
    private function handlerMethodIn(string $className, string $routingKeyOrClass, string $handlerAttribute): ?string
    {
        try {
            if (! class_exists($className)) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        foreach ((new ReflectionClass($className))->getMethods() as $method) {
            foreach ($method->getAttributes($handlerAttribute) as $attribute) {
                $routingKey = $attribute->newInstance()->getInputChannelName();
                if ($routingKey !== '') {
                    if ($routingKey === $routingKeyOrClass) {
                        return $method->getName();
                    }

                    continue;
                }

                $firstParameterType = $method->getParameters()[0] ?? null;
                $type = $firstParameterType?->getType();
                if ($type instanceof ReflectionNamedType && $type->getName() === $routingKeyOrClass) {
                    return $method->getName();
                }
            }
        }

        return null;
    }
}
