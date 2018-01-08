<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

/**
 * Interface ParameterConverter
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface NamedParameter
{
    /**
     * @param MethodArgument $methodArgument
     * @return bool
     */
    public function hasParameterNameAs(MethodArgument $methodArgument) : bool;

    /**
     * @param string $parameterName
     * @return bool
     */
    public function hasParameterName(string $parameterName) : bool;
}