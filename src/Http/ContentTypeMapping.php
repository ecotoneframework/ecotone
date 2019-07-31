<?php
declare(strict_types=1);

namespace Ecotone\Http;

/**
 * Class RequestMapping
 * @package Ecotone\Http
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ContentTypeMapping
{
    /**
     * @var string
     */
    private $consumes;
    /**
     * @var string
     */
    private $produces;

    /**
     * RequestMapping constructor.
     * @param string $consumes
     * @param string $produces
     */
    private function __construct(string $consumes, string $produces)
    {
        $this->consumes = $consumes;
        $this->produces = $produces;
    }

    /**
     * @param string $consumes
     * @param string $produces
     * @return ContentTypeMapping
     */
    public static function create(string $consumes, string $produces) : self
    {
        return new self($consumes, $produces);
    }

    /**
     * @return string
     */
    public function getConsumes(): string
    {
        return $this->consumes;
    }

    /**
     * @return string
     */
    public function getProduces(): string
    {
        return $this->produces;
    }
}