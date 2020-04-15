<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 */
class Consumer
{
    /**
     * @var string
     * @Required()
     */
    public $endpointId;
}