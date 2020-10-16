<?php
declare(strict_types=1);


namespace Ecotone\Modelling\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class AggregateRepository
 * @package Ecotone\Modelling\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"CLASS"})
 */
class Repository
{
    /**
     * If not configured it will take class name as reference
     */
    public string $referenceName = "";
}