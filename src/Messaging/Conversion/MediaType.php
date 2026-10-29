<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class MediaType
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
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

    private const TYPE_PARAMETER = 'type';

    private static array $parsedMediaTypes = [];

    private string $type;
    private string $subtype;
    /**
     * @var string[]
     */
    private array $parameters = [];

    /**
     * MediaType constructor.
     * @param string $type
     * @param string $subtype
     * @param string[] $parameters
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function __construct(string $type, string $subtype, array $parameters)
    {
        Assert::notNullAndEmpty($type, "Primary type can't be empty");
        Assert::notNullAndEmpty($subtype, "Subtype type can't be empty");

        $this->type = $type;
        $this->subtype = $subtype;
        $this->parameters = array_filter($parameters, fn ($parameter) => $parameter !== null);
    }

    /**
     * @param string $type primary type
     * @param string $subtype subtype
     * @return MediaType
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function create(string $type, string $subtype): self
    {
        return new self($type, $subtype, []);
    }

    /**
     * @return MediaType
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createApplicationJson(): self
    {
        return self::parseMediaType(self::APPLICATION_JSON);
    }

    /**
     * @return MediaType
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createApplicationXml(): self
    {
        return self::parseMediaType(self::APPLICATION_XML);
    }

    /**
     * @return MediaType
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createMultipartFormData(): self
    {
        return self::parseMediaType(self::MULTIPART_FORM_DATA);
    }

    /**
     * @return MediaType
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createApplicationOcetStream(): self
    {
        return self::parseMediaType(self::APPLICATION_OCTET_STREAM);
    }

    /**
     * @return MediaType
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createApplicationXPHP(): self
    {
        return self::parseMediaType(self::APPLICATION_X_PHP);
    }

    /**
     * @return MediaType
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createApplicationXPHPArray(): self
    {
        return self::parseMediaType(self::APPLICATION_X_PHP_ARRAY);
    }

    /**
     * @param string $type
     * @return MediaType
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
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

    /**
     * @return MediaType
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createApplicationXPHPSerialized(): self
    {
        return self::parseMediaType(self::APPLICATION_X_PHP_SERIALIZED);
    }

    /**
     * @return MediaType
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createTextPlain(): self
    {
        return self::parseMediaType(self::TEXT_PLAIN);
    }

    /**
     * @param string $type
     * @param string $subtype
     * @param string[] $parameters
     * @return MediaType
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createWithParameters(string $type, string $subtype, array $parameters): self
    {
        return new self($type, $subtype, $parameters);
    }

    /**
     * @param string $mediaType
     * @return MediaType
     * @throws \Ecotone\Messaging\MessagingException
     * @throws InvalidArgumentException
     */
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

    /**
     * @param string $mediaType
     * @return bool
     */
    public function hasType(string $mediaType): bool
    {
        return $this->type === $mediaType;
    }

    /**
     * @param string $name
     * @param string $value
     * @return MediaType
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function addParameter(string $name, string $value): self
    {
        return self::createWithParameters(
            $this->type,
            $this->subtype,
            array_merge($this->parameters, [$name => $value])
        );
    }

    /**
     * Returns primary type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getSubtype(): string
    {
        return $this->subtype;
    }

    /**
     * @return bool
     */
    public function isWildcardType(): bool
    {
        return $this->type === '*';
    }

    /**
     * @return bool
     */
    public function isWildcardSubtype(): bool
    {
        return $this->subtype === '*';
    }

    /**
     * @return string[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasParameter(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    /**
     * @return bool
     */
    public function hasTypeParameter(): bool
    {
        return $this->hasParameter(self::TYPE_PARAMETER);
    }

    /**
     * @return Type
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function getTypeParameter(): Type
    {
        return Type::create($this->getParameter(self::TYPE_PARAMETER));
    }

    /**
     * @param string $name
     * @return string
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function getParameter(string $name): string
    {
        foreach ($this->parameters as $key => $value) {
            if ($key === $name) {
                return $value;
            }
        }

        throw InvalidArgumentException::create("Trying to access not existing media type parameter {$name} for {$this}");
    }

    /**
     * @param MediaType $other
     * @return bool
     */
    public function isCompatibleWith(MediaType $other): bool
    {
        return ($this->type === $other->type || $this->isWildcardType() || $other->isWildcardType()) && ($this->subtype === $other->subtype || $this->isWildcardSubtype() || $other->isWildcardSubtype());
    }

    /**
     * @param string $otherMediaTypeToParse
     * @return bool
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function isCompatibleWithParsed(string $otherMediaTypeToParse): bool
    {
        $other = self::parseMediaType($otherMediaTypeToParse);

        return ($this->type === $other->type || $this->isWildcardType() || $other->isWildcardType()) && ($this->subtype === $other->subtype || $this->isWildcardSubtype() || $other->isWildcardSubtype());
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return (string)$this;
    }

    /**
     * @return string
     */
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
