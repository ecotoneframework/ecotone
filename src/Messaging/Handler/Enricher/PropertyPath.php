<?php

namespace Ecotone\Messaging\Handler\Enricher;

use Ecotone\Messaging\Support\Assert;

/**
 * Class PropertyPath
 * @package Ecotone\Messaging\Handler\Enricher
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class PropertyPath
{
    private ?string $path;

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
    public static function createWith(string $path): self
    {
        return new self($path);
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @return string|null
     */
    public function getCurrentAccessProperty(): ?string
    {
        return $this->getNextAccessProperty(false);
    }

    /**
     * @return PropertyPath|null
     */
    public function cutCurrentAccessProperty(): ?PropertyPath
    {
        $currentAccessProperty = $this->getNextAccessProperty(true);

        if (is_null($currentAccessProperty)) {
            return null;
        }

        $path = substr($this->getPath(), strlen($currentAccessProperty));

        if (! $path) {
            return null;
        }

        return PropertyPath::createWith($path);
    }

    /**
     * @param string $path
     *
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function initialize(string $path): void
    {
        Assert::notNullAndEmpty($path, "Path for Property path can't be empty");

        $path = str_replace(["'", '"'], '', $path);

        $this->path = $path;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->path;
    }

    /**
     * @param $matches
     *
     * @return bool
     */
    private function hasAnyMatches($matches): bool
    {
        return ! empty($matches);
    }

    /**
     * @param bool $withSurrounding
     * @return mixed|string
     */
    private function getNextAccessProperty(bool $withSurrounding)
    {
        $extractKey = $withSurrounding ? 0 : 1;
        /** [0][data][worker] */
        preg_match("#^\[([a-zA-Z0-9]*)\]#", $this->getPath(), $startingWithPath);
        if ($this->hasAnyMatches($startingWithPath)) {
            return $startingWithPath[$extractKey];
        } else {
            /** worker[name] */
            preg_match('#\b([^\[\]]*)\[[a-zA-Z0-9]*\]#', $this->getPath(), $startingWithPropertyName);

            if ($this->hasAnyMatches($startingWithPropertyName)) {
                return $startingWithPropertyName[1];
            } else {
                return $this->path;
            }
        }
    }
}
