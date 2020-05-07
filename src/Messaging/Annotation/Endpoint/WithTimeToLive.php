<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation\Endpoint;

/**
 * @Annotation
 */
class WithTimeToLive
{
    /**
     * @var int
     */
    private $time;

    public function __construct(array $values)
    {
        $this->time = $values['value'];
    }

    public function getTime(): int
    {
        return $this->time;
    }
}