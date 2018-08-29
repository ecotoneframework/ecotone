<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Scheduling;

/**
 * Interface Clock
 * @package SimplyCodedSoftware\IntegrationMessaging\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Clock
{
    /**
     * @return integer Milliseconds since Epoch
     */
    public function unixTimeInMilliseconds() : int;
}