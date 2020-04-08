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
    public $initialDelay;
    /**
     * @var int
     * @Required()
     */
    public $maxAttempts;
    /**
     * @var int
     */
    public $multiplier = 3;
}