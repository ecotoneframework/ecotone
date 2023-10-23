<?php

declare(strict_types=1);

namespace Ecotone\Messaging\MessageConverter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * Class DefaultHeaderMapper
 * @package Ecotone\Messaging\Endpoint\Mapper
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DefaultHeaderMapper implements HeaderMapper
{
    public const DEFAULT_HEADER_CONVERSION_MEDIA_TYPE = MediaType::APPLICATION_JSON;
    public const CONVERTED_HEADERS_TO_DIFFERENT_FORMAT = 'ecotone.convertedKeys';

    /**
     * @param string[] $toMessageHeadersMapping
     * @param string[] $fromMessageHeadersMapping
     */
    public function __construct(private array $toMessageHeadersMapping, private array $fromMessageHeadersMapping, private bool $headerNamesToLower)
    {
    }

    /**
     * @param string[] $toMessageHeadersMapping
     * @param string[] $fromMessageHeadersMapping
     */
    private static function create(array $toMessageHeadersMapping, array $fromMessageHeadersMapping, bool $headerNamesToLower): self
    {
        return new self(self::prepareRegex($toMessageHeadersMapping, $headerNamesToLower), self::prepareRegex($fromMessageHeadersMapping, $headerNamesToLower), $headerNamesToLower);
    }

    /**
     * @param string[] $toMessageHeadersMapping
     * @param string[] $fromMessageHeadersMapping
     */
    public static function createWith(array $toMessageHeadersMapping, array $fromMessageHeadersMapping): self
    {
        return self::create($toMessageHeadersMapping, $fromMessageHeadersMapping, false);
    }

    /**
     * @param string[] $toMessageHeadersMapping
     * @param string[] $fromMessageHeadersMapping
     */
    public static function createCaseInsensitiveHeadersWith(array $toMessageHeadersMapping, array $fromMessageHeadersMapping): self
    {
        return self::create($toMessageHeadersMapping, $fromMessageHeadersMapping, true);
    }

    public static function createAllHeadersMapping(): self
    {
        return self::create(['*'], ['*'], false);
    }

    public static function createNoMapping(): self
    {
        return self::create([], [], false);
    }

    /**
     * @inheritDoc
     */
    public function mapToMessageHeaders(array $headersToBeMapped, ConversionService $conversionService): array
    {
        return $this->mapHeaders($this->toMessageHeadersMapping, $headersToBeMapped, $conversionService);
    }

    /**
     * @inheritDoc
     */
    public function mapFromMessageHeaders(array $headersToBeMapped, ConversionService $conversionService): array
    {
        return $this->mapHeaders($this->fromMessageHeadersMapping, $headersToBeMapped, $conversionService);
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->toMessageHeadersMapping,
            $this->fromMessageHeadersMapping,
            $this->headerNamesToLower,
        ]);
    }

    /**
     * @param array $mappingHeaders
     * @param array $sourceHeaders
     * @return mixed
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function mapHeaders(array $mappingHeaders, array $sourceHeaders, ConversionService $conversionService): array
    {
        $targetHeaders = [];
        $convertedSourceHeaders = [];
        foreach ($sourceHeaders as $sourceHeaderName => $value) {
            $convertedSourceHeaders[$this->headerNamesToLower ? strtolower($sourceHeaderName) : $sourceHeaderName] = $value;
        }

        foreach ($mappingHeaders as $mappedHeader) {
            if (is_array($mappedHeader)) {
                $targetHeaders = array_merge($this->mapHeaders($mappedHeader, $sourceHeaders, $conversionService));
                continue;
            }

            if (array_key_exists($mappedHeader, $convertedSourceHeaders)) {
                $targetHeaders = $this->convertToStoreableFormat($mappedHeader, $convertedSourceHeaders[$mappedHeader], $targetHeaders, $conversionService);

                continue;
            }

            foreach ($convertedSourceHeaders as $sourceHeaderName => $value) {
                if (preg_match("#{$mappedHeader}#", $sourceHeaderName)) {
                    $targetHeaders = $this->convertToStoreableFormat($sourceHeaderName, $value, $targetHeaders, $conversionService);
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
    private function isScalarType($headerValue): bool
    {
        return (TypeDescriptor::createFromVariable($headerValue))->isScalar();
    }

    /**
     * @param array $mappedHeaders
     * @param bool $caseInsensitiveHeaderNames
     * @return array
     */
    private static function prepareRegex(array $mappedHeaders, bool $caseInsensitiveHeaderNames): array
    {
        $finalMappingHeaders = [];
        foreach ($mappedHeaders as $targetHeader) {
            $transformedHeader = $targetHeader;
            $transformedHeader = str_replace('.', "\.", $transformedHeader);
            $transformedHeader = str_replace('*', '.*', $transformedHeader);

            if (is_array($transformedHeader)) {
                $finalMappingHeaders[] = self::prepareRegex($transformedHeader, $caseInsensitiveHeaderNames);
            } else {
                $finalMappingHeaders[] = trim($caseInsensitiveHeaderNames ? strtolower($transformedHeader) : $transformedHeader);
            }
        }

        return $finalMappingHeaders;
    }

    private function convertToStoreableFormat(string $mappedHeader, mixed $value, array $convertedHeaders, ConversionService $conversionService): array
    {
        if ($this->isScalarType($value)) {
            $convertedHeaders[$mappedHeader] = $value;
        } elseif (
            $conversionService->canConvert(
                TypeDescriptor::createFromVariable($value),
                MediaType::createApplicationXPHP(),
                TypeDescriptor::createStringType(),
                MediaType::parseMediaType(self::DEFAULT_HEADER_CONVERSION_MEDIA_TYPE)
            )
        ) {
            $convertedHeaders[$mappedHeader] =  $conversionService->convert(
                $value,
                TypeDescriptor::createFromVariable($value),
                MediaType::createApplicationXPHP(),
                TypeDescriptor::createStringType(),
                MediaType::parseMediaType(self::DEFAULT_HEADER_CONVERSION_MEDIA_TYPE)
            );
        } elseif (
            $conversionService->canConvert(
                TypeDescriptor::createFromVariable($value),
                MediaType::createApplicationXPHP(),
                TypeDescriptor::createStringType(),
                MediaType::createApplicationXPHP()
            )
        ) {
            $convertedHeaders[$mappedHeader] =  $conversionService->convert(
                $value,
                TypeDescriptor::createFromVariable($value),
                MediaType::createApplicationXPHP(),
                TypeDescriptor::createStringType(),
                MediaType::createApplicationXPHP()
            );
        }

        return $convertedHeaders;
    }
}
