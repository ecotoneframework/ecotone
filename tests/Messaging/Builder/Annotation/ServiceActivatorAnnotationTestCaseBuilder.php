<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Builder\Annotation;
use Ramsey\Uuid\Uuid;
use Ecotone\Messaging\Annotation\ServiceActivator;

/**
 * Class ServiceActivatorBuilder
 * @package Test\Ecotone\Messaging\Builder\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatorAnnotationTestCaseBuilder
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
     * @return ServiceActivatorAnnotationTestCaseBuilder
     */
    public function withEndpointId(string $endpointId): ServiceActivatorAnnotationTestCaseBuilder
    {
        $this->endpointId = $endpointId;
        return $this;
    }

    /**
     * @param string $outputChannelName
     * @return ServiceActivatorAnnotationTestCaseBuilder
     */
    public function withOutputChannelName(string $outputChannelName): ServiceActivatorAnnotationTestCaseBuilder
    {
        $this->outputChannelName = $outputChannelName;
        return $this;
    }

    /**
     * @param bool $requiresReply
     * @return ServiceActivatorAnnotationTestCaseBuilder
     */
    public function withRequiresReply(bool $requiresReply): ServiceActivatorAnnotationTestCaseBuilder
    {
        $this->requiresReply = $requiresReply;
        return $this;
    }

    /**
     * @param array $parameterConverters
     * @return ServiceActivatorAnnotationTestCaseBuilder
     */
    public function withParameterConverters(array $parameterConverters): ServiceActivatorAnnotationTestCaseBuilder
    {
        $this->parameterConverters = $parameterConverters;
        return $this;
    }

    /**
     * @param array $preCallInterceptors
     * @return ServiceActivatorAnnotationTestCaseBuilder
     */
    public function withPreCallInterceptors(array $preCallInterceptors): ServiceActivatorAnnotationTestCaseBuilder
    {
        $this->preCallInterceptors = $preCallInterceptors;
        return $this;
    }

    /**
     * @param array $postCallInterceptors
     * @return ServiceActivatorAnnotationTestCaseBuilder
     */
    public function withPostCallInterceptors(array $postCallInterceptors): ServiceActivatorAnnotationTestCaseBuilder
    {
        $this->postCallInterceptors = $postCallInterceptors;
        return $this;
    }

    /**
     * @param string $inputChannelName
     * @return ServiceActivatorAnnotationTestCaseBuilder
     */
    public function withInputChannelName(string $inputChannelName): ServiceActivatorAnnotationTestCaseBuilder
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
        $serviceActivator->endpointId = $this->endpointId ? $this->endpointId : Uuid::uuid4()->toString();

        return $serviceActivator;
    }
}