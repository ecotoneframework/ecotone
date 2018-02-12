<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

/**
 * Interface MessageFromParameterConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ParameterToMessageConverterBuilder
{
    /**
     * @return ParameterToMessageConverter
     */
    public function build() : ParameterToMessageConverter;
}