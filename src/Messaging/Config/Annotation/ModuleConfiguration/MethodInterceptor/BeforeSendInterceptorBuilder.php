<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptor;

use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Endpoint\EntrypointGateway;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class BeforeSendInterceptorBuilder
 * @package Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class BeforeSendInterceptorBuilder implements ChannelInterceptorBuilder
{
    /**
     * @var int
     */
    private $precedence;
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var array|ParameterConverterBuilder[]
     */
    private $parameterConverters;
    /**
     * @var string[]
     */
    private $requiredReferenceNames = [];
    /**
     * @var object
     */
    private $directObject;

    /**
     * BeforeSendInterceptorBuilder constructor.
     * @param string $interceptorName
     * @param string $pointcut
     * @param int $precedence
     * @param string $referenceName
     * @param string $methodName
     * @param ParameterConverterBuilder[] $parameterConverters
     */
    private function __construct(string $interceptorName, string $pointcut, int $precedence, string $referenceName, string $methodName, array $parameterConverters)
    {
        $this->inputChannelName = $pointcut;
        $this->precedence = $precedence;
        $this->referenceName = $referenceName;
        $this->methodName = $methodName;
        $this->parameterConverters = $parameterConverters;

        if ($referenceName) {
            $this->requiredReferenceNames[] = $referenceName;
        }
    }

    /**
     * @param string $inputChannelName
     * @param int $precedence
     * @param string $referenceName
     * @param string $methodName
     * @param array $parameterConverters
     * @return BeforeSendInterceptorBuilder
     */
    public static function createWithReferenceName(string $inputChannelName, int $precedence, string $referenceName, string $methodName, array $parameterConverters) : self
    {
        return new self($inputChannelName, $precedence, $referenceName, $methodName, $parameterConverters);
    }

    /**
     * @param string $inputChannelName
     * @param int $precedence
     * @param object $directObject
     * @param string $methodName
     * @param array $parameterConverters
     * @return BeforeSendInterceptorBuilder
     */
    public static function createWithDirectObject(string $inputChannelName, int $precedence, object $directObject, string $methodName, array $parameterConverters) : self
    {
        $interceptor = new self($inputChannelName, $precedence, "", $methodName, $parameterConverters);
        $interceptor->directObject = $directObject;

        return $interceptor;
    }

    /**
     * @inheritDoc
     */
    public function relatedChannelName(): string
    {
        return $this->inputChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return $this->requiredReferenceNames;
    }

    /**
     * @inheritDoc
     */
    public function getPrecedence(): int
    {
        return $this->precedence;
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): ChannelInterceptor
    {
        $requestChannel = DirectChannel::create();
        $serviceActivator = ServiceActivatorBuilder::create($this->referenceName, $this->methodName)
            ->withPassThroughMessageOnVoidInterface(true)
            ->withMethodParameterConverters($this->parameterConverters)
            ->build(InMemoryChannelResolver::createEmpty(), $referenceSearchService);
        $requestChannel->subscribe($serviceActivator);

        $gateway = GatewayProxyBuilder::create("", EntrypointGateway::class, "executeEntrypoint", "requestChannel")
                        ->build($referenceSearchService, InMemoryChannelResolver::createFromAssociativeArray([
                            "requestChannel" => $requestChannel
                        ]));

        return new BeforeSendInterceptor($gateway);
    }
}