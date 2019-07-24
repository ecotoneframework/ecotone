<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation\Parameter;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class HeaderParameterConverter
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation()
 */
class Header
{
    /**
     * @var string
     * @Required()
     */
    public $parameterName;
    /**
     * @var string
     * @Required()
     */
    public $headerName;
    /**
     * @var string
     */
    public $expression;
    /**
     * @var bool
     */
    public $isRequired = true;
}