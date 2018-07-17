<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\HeaderSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\SetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class StaticHeaderSetterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnricherHeaderValueBuilder implements SetterBuilder
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
     * StaticHeaderSetter constructor.
     * @param string $name
     * @param mixed $value
     */
    private function __construct(string $name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public static function create(string $name, $value) : self
    {
        return new self($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): Setter
    {
        return EnricherHeaderValueSetter::create(PropertyPath::createWith($this->name), $this->value);
    }
}