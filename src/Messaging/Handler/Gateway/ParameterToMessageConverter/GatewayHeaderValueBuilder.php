<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class StaticHeaderToMessageConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayHeaderValueBuilder implements GatewayParameterConverterBuilder
{
    /**
     * @var string
     */
    private $headerName;
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
    public static function create(string $headerName, $headerValue) : self
    {
        return new self($headerName, $headerValue);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): GatewayParameterConverter
    {
        return GatewayHeaderValueConverter::create($this->headerName, $this->headerValue);
    }
}