<?php

namespace Ecotone\Messaging\Config\Container;

/**
 * licence Apache-2.0
 */
class MethodInterceptorsConfiguration
{
    /**
     * @param array<Definition|Reference> $beforeInterceptors
     * @param array<Definition|Reference> $aroundInterceptors
     * @param array<Definition|Reference> $afterInterceptors
     */
    public function __construct(
        private array $beforeInterceptors,
        private array $aroundInterceptors,
        private array $afterInterceptors,
    ) {
    }

    public static function createEmpty()
    {
        return new self([], [], []);
    }

    /**
     * @return array<Definition|Reference>
     */
    public function getBeforeInterceptors(): array
    {
        return $this->beforeInterceptors;
    }

    /**
     * @return array<Definition|Reference>
     */
    public function getAroundInterceptors(): array
    {
        return $this->aroundInterceptors;
    }

    /**
     * @return array<Definition|Reference>
     */
    public function getAfterInterceptors(): array
    {
        return $this->afterInterceptors;
    }
}
