<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class InMemoryConfigurationVariableRetrievingService
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryConfigurationVariableRetrievingService implements ConfigurationVariableRetrievingService
{
    /**
     * @var string[]
     */
    private $variables;

    /**
     * InMemoryConfigurationVariableRetrievingService constructor.
     * @param string[] $variables
     */
    private function __construct(array $variables)
    {
        $this->variables = $variables;
    }

    /**
     * @param array $variables
     * @return InMemoryConfigurationVariableRetrievingService
     */
    public static function create(array $variables) : self
    {
        return new self($variables);
    }

    /**
     * @return InMemoryConfigurationVariableRetrievingService
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @inheritDoc
     */
    public function has(string $variableName): bool
    {
        return array_key_exists($variableName, $this->variables);
    }

    /**
     * @inheritDoc
     */
    public function get(string $variableName)
    {
        if (!$this->has($variableName)) {
            throw InvalidArgumentException::create("Expected configuration variable with name '{$variableName}' but got none.");
        }

        return $this->variables[$variableName];
    }
}