<?php

namespace Ecotone\Messaging\Config;

/**
 * Class RequiredReference
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RequiredReference
{
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $className;
    /**
     * @var string
     */
    private $description;

    /**
     * RequiredReference constructor.
     * @param string $referenceName
     * @param string $className
     * @param string $description
     */
    private function __construct(string $referenceName, string $className, string $description)
    {
        $this->referenceName = $referenceName;
        $this->className = $className;
        $this->description = $description;
    }

    /**
     * @param string $referenceName
     * @param string $className
     * @param string $description
     * @return RequiredReference
     */
    public static function create(string $referenceName, string $className, string $description) : self
    {
        return new self($referenceName, $className, $description);
    }

    /**
     * @return string
     */
    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}