<?php

namespace Test\Ecotone\Modelling\Fixture\QueryHandlerAggregate;

class CreateStorage
{
    private string $storageId;
    /**
     * @var SmallBox[]
     */
    private array $smallBoxes;
    /**
     * @var BigBox[]
     */
    private array $bigBoxes;

    /**
     * @param string $storageId
     * @param SmallBox[] $smallBoxes
     * @param BigBox[] $bigBoxes
     */
    public function __construct(string $storageId, array $smallBoxes, array $bigBoxes)
    {
        $this->storageId = $storageId;
        $this->smallBoxes = $smallBoxes;
        $this->bigBoxes = $bigBoxes;
    }


    public function getStorageId(): string
    {
        return $this->storageId;
    }

    /**
     * @return SmallBox[]
     */
    public function getSmallBoxes(): array
    {
        return $this->smallBoxes;
    }

    /**
     * @return BigBox[]
     */
    public function getBigBoxes(): array
    {
        return $this->bigBoxes;
    }
}