<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class HeaderArgument
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class HeaderConverter implements ParameterConverter
{
    /**
     * @var string
     */
    private $headerName;
    /**
     * @var string
     */
    private $parameterName;
    /**
     * @var bool
     */
    private $isRequired;

    /**
     * HeaderArgument constructor.
     *
     * @param string $parameterName
     * @param string $headerName
     * @param bool   $isRequired
     */
    private function __construct(string $parameterName, string $headerName, bool $isRequired)
    {
        $this->parameterName = $parameterName;
        $this->headerName = $headerName;
        $this->isRequired = $isRequired;
    }

    /**
     * @param string $parameterName
     * @param string $headerName
     * @param bool   $isRequired
     *
     * @return HeaderConverter
     */
    public static function create(string $parameterName, string $headerName, bool $isRequired) : self
    {
        return new self($parameterName, $headerName, $isRequired);
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(InterfaceParameter $relatedParameter, Message $message)
    {
        if (!$this->isRequired && !$message->getHeaders()->containsKey($this->headerName)) {
            return null;
        }

        return $message->getHeaders()->get($this->headerName);
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() == $this->parameterName;
    }
}