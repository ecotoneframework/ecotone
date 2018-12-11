<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class ConfigurableReferenceSearchService
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConfigurableReferenceSearchService
{
    /**
     * @var object[]
     */
    private $referenceObjects = [];

    /**
     * ConfigurableReferenceSearchService constructor.
     * @param object[] $referenceObjects
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct(array $referenceObjects)
    {
        foreach ($referenceObjects as $referenceName => $object) {
            $this->addObject($referenceName, $object);
        }
    }

    /**
     * @return ConfigurableReferenceSearchService
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @param array $referenceObjects
     * @return ConfigurableReferenceSearchService
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWith(array $referenceObjects) : self
    {
        return new self($referenceObjects);
    }

    /**
     * @param string $referenceName
     * @param $object
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function addObject(string $referenceName, $object) : void
    {
        Assert::notNullAndEmpty($referenceName, "Reference Name must not empty");
        Assert::isObject($object, "Reference service working only with objects. Some other type given");

        $this->referenceObjects[$referenceName] = $object;
    }

    /**
     * @return object[]
     */
    public function getReferenceObjects() : array
    {
        return $this->referenceObjects;
    }
}