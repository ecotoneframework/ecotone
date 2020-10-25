<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

use Ecotone\Messaging\Config\ConfigurationException;

/**
 * Class EndpointAnnotation
 * @package Ecotone\Messaging\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class EndpointAnnotation extends IdentifiedAnnotation
{
    public string $inputChannelName = "";

    public function __construct(string $inputChannelName = "", string $endpointId = "")
    {
        if ($inputChannelName && $endpointId && $inputChannelName === $endpointId) {
            throw ConfigurationException::create("endpointId should not equals inputChannelName for endpoint with id: `{$inputChannelName}`");
        }

        $this->inputChannelName = $inputChannelName;
        parent::__construct($endpointId);
    }

    public function getInputChannelName(): string
    {
        return $this->inputChannelName;
    }
}