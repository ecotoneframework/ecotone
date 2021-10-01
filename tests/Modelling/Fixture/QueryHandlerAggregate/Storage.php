<?php

namespace Test\Ecotone\Modelling\Fixture\QueryHandlerAggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
class Storage
{
    /**
     * @param SmallBox[] $smallBoxes
     * @param BigBox[] $bigBoxes
     */
    private function __construct(#[AggregateIdentifier] private string $storageId, private array $smallBoxes, private array $bigBoxes) {}

    public static function create(CreateStorage $command) : self
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
    #[QueryHandler("storage.getSmallBoxes")]
    public function getSmallBoxes() : array
    {
        return $this->smallBoxes;
    }

    /**
     * @return Box[]
     */
    public function getBoxes() : array
    {
        return array_merge($this->smallBoxes, $this->bigBoxes);
    }

    /**
     * @return Box[]|BigBox[]
     */
    public function getBigBoxes() : array
    {
        return $this->bigBoxes;
    }
}