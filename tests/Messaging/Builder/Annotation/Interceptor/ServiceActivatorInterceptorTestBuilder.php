<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Builder\Annotation\Interceptor;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\ServiceActivatorInterceptor;

/**
 * Class ServiceActivatorInterceptor
 * @package Test\SimplyCodedSoftware\Messaging\Builder\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatorInterceptorTestBuilder
{
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var array
     */
    private $parameterConverters = [];

    /**
     * ServiceActivatorInterceptorTestBuilder constructor.
     * @param string $referenceName
     * @param string $methodName
     */
    private function __construct(string $referenceName, string $methodName)
    {
        $this->referenceName = $referenceName;
        $this->methodName = $methodName;
    }

    /**
     * @param string $referenceName
     * @param string $methodName
     * @return ServiceActivatorInterceptorTestBuilder
     */
    public static function create(string $referenceName, string $methodName) : self
    {
        return new self($referenceName, $methodName);
    }

    /**
     * @param array $parameterConverters
     * @return ServiceActivatorInterceptorTestBuilder
     */
    public function setParameterConverters(array $parameterConverters): self
    {
        $this->parameterConverters = $parameterConverters;
        return $this;
    }

    /**
     * @return ServiceActivatorInterceptor
     */
    public function build() : ServiceActivatorInterceptor
    {
        $serviceActivatorInterceptor = new ServiceActivatorInterceptor();

        $parameterConverters = [];
        foreach ($this->parameterConverters as $parameterConverter) {
            $parameterConverters[] = $parameterConverter->build();
        }

        $serviceActivatorInterceptor->referenceName = $this->referenceName;
        $serviceActivatorInterceptor->methodName = $this->methodName;
        $serviceActivatorInterceptor->parameterConverters = $parameterConverters;

        return $serviceActivatorInterceptor;
    }
}