<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation\Parameter;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class HeaderParameterConverter
 * @package Ecotone\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation()
 */
class Header
{
    /**
     * @Required()
     */
    public string $parameterName;
    /**
     * @Required()
     */
    public string $headerName;
    public string $expression = "";
    public bool $isRequired = true;
}