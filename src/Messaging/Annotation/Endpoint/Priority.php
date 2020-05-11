<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation\Endpoint;

/**
 * @Annotation
 */
class Priority
{
    /**
     * @var int
     */
    private $number;

    public function __construct(array $values)
    {
        $this->number = $values['value'];
    }

    public function getNumber(): int
    {
        return $this->number;
    }
}