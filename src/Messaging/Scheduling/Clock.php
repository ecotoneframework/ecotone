<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Scheduling;

/**
 * Interface Clock
 * @package SimplyCodedSoftware\Messaging\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Clock
{
    /**
     * @return integer Milliseconds since Epoch
     */
    public function unixTimeInMilliseconds() : int;
}