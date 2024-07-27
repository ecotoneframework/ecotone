<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayParameterConverterBuilder;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * Class StaticHeaderToMessageConverterBuilder
 * @package Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class GatewayHeaderValueBuilder implements GatewayParameterConverterBuilder
{
    private string $headerName;
    /**
     * @var mixed
     */
    private $headerValue;

    /**
     * StaticHeaderMessageArgumentConverter constructor.
     * @param string $headerName
     * @param mixed $headerValue
     */
    private function __construct(string $headerName, $headerValue)
    {
        $this->headerName = $headerName;
        $this->headerValue = $headerValue;
    }

    /**
     * @param string $headerName
     * @param mixed $headerValue
     * @return self
     */
    public static function create(string $headerName, $headerValue): self
    {
        return new self($headerName, $headerValue);
    }

    public function isHandling(InterfaceParameter $parameter): bool
    {
        return false;
    }

    public function compile(MessagingContainerBuilder $builder, InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(
            GatewayHeaderValueConverter::class,
            [
                $this->headerName,
                $this->headerValue,
            ]
        );
    }
}
