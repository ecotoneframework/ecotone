<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class PropertyPath
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PropertyPath
{
    /**
     * @var string
     */
    private $path;

    /**
     * PropertyPath constructor.
     *
     * @param string $path
     */
    private function __construct(string $path)
    {
        $this->initialize($path);
    }

    /**
     * @param string $path
     *
     * @return PropertyPath
     */
    public static function createWith(string $path) : self
    {
        return new self($path);
    }

    /**
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function initialize(string $path) : void
    {
        if (preg_match("#[\'\\\"\.]#", $path)) {
            throw InvalidArgumentException::create("Passed property path {$path} is wrong. It should be in format like: `order[orderId]`. Remember it's not expression language, so do not use `'` or `\"` or `.`");
        }

        $this->path = $path;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->path;
    }
}