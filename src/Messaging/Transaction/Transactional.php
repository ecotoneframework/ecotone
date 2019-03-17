<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Transaction;

use SimplyCodedSoftware\Messaging\Annotation\RequiredReferenceNameList;

/**
 * Class Transactional
 * @package SimplyCodedSoftware\Messaging\Transaction
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class Transactional
{
    /**
     * @var string[]
     * @RequiredReferenceNameList()
     */
    private $factoryReferenceNameList;

    /**
     * Transactional constructor.
     * @param string[] $values
     */
    public function __construct(array $values)
    {
        $this->factoryReferenceNameList = $values['factoryReferenceNameList'];
    }

    /**
     * @return string[]
     */
    public function getFactoryReferenceNameList(): array
    {
        return $this->factoryReferenceNameList;
    }
}