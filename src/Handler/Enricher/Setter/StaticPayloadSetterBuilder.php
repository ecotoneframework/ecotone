<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\SetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class StaticPropertySetterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StaticPayloadSetterBuilder implements SetterBuilder
{
    /**
     * @var string
     */
    private $propertyPath;
    /**
     * @var mixed
     */
    private $value;

    /**
     * StaticPropertySetterBuilder constructor.
     *
     * @param string $propertyPath
     * @param mixed  $value
     */
    private function __construct(string $propertyPath, $value)
    {
        $this->propertyPath = $propertyPath;
        $this->value        = $value;
    }

    /**
     * @param string $propertyPath
     * @param mixed  $value
     *
     * @return StaticPayloadSetterBuilder
     */
    public static function createWith(string $propertyPath, $value) : self
    {
        return new self($propertyPath, $value);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): Setter
    {
        return StaticPayloadSetter::createWith(PropertyPath::createWith($this->propertyPath), $this->value);
    }
}