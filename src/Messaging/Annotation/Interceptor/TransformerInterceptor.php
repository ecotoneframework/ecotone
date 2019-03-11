<?php

namespace SimplyCodedSoftware\Messaging\Annotation\Interceptor;

/**
 * Class TransformerInterceptor
 * @package SimplyCodedSoftware\Messaging\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class TransformerInterceptor
{
    /**
     * @var array
     */
    public $parameterConverters = [];
}