<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * Class InMemoryReferenceTypeFromNameResolver
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryReferenceTypeFromNameResolver implements ReferenceTypeFromNameResolver
{
    /**
     * @var string[]
     */
    private array $references;
    private ?\Ecotone\Messaging\Handler\ReferenceSearchService $referenceSearchService = null;

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
     * @param array $references
     * @return InMemoryReferenceTypeFromNameResolver
     */
    public static function createFromObjects(array $references) : self
    {
        $objectTypes = [];

        foreach ($references as $referenceName => $object) {
            $objectTypes[$referenceName] = get_class($object);
        }

        return self::createFromAssociativeArray($objectTypes);
    }

    public static function createFromReferenceSearchService(ReferenceSearchService $referenceSearchService) : self
    {
        $self = new self([]);
        $self->referenceSearchService = $referenceSearchService;

        return $self;
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
    public function resolve(string $referenceName): Type
    {
        if ($this->referenceSearchService) {
            return TypeDescriptor::createFromVariable($this->referenceSearchService->get($referenceName));
        }
        if (!array_key_exists($referenceName, $this->references)) {
            throw ConfigurationException::create("Reference not found for name resolver `{$referenceName}`.");
        }

        return TypeDescriptor::create($this->references[$referenceName]);
    }
}