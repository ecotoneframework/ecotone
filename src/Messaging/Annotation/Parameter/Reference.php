<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation\Parameter;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class ReferenceServiceParameterConverterAnnotation
 * @package Ecotone\Messaging\Annotation\MessageToParameter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class Reference
{
    /**
     * @Required()
     */
    public string $parameterName;
    public string $referenceName = '';
}