<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation\Endpoint;

/**
 * @Annotation
 */
class Delayed
{
    private int $time;

    public function __construct(array $values)
    {
        $this->time = $values['value'];
    }

    public function getTime(): int
    {
        return $this->time;
    }
}