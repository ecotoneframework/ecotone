<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Transaction;

use Attribute;
use Ecotone\Messaging\Attribute\WithRequiredReferenceNameList;

#[Attribute]
/**
 * licence Apache-2.0
 */
class Transactional implements WithRequiredReferenceNameList
{
    private const FACTORY_REFERENCE_NAME_LIST = 'factoryReferenceNameList';
    /**
     * @var array|string[]
     */
    private $factoryReferenceNameList;

    public function __construct(array $factoryReferenceNameList)
    {
        $this->factoryReferenceNameList = $factoryReferenceNameList;
    }

    /**
     * @param string[] $factoryReferenceNameList
     * @return Transactional
     */
    public static function createWith(array $factoryReferenceNameList): self
    {
        return new self($factoryReferenceNameList);
    }

    /**
     * @return string[]
     */
    public function getFactoryReferenceNameList(): array
    {
        return $this->factoryReferenceNameList;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNameList(): iterable
    {
        return $this->getFactoryReferenceNameList();
    }
}
