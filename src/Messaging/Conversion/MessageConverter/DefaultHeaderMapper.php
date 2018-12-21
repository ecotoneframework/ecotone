<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion\MessageConverter;

/**
 * Class DefaultHeaderMapper
 * @package SimplyCodedSoftware\Messaging\Endpoint\Mapper
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DefaultHeaderMapper implements HeaderMapper
{
    /**
     * @var array
     */
    private $mappedHeaders = [];

    /**
     * DefaultHeaderMapper constructor.
     * @param array $mappedHeaders
     */
    private function __construct(array $mappedHeaders)
    {
        $this->initialize($mappedHeaders);
    }

    /**
     * @param array $mappedHeaders
     * @return DefaultHeaderMapper
     */
    public static function createWith(array $mappedHeaders) : self
    {
        return new self($mappedHeaders);
    }

    /**
     * @inheritDoc
     */
    public function map(array $headersToBeMapped): array
    {
        return $this->mapHeaders($headersToBeMapped);
    }

    /**
     * @param $sourceHeaders
     * @return mixed
     */
    private function mapHeaders(array $sourceHeaders)
    {
        $targetHeaders = [];

        foreach ($this->mappedHeaders as $fromHeader => $toHeader) {
            if (array_key_exists($fromHeader, $sourceHeaders)) {
                $targetHeaders[$toHeader] = $sourceHeaders[$fromHeader];
                continue;
            }

            foreach ($sourceHeaders as $sourceHeaderName => $sourceHeaderValue) {
                if (preg_match("#{$fromHeader}#", $sourceHeaderName)) {
                    $targetHeaders[$sourceHeaderName] = $sourceHeaderValue;
                }
            }
        }

        return $targetHeaders;
    }

    /**
     * @param array $mappedHeaders
     */
    private function initialize(array $mappedHeaders) : void
    {
        $finalMappingHeaders = [];
        foreach ($mappedHeaders as $sourceHeader => $targetHeader) {
            $finalMappingHeaders[str_replace("*", ".*", $sourceHeader)] = $targetHeader;
        }

        $this->mappedHeaders = $finalMappingHeaders;
    }
}