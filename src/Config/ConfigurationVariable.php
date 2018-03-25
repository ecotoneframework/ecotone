<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

/**
 * Class ConfigurationVariable
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConfigurationVariable
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var bool
     */
    private $isRequired;
    /**
     * @var string
     */
    private $description;

    /**
     * ConfigurationVariable constructor.
     * @param string $name
     * @param bool $isRequired
     * @param string $description
     */
    private function __construct(string $name, bool $isRequired, string $description)
    {
        $this->name = $name;
        $this->isRequired = $isRequired;
        $this->description = $description;
    }

    /**
     * @param string $name
     * @param string $description
     * @return ConfigurationVariable
     */
    public static function createRequired(string $name, string $description) : self
    {
        return new self($name, true, $description);
    }

    /**
     * @param string $name
     * @param string $description
     * @return ConfigurationVariable
     */
    public static function createOptional(string $name, string $description) : self
    {
        return new self($name, false, $description);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}