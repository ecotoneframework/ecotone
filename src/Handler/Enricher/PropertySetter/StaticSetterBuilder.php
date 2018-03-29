<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\SetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class StaticPropertySetterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StaticSetterBuilder implements SetterBuilder
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var mixed
     */
    private $value;

    /**
     * StaticPropertySetterBuilder constructor.
     *
     * @param string $name
     * @param mixed $value
     */
    private function __construct(string $name, $value)
    {
        $this->name  = $name;
        $this->value = $value;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return StaticSetterBuilder
     */
    public static function createWith(string $name, $value) : self
    {
        return new self($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): Setter
    {
        return StaticSetter::createWith($this->name, $this->value);
    }
}