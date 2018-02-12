<?php

namespace Fixture\Annotation\MessageEndpoint\Router;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverter\PayloadParameterConverterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\RouterAnnotation;

/**
 * Class RouterWithNoResolutionRequiredExample
 * @package Fixture\Annotation\MessageEndpoint\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class RouterWithNoResolutionRequiredExample
{
    /**
     * @param $content
     * @return string
     * @RouterAnnotation(inputChannel="inputChannel", isResolutionRequired=false, parameterConverters={@PayloadParameterConverterAnnotation(parameterName="content")})
     */
    public function route($content) : string
    {
        return "outputChannel";
    }
}