<?php
declare(strict_types=1);

namespace Ecotone\Messaging\MessageConverter;

use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * Class DefaultHeaderMapper
 * @package Ecotone\Messaging\Endpoint\Mapper
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DefaultHeaderMapper implements HeaderMapper
{
    /**
     * @var string[]
     */
    private $fromMessageHeadersMapping = [];
    /**
     * @var string[]
     */
    private $toMessageHeadersMapping = [];
    /**
     * @var bool
     */
    private $headerNamesToLower;

    /**
     * DefaultHeaderMapper constructor.
     * @param string[] $toMessageHeadersMapping
     * @param string[] $fromMessageHeadersMapping
     * @param bool $headerNamesToLower
     */
    private function __construct(array $toMessageHeadersMapping, array $fromMessageHeadersMapping, bool $headerNamesToLower)
    {
        $this->fromMessageHeadersMapping = $this->prepareRegex($fromMessageHeadersMapping, $headerNamesToLower);
        $this->toMessageHeadersMapping = $this->prepareRegex($toMessageHeadersMapping, $headerNamesToLower);
        $this->headerNamesToLower = $headerNamesToLower;
    }

    /**
     * @param array $toMessageHeadersMapping
     * @param array $fromMessageHeadersMapping
     * @return DefaultHeaderMapper
     */
    public static function createWith(array $toMessageHeadersMapping, array $fromMessageHeadersMapping) : self
    {
        return new self($toMessageHeadersMapping, $fromMessageHeadersMapping, false);
    }

    /**
     * @param array $toMessageHeadersMapping
     * @param array $fromMessageHeadersMapping
     * @return DefaultHeaderMapper
     */
    public static function createCaseInsensitiveHeadersWith(array $toMessageHeadersMapping, array $fromMessageHeadersMapping) : self
    {
        return new self($toMessageHeadersMapping, $fromMessageHeadersMapping, true);
    }

    /**
     * @return DefaultHeaderMapper
     */
    public static function createAllHeadersMapping() : self
    {
        return new self(["*"], ["*"], false);
    }

    /**
     * @return DefaultHeaderMapper
     */
    public static function createNoMapping() : self
    {
        return new self([], [], false);
    }

    /**
     * @inheritDoc
     */
    public function mapToMessageHeaders(array $headersToBeMapped): array
    {
        return $this->mapHeaders($this->toMessageHeadersMapping, $headersToBeMapped);
    }

    /**
     * @inheritDoc
     */
    public function mapFromMessageHeaders(array $headersToBeMapped): array
    {
        return $this->mapHeaders($this->fromMessageHeadersMapping, $headersToBeMapped);
    }

    /**
     * @param array $mappingHeaders
     * @param array $sourceHeaders
     * @return mixed
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function mapHeaders(array $mappingHeaders, array $sourceHeaders)
    {
        $targetHeaders = [];
        $convertedSourceHeaders = [];
        foreach ($sourceHeaders as $sourceHeaderName => $sourceHeaderValue) {
            $convertedSourceHeaders[$this->headerNamesToLower ? strtolower($sourceHeaderName) : $sourceHeaderName] = $sourceHeaderValue;
        }

        foreach ($mappingHeaders as $mappedHeader) {
            if (is_array($mappedHeader)) {
                $targetHeaders = array_merge($this->mapHeaders($mappedHeader, $sourceHeaders));
                continue;
            }

            if (array_key_exists($mappedHeader, $convertedSourceHeaders)) {
                if ($this->isScalarType($convertedSourceHeaders[$mappedHeader])) {
                    $targetHeaders[$mappedHeader] = $convertedSourceHeaders[$mappedHeader];
                }

                continue;
            }

            foreach ($convertedSourceHeaders as $sourceHeaderName => $sourceHeaderValue) {
                if (preg_match("#{$mappedHeader}#", $sourceHeaderName)) {
                    if ($this->isScalarType($sourceHeaderValue)) {
                        $targetHeaders[$sourceHeaderName] = $sourceHeaderValue;
                    }
                }
            }
        }

        return $targetHeaders;
    }

    /**
     * @param $headerValue
     * @return bool
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function isScalarType($headerValue) : bool
    {
        return TypeDescriptor::isItTypeOfScalar(TypeDescriptor::createFromVariable($headerValue)->toString());
    }

    /**
     * @param array $mappedHeaders
     * @param bool $caseInsensitiveHeaderNames
     * @return array
     */
    private function prepareRegex(array $mappedHeaders, bool $caseInsensitiveHeaderNames) : array
    {
        $finalMappingHeaders = [];
        foreach ($mappedHeaders as $targetHeader) {
            $transformedHeader = $targetHeader;
            $transformedHeader = str_replace(".", "\.", $transformedHeader);
            $transformedHeader = str_replace("*", ".*", $transformedHeader);

            if (is_array($transformedHeader)) {
                $finalMappingHeaders[] = $this->prepareRegex($transformedHeader, $caseInsensitiveHeaderNames);
            }else {
                $finalMappingHeaders[] = trim($caseInsensitiveHeaderNames ? strtolower($transformedHeader) : $transformedHeader);
            }
        }

        return $finalMappingHeaders;
    }
}