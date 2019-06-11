<?php


namespace SimplyCodedSoftware\Messaging\Config;

use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class ModuleReferenceSearchService
 * @package SimplyCodedSoftware\Messaging\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ModuleReferenceSearchService
{
    public const REFERENCE_NAME = "moduleReferenceSearchService";

    /**
     * @var object[]
     */
    private $moduleReferences = [];

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
    public function retrieveRequired(string $referenceName)
    {
        if (!$this->retrieve($referenceName)) {
            throw InvalidArgumentException::create("Reference with name {$referenceName} does not exist for module reference. Wrong configuration in module.");
        }

        return $this->retrieve($referenceName);
    }

    /**
     * @param string $referenceName
     *
     * @return object|null
     */
    public function retrieve(string $referenceName)
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