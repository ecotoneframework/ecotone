<?php

namespace Ecotone\AnnotationFinder\FileSystem;

/**
 * licence Apache-2.0
 */
class InMemoryAutoloadNamespaceParser implements AutoloadNamespaceParser
{
    /**
     * @var array
     */
    private $parsedNamespaces;
    /**
     * @var array
     */
    private $parsedPaths;

    private function __construct(array $parsedNamespaces, array $parsedPaths)
    {
        $this->parsedNamespaces = $parsedNamespaces;
        $this->parsedPaths = $parsedPaths;
    }

    public static function createWith(array $parsedNamespaces, array $parsedPaths): self
    {
        return new self($parsedNamespaces, $parsedPaths);
    }

    public static function createEmpty(): self
    {
        return new self([], []);
    }

    /**
     * @inheritDoc
     */
    public function getNamespacesForGivenCatalog(array $autoloadData, string $catalogToLoad): array
    {
        return $this->parsedNamespaces;
    }

    public function getFor(array $requiredNamespaces, array $autoload, bool $autoloadPsr4): array
    {
        return $this->parsedPaths;
    }
}
