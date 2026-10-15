<?php

declare(strict_types=1);

namespace Ecotone\Api\ExtensionObject;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * licence Apache-2.0
 */
final class MediaType implements DefinedObject
{
    public const TEXT_XML = 'text/xml';
    public const TEXT_JSON = 'text/json';
    public const TEXT_PLAIN = 'text/plain';
    public const TEXT_HTML = 'text/html';
    public const MULTIPART_FORM_DATA = 'multipart/form-data';
    public const IMAGE_PNG = 'image/png';
    public const IMAGE_JPEG = 'image/jpeg';
    public const IMAGE_GIF = 'image/gif';
    public const APPLICATION_XML = 'application/xml';
    public const APPLICATION_JSON = 'application/json';
    public const APPLICATION_FORM_URLENCODED = 'application/x-www-form-urlencoded';
    public const APPLICATION_ATOM_XML = 'application/atom+xml';
    public const APPLICATION_XHTML_XML = 'application/xhtml+xml';
    public const APPLICATION_OCTET_STREAM = 'application/octet-stream';
    public const APPLICATION_X_PHP = 'application/x-php';
    public const APPLICATION_X_PHP_ARRAY = 'application/x-php;type=array';
    public const APPLICATION_X_PHP_SERIALIZED = 'application/x-php-serialized';

    public const TYPE_PARAMETER = 'type';

    private static array $parsedMediaTypes = [];

    private string $type;
    public readonly string $subtype;
    /**
     * @var string[]
     */
    public readonly array $parameters;

    /**
     * @param string[] $parameters
     */
    private function __construct(string $type, string $subtype, array $parameters)
    {
        Assert::notNullAndEmpty($type, "Primary type can't be empty");
        Assert::notNullAndEmpty($subtype, "Subtype type can't be empty");

        $this->type = $type;
        $this->subtype = $subtype;
        $this->parameters = array_filter($parameters, fn ($parameter) => $parameter !== null);
    }

    public static function create(string $type, string $subtype): self
    {
        return new self($type, $subtype, []);
    }

    public static function createApplicationJson(): self
    {
        return self::parseMediaType(self::APPLICATION_JSON);
    }

    public static function createApplicationXml(): self
    {
        return self::parseMediaType(self::APPLICATION_XML);
    }

    public static function createMultipartFormData(): self
    {
        return self::parseMediaType(self::MULTIPART_FORM_DATA);
    }

    public static function createApplicationOcetStream(): self
    {
        return self::parseMediaType(self::APPLICATION_OCTET_STREAM);
    }

    public static function createApplicationXPHP(): self
    {
        return self::parseMediaType(self::APPLICATION_X_PHP);
    }

    public static function createApplicationXPHPArray(): self
    {
        return self::parseMediaType(self::APPLICATION_X_PHP_ARRAY);
    }

    public static function createApplicationXPHPWithTypeParameter(string $type): self
    {
        if ($type === 'mixed') {
            return self::parseMediaType(self::APPLICATION_X_PHP);
        }

        return self::parseMediaType(self::APPLICATION_X_PHP . ";type={$type}");
    }

    public function withoutTypeParameter(): self
    {
        return self::createWithParameters($this->type, $this->subtype, array_diff_key($this->parameters, [self::TYPE_PARAMETER => null]));
    }

    public static function createApplicationXPHPSerialized(): self
    {
        return self::parseMediaType(self::APPLICATION_X_PHP_SERIALIZED);
    }

    public static function createTextPlain(): self
    {
        return self::parseMediaType(self::TEXT_PLAIN);
    }

    /**
     * @param string[] $parameters
     */
    public static function createWithParameters(string $type, string $subtype, array $parameters): self
    {
        return new self($type, $subtype, $parameters);
    }

    public static function parseMediaType(string $mediaType): self
    {
        if (isset(self::$parsedMediaTypes[$mediaType])) {
            return self::$parsedMediaTypes[$mediaType];
        }
        $parsedMediaType = explode('/', $mediaType);

        Assert::keyExists($parsedMediaType, 0, "Passed media type `{$mediaType}` has no type");
        Assert::keyExists($parsedMediaType, 1, "Passed media type `{$mediaType}` has no subtype");
        $parametersToParse = explode(';', $parsedMediaType[1]);
        $subtype = array_shift($parametersToParse);
        $parameters = [];
        foreach ($parametersToParse as $parameterToParse) {
            $parameter = explode('=', $parameterToParse);
            $parameters[$parameter[0]] = $parameter[1];
        }

        return self::$parsedMediaTypes[$mediaType] = self::createWithParameters($parsedMediaType[0], $subtype, $parameters);
    }

    public function hasType(string $mediaType): bool
    {
        return $this->type === $mediaType;
    }

    public function addParameter(string $name, string $value): self
    {
        return self::createWithParameters(
            $this->type,
            $this->subtype,
            array_merge($this->parameters, [$name => $value])
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isWildcardType(): bool
    {
        return $this->type === '*';
    }

    public function isWildcardSubtype(): bool
    {
        return $this->subtype === '*';
    }

    public function hasParameter(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    public function hasTypeParameter(): bool
    {
        return $this->hasParameter(self::TYPE_PARAMETER);
    }

    public function getParameter(string $name): string
    {
        foreach ($this->parameters as $key => $value) {
            if ($key === $name) {
                return $value;
            }
        }

        throw InvalidArgumentException::create("Trying to access not existing media type parameter {$name} for {$this}");
    }

    public function isCompatibleWith(MediaType $other): bool
    {
        return ($this->type === $other->type || $this->isWildcardType() || $other->isWildcardType()) && ($this->subtype === $other->subtype || $this->isWildcardSubtype() || $other->isWildcardSubtype());
    }

    public function isCompatibleWithParsed(string $otherMediaTypeToParse): bool
    {
        $other = self::parseMediaType($otherMediaTypeToParse);

        return ($this->type === $other->type || $this->isWildcardType() || $other->isWildcardType()) && ($this->subtype === $other->subtype || $this->isWildcardSubtype() || $other->isWildcardSubtype());
    }

    public function toString(): string
    {
        return (string)$this;
    }

    public function __toString()
    {
        $parameters = '';
        foreach ($this->parameters as $key => $value) {
            $parameters .= ";{$key}={$value}";
        }

        return "{$this->type}/{$this->subtype}{$parameters}";
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->type,
            $this->subtype,
            $this->parameters,
        ], 'createWithParameters');
    }
}
