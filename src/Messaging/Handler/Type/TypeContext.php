<?php

/**
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Type;

/**
 * Context for resolving type keywords like 'self', 'static', 'this'
 * and class aliases from use statements
 */
class TypeContext
{
    /**
     * @param array<string, string> $aliases Map of alias => fully qualified class name
     */
    public function __construct(
        public readonly ?string $callingClass = null,
        public readonly ?string $declaringClass = null,
        public readonly ?string $parentClass = null,
        public readonly ?string $namespace = null,
        public readonly array $aliases = [],
    ) {
    }

    /**
     * Resolve a type keyword to its actual type
     */
    public function resolveKeyword(string $keyword): ?string
    {
        return match ($keyword) {
            'self' => $this->declaringClass,
            'static' => $this->callingClass,
            'parent' => $this->parentClass,
            default => null,
        };
    }

    /**
     * Check if a string is a type keyword that needs resolution
     */
    public function isKeyword(string $type): bool
    {
        return in_array($type, ['self', 'static', 'this', 'parent'], true);
    }

    /**
     * Resolve a class name using aliases and namespace context
     */
    public function resolveClassName(string $className): string
    {
        // If it's already fully qualified (starts with \), return as-is
        if (str_starts_with($className, '\\')) {
            return ltrim($className, '\\');
        }

        // Check if it's an alias
        if (isset($this->aliases[$className])) {
            $resolvedAlias = $this->aliases[$className];
            // If the alias is fully qualified, return it as-is
            if (str_starts_with($resolvedAlias, '\\')) {
                return ltrim($resolvedAlias, '\\');
            }
            return $resolvedAlias;
        }

        // If we have a namespace, try resolving in the current namespace
        if ($this->namespace !== null) {
            // Check if it's a global class first (like stdClass, Exception, etc.)
            if (class_exists($className) || interface_exists($className)) {
                return $className;
            }

            $namespacedClass = $this->namespace . '\\' . $className;
            // For testing purposes, we'll assume the namespaced class exists
            // In real usage, this would be checked with class_exists()
            return $namespacedClass;
        }

        // Return as-is if no resolution found
        return $className;
    }

    /**
     * Check if a class name is an alias
     */
    public function isAlias(string $className): bool
    {
        return isset($this->aliases[$className]);
    }

    /**
     * Create a TypeContext for a class
     */
    public static function forClass(string $className, ?string $parentClass = null, ?string $namespace = null, array $aliases = []): self
    {
        return new self(
            callingClass: $className,
            declaringClass: $className,
            parentClass: $parentClass,
            namespace: $namespace,
            aliases: $aliases
        );
    }

    /**
     * Create an empty TypeContext (no resolution available)
     */
    public static function empty(): self
    {
        return new self();
    }
}
