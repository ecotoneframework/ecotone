<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

use Ecotone\Messaging\Config\ConfigurationException;
use Ramsey\Uuid\Uuid;

/**
 * Class IdentifiedAnnotation
 * @package Ecotone\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class IdentifiedAnnotation
{
    /**
     * @var string
     */
    public $endpointId;
    /**
     * @var bool
     */
    private $isGenerated = false;

    public function __construct(array $values = [])
    {
        if (isset($values["inputChannelName"]) && isset($values["endpointId"]) && $values["inputChannelName"] === $values["endpointId"]) {
            throw ConfigurationException::create("endpointId should not equals inputChannelName for endpoint with id: `{$values["inputChannelName"]}`");
        }

        foreach ($values as $propertyName => $value) {
            if ($propertyName === "value") {
                continue;
            }

            $this->{$propertyName} = $value;
        }

        if (!$this->endpointId) {
            $this->endpointId = Uuid::uuid4()->toString();
            $this->isGenerated = true;
        }
    }

    /**
     * @return bool
     */
    public function isEndpointIdGenerated() : bool
    {
        return $this->isGenerated;
    }
}