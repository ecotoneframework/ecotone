<?php
declare(strict_types=1);

namespace Ecotone\Messaging\MessageConverter;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Recoverability\ErrorContext;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * Class DefaultHeaderMapper
 * @package Ecotone\Messaging\Endpoint\Mapper
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DefaultHeaderMapper implements HeaderMapper
{
    const DEFAULT_HEADER_CONVERSION_MEDIA_TYPE = MediaType::APPLICATION_JSON;
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

    private ConversionService $conversionService;

    /**
     * @param string[] $toMessageHeadersMapping
     * @param string[] $fromMessageHeadersMapping
     */
    private function __construct(array $toMessageHeadersMapping, array $fromMessageHeadersMapping, bool $headerNamesToLower, ConversionService $conversionService)
    {
        $this->fromMessageHeadersMapping = $this->prepareRegex($fromMessageHeadersMapping, $headerNamesToLower);
        $this->toMessageHeadersMapping = $this->prepareRegex($toMessageHeadersMapping, $headerNamesToLower);
        $this->headerNamesToLower = $headerNamesToLower;
        $this->conversionService = $conversionService;
    }

    /**
     * @param array $toMessageHeadersMapping
     * @param array $fromMessageHeadersMapping
     * @return DefaultHeaderMapper
     */
    public static function createWith(array $toMessageHeadersMapping, array $fromMessageHeadersMapping, ConversionService $conversionService) : self
    {
        return new self($toMessageHeadersMapping, $fromMessageHeadersMapping, false, $conversionService);
    }

    /**
     * @param array $toMessageHeadersMapping
     * @param array $fromMessageHeadersMapping
     * @return DefaultHeaderMapper
     */
    public static function createCaseInsensitiveHeadersWith(array $toMessageHeadersMapping, array $fromMessageHeadersMapping, ConversionService $conversionService) : self
    {
        return new self($toMessageHeadersMapping, $fromMessageHeadersMapping, true, $conversionService);
    }

    /**
     * @return DefaultHeaderMapper
     */
    public static function createAllHeadersMapping(ConversionService $conversionService) : self
    {
        return new self(["*"], ["*"], false, $conversionService);
    }

    /**
     * @return DefaultHeaderMapper
     */
    public static function createNoMapping(ConversionService $conversionService) : self
    {
        return new self([], [], false, $conversionService);
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
        foreach ($sourceHeaders as $sourceHeaderName => $value) {
            $convertedSourceHeaders[$this->headerNamesToLower ? strtolower($sourceHeaderName) : $sourceHeaderName] = $value;
        }

        foreach ($mappingHeaders as $mappedHeader) {
            if (is_array($mappedHeader)) {
                $targetHeaders = array_merge($this->mapHeaders($mappedHeader, $sourceHeaders));
                continue;
            }

            if (array_key_exists($mappedHeader, $convertedSourceHeaders)) {
                $value = $this->extractValue($convertedSourceHeaders[$mappedHeader]);

                if (!is_null($value)) {
                    $targetHeaders[$mappedHeader] = $value;
                }

                continue;
            }

            foreach ($convertedSourceHeaders as $sourceHeaderName => $value) {
                if (preg_match("#{$mappedHeader}#", $sourceHeaderName)) {
                    $value = $this->extractValue($value);

                    if (!is_null($value)) {
                        $targetHeaders[$sourceHeaderName] = $value;
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
        return (TypeDescriptor::createFromVariable($headerValue))->isScalar();
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

    private function extractValue($value)
    {
        if ($this->isScalarType($value)) {
            return $value;
        } else if (
            $this->conversionService->canConvert(
                TypeDescriptor::createFromVariable($value),
                MediaType::createApplicationXPHP(),
                TypeDescriptor::createStringType(),
                MediaType::parseMediaType(self::DEFAULT_HEADER_CONVERSION_MEDIA_TYPE)
            )
        ) {
            return $this->conversionService->convert(
                $value,
                TypeDescriptor::createFromVariable($value),
                MediaType::createApplicationXPHP(),
                TypeDescriptor::createStringType(),
                MediaType::parseMediaType(self::DEFAULT_HEADER_CONVERSION_MEDIA_TYPE)
            );
        }

        return null;
    }
}