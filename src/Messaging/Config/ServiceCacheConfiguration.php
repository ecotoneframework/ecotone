<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * ServiceConfiguration can be dumped for given environment and then code can be moved/symlinked.
 * So cache directory have to be split from dumped configuration.
 * This class can be resolved from DI and promotes design to resolve the cache path during execution phase.
 */
final class ServiceCacheConfiguration implements DefinedObject
{
    public const REFERENCE_NAME = self::class;
    private const CACHE_DIRECTORY_SUFFIX = DIRECTORY_SEPARATOR . 'ecotone';

    private string $path;

    /**
     * @param bool $shouldUseCache
     */
    public function __construct(string $path, private bool $shouldUseCache)
    {
        $this->path = $path . self::CACHE_DIRECTORY_SUFFIX;
    }

    public static function noCache(): self
    {
        return new self(sys_get_temp_dir(), false);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function shouldUseCache(): bool
    {
        return $this->shouldUseCache;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->path, $this->shouldUseCache]);
    }
}
