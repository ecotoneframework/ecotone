<?php

namespace Test\Ecotone\Modelling\Fixture\QueryHandlerAggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class Storage
{
    /**
     * @param SmallBox[] $smallBoxes
     * @param BigBox[] $bigBoxes
     */
    private function __construct(#[Identifier] private string $storageId, private array $smallBoxes, private array $bigBoxes)
    {
    }

    #[CommandHandler]
    public static function create(CreateStorage $command): self
    {
        return new self(
            $command->getStorageId(),
            $command->getSmallBoxes(),
            $command->getBigBoxes()
        );
    }

    /**
     * @return SmallBox[]
     */
    #[QueryHandler('storage.getSmallBoxes')]
    public function getSmallBoxes(): array
    {
        return $this->smallBoxes;
    }

    /**
     * @return Box[]|BigBox[]
     */
    #[QueryHandler('storage.getBoxes')]
    public function getBoxes(): array
    {
        return array_merge($this->smallBoxes, $this->bigBoxes);
    }

    /**
     * @return Box[]
     */
    public function getBigBoxes(): array
    {
        return $this->bigBoxes;
    }
}
