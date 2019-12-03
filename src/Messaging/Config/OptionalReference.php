<?php


namespace Ecotone\Messaging\Config;


class OptionalReference
{
    /**
     * @var string
     */
    private $referenceName;

    /**
     * RequiredReference constructor.
     * @param string $referenceName
     */
    private function __construct(string $referenceName)
    {
        $this->referenceName = $referenceName;
    }

    /**
     * @param string $referenceName
     * @return RequiredReference
     */
    public static function create(string $referenceName) : self
    {
        return new self($referenceName);
    }

    /**
     * @return string
     */
    public function getReferenceName(): string
    {
        return $this->referenceName;
    }
}