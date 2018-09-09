<?php
declare(strict_types=1);

namespace Builder\Annotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivator;

/**
 * Class ServiceActivatorBuilder
 * @package Builder\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatorTestBuilder
{
    /**
     * @var string
     */
    private $endpointId = "";
    /**
     * @var string
     */
    private $outputChannelName = '';
    /**
     * @var bool
     */
    private $requiresReply = false;
    /**
     * @var array
     */
    private $parameterConverters = [];
    /**
     * @var array
     */
    private $preCallInterceptors = [];
    /**
     * @var array
     */
    private $postCallInterceptors = [];
    /**
     * @var string
     */
    private $inputChannelName;

    private function __construct()
    {
    }

    public static function create() : self
    {
        return new self();
    }

    /**
     * @param string $endpointId
     * @return ServiceActivatorTestBuilder
     */
    public function withEndpointId(string $endpointId): ServiceActivatorTestBuilder
    {
        $this->endpointId = $endpointId;
        return $this;
    }

    /**
     * @param string $outputChannelName
     * @return ServiceActivatorTestBuilder
     */
    public function withOutputChannelName(string $outputChannelName): ServiceActivatorTestBuilder
    {
        $this->outputChannelName = $outputChannelName;
        return $this;
    }

    /**
     * @param bool $requiresReply
     * @return ServiceActivatorTestBuilder
     */
    public function withRequiresReply(bool $requiresReply): ServiceActivatorTestBuilder
    {
        $this->requiresReply = $requiresReply;
        return $this;
    }

    /**
     * @param array $parameterConverters
     * @return ServiceActivatorTestBuilder
     */
    public function withParameterConverters(array $parameterConverters): ServiceActivatorTestBuilder
    {
        $this->parameterConverters = $parameterConverters;
        return $this;
    }

    /**
     * @param array $preCallInterceptors
     * @return ServiceActivatorTestBuilder
     */
    public function withPreCallInterceptors(array $preCallInterceptors): ServiceActivatorTestBuilder
    {
        $this->preCallInterceptors = $preCallInterceptors;
        return $this;
    }

    /**
     * @param array $postCallInterceptors
     * @return ServiceActivatorTestBuilder
     */
    public function withPostCallInterceptors(array $postCallInterceptors): ServiceActivatorTestBuilder
    {
        $this->postCallInterceptors = $postCallInterceptors;
        return $this;
    }

    /**
     * @param string $inputChannelName
     * @return ServiceActivatorTestBuilder
     */
    public function withInputChannelName(string $inputChannelName): ServiceActivatorTestBuilder
    {
        $this->inputChannelName = $inputChannelName;
        return $this;
    }

    /**
     * @return ServiceActivator
     */
    public function build() : ServiceActivator
    {
        $serviceActivator = new ServiceActivator();

        $parameterConverters = [];
        foreach ($this->parameterConverters as $parameterConverter) {
            $parameterConverters[] = $parameterConverter->build();
        }

        $serviceActivator->requiresReply = $this->requiresReply;
        $serviceActivator->inputChannelName = $this->inputChannelName;
        $serviceActivator->outputChannelName = $this->outputChannelName;
        $serviceActivator->parameterConverters = $parameterConverters;
        $serviceActivator->endpointId = $this->endpointId;

        return $serviceActivator;
    }
}