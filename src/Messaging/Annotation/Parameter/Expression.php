<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation\Parameter;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class PayloadParameterConverter
 * @package Ecotone\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation()
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Expression
{
    /**
     * @Required()
     */
    public string $parameterName;
    /**
     * @Required()
     */
    public string $expression;
}