<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class PayloadToMessageConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayPayloadBuilder implements GatewayParameterConverterBuilder
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * PayloadMessageParameter constructor.
     * @param string $parameterName
     */
    private function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @param string $parameterName
     * @return self
     */
    public static function create(string $parameterName) : self
    {
        return new self($parameterName);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): GatewayParameterConverter
    {
        return GatewayPayloadConverter::create($this->parameterName);
    }
}