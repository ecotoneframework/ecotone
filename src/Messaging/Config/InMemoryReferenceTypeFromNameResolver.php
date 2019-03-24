<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config;

use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;

/**
 * Class InMemoryReferenceTypeFromNameResolver
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryReferenceTypeFromNameResolver implements ReferenceTypeFromNameResolver
{
    /**
     * @var string[]
     */
    private $references;

    /**
     * InMemoryReferenceTypeFromNameResolver constructor.
     * @param string[] $references
     */
    private function __construct(array $references)
    {
        $this->references = $references;
    }

    /**
     * @param string[] $references
     * @return InMemoryReferenceTypeFromNameResolver
     */
    public static function createFromAssociativeArray(array $references) : self
    {
        return new self($references);
    }

    /**
     * @return InMemoryReferenceTypeFromNameResolver
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $referenceName): TypeDescriptor
    {
        if (!array_key_exists($referenceName, $this->references)) {
            throw ConfigurationException::create("Reference not found `{$referenceName}`.");
        }

        return TypeDescriptor::create($this->references[$referenceName]);
    }
}