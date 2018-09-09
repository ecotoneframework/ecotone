<?php
declare(strict_types=1);

namespace Builder\Annotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\EndpointId;

/**
 * Class EndpointIdTestBuilder
 * @package Builder\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EndpointIdTestBuilder
{
    /**
     * @var string
     */
    private $endpointId;

    /**
     * EndpointIdTestBuilder constructor.
     * @param string $endpointId
     */
    private function __construct(string $endpointId)
    {
        $this->endpointId = $endpointId;
    }

    /**
     * @param string $endpointId
     * @return EndpointIdTestBuilder
     */
    public static function create(string $endpointId) : self
    {
        return new self($endpointId);
    }

    /**
     * @return EndpointId
     */
    public function build() : EndpointId
    {
        $endpointId = new EndpointId();
        $endpointId->value = $this->endpointId;

        return $endpointId;
    }
}