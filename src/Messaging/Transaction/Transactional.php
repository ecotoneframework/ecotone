<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Transaction;

use Ecotone\Messaging\Annotation\WithRequiredReferenceNameList;

/**
 * Class Transactional
 * @package Ecotone\Messaging\Transaction
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class Transactional implements WithRequiredReferenceNameList
{
    private const FACTORY_REFERENCE_NAME_LIST = 'factoryReferenceNameList';
    /**
     * @var array|string[]
     */
    private $factoryReferenceNameList;

    /**
     * Transactional constructor.
     * @param string[] $values
     */
    public function __construct(array $values)
    {
        if (isset($values[self::FACTORY_REFERENCE_NAME_LIST])) {
            $this->factoryReferenceNameList = $values[self::FACTORY_REFERENCE_NAME_LIST];
        }else {
            if (isset($values['value'])) {
                $this->factoryReferenceNameList = is_array($values['value']) ? $values['value'] : [$values['value']];
            }
        }
    }

    /**
     * @param string[] $factoryReferenceNameList
     * @return Transactional
     */
    public static function createWith(array $factoryReferenceNameList) : self
    {
        return new self([self::FACTORY_REFERENCE_NAME_LIST => $factoryReferenceNameList]);
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