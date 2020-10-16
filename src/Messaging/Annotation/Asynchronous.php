<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class Async
 * @package Ecotone\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class Asynchronous
{
    /**
     * @Required()
     */
    public string $channelName;
}