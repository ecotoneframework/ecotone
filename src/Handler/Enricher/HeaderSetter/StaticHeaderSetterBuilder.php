<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\HeaderSetter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\HeaderSetter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\HeaderSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class StaticHeaderSetterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\HeaderSetter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StaticHeaderSetterBuilder implements HeaderSetterBuilder
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $value;

    /**
     * StaticHeaderSetter constructor.
     * @param string $name
     * @param string $value
     */
    private function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @param string $name
     * @param string $value
     * @return self
     */
    public static function create(string $name, string $value) : self
    {
        return new self($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): HeaderSetter
    {
        return StaticHeaderSetter::create($this->name, $this->value);
    }
}