<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

/**
 * Class CombinedGatewayDefinition
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CombinedGatewayDefinition
{
    /**
     * @var GatewayBuilder
     */
    private $gatewayBuilder;
    /**
     * @var string
     */
    private $relatedMethod;

    /**
     * CombinedGatewayDefinition constructor.
     * @param GatewayBuilder $gatewayBuilder
     * @param string $relatedMethod
     */
    private function __construct(GatewayBuilder $gatewayBuilder, string $relatedMethod)
    {
        $this->gatewayBuilder = $gatewayBuilder;
        $this->relatedMethod = $relatedMethod;
    }

    /**
     * @param GatewayBuilder $gatewayBuilder
     * @param string $relatedMethod
     * @return CombinedGatewayDefinition
     */
    public static function create(GatewayBuilder $gatewayBuilder, string $relatedMethod) : self
    {
        return new self($gatewayBuilder, $relatedMethod);
    }

    /**
     * @return GatewayBuilder
     */
    public function getGatewayBuilder(): GatewayBuilder
    {
        return $this->gatewayBuilder;
    }

    /**
     * @return string
     */
    public function getRelatedMethod(): string
    {
        return $this->relatedMethod;
    }
}