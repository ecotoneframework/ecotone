<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

/**
 * Interface MessageFromParameterConverterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ParameterToMessageConverterBuilder
{
    /**
     * @return ParameterToMessageConverter
     */
    public function build() : ParameterToMessageConverter;
}