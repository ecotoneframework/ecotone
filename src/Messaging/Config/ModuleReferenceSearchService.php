<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class ModuleReferenceSearchService
 * @package Ecotone\Messaging\Config
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class ModuleReferenceSearchService
{
    public const REFERENCE_NAME = 'moduleReferenceSearchService';

    /**
     * @var object[]
     */
    private array $moduleReferences = [];

    /**
     * ModuleReferenceSearchService constructor.
     *
     * @param object[] $moduleReferences
     */
    private function __construct(array $moduleReferences)
    {
        $this->moduleReferences = $moduleReferences;
    }

    /**
     * @return ModuleReferenceSearchService
     */
    public static function createEmpty(): self
    {
        return new self([]);
    }

    /**
     * @param string $referenceName
     * @param object $object
     */
    public function store(string $referenceName, $object): void
    {
        $this->moduleReferences[$referenceName] = $object;
    }

    /**
     * @param string $referenceName
     *
     * @throws MessagingException
     */
    public function retrieveRequired(string $referenceName): ?object
    {
        if (! $this->retrieve($referenceName)) {
            throw InvalidArgumentException::create("Reference with name {$referenceName} does not exist for module reference. Wrong configuration in module.");
        }

        return $this->retrieve($referenceName);
    }

    /**
     * @param string $referenceName
     *
     * @return object|null
     */
    public function retrieve(string $referenceName): ?object
    {
        return $this->moduleReferences[$referenceName] ?? null;
    }

    /**
     * @return object[]
     */
    public function getAllRegisteredReferences(): array
    {
        return $this->moduleReferences;
    }
}
