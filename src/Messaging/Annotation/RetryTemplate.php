<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 */
class RetryTemplate
{
    /**
     * @var int in milliseconds
     * @Required()
     */
    public int $initialDelay;
    /**
     * @Required()
     */
    public int $maxAttempts;
    public int $multiplier = 3;
}