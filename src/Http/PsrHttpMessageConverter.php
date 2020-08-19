<?php
declare(strict_types=1);

namespace Ecotone\Http;

use Ecotone\Messaging\Conversion\InMemoryConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Enricher\EnrichException;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\MessageConverter\MessageConverter;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use ReflectionException;

/**
 * Class PsrHttpMessageConverter
 * @package Ecotone\Http
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PsrHttpMessageConverter implements MessageConverter, \Serializable
{
    public const URI_META_DATA_KEY = 'uri';

    private const HTTP_HEADER_PREFIX = "http_";
    /**
     * @var PropertyEditorAccessor
     */
    private $propertyEditorAccessor;
    /**
     * @var PropertyReaderAccessor
     */
    private $propertyReaderAccessor;

    /**
     * PsrHttpMessageConverter constructor.
     */
    private function __construct()
    {

    }

    /**
     * @return PsrHttpMessageConverter
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function fromMessage(Message $message, Type $targetType)
    {
        return null;
        //http://www.jcombat.com/spring/understanding-http-message-converters-in-spring-framework
    }

    /**
     * @inheritDoc
     */
    public function toMessage($source, array $messageHeaders): ?MessageBuilder
    {
        if (!($source instanceof ServerRequestInterface)) {
            return null;
        }

        $headerMapper = DefaultHeaderMapper::createAllHeadersMapping(InMemoryConversionService::createWithoutConversion());
        $data = (string)$source->getBody();
        $contentType = $source->hasHeader("content-type") ? $source->getHeaderLine("content-type") : MediaType::APPLICATION_OCTET_STREAM;

        $headersToMap = [];
        foreach ($source->getHeaders() as $headerName => $header) {
            $headerName = strtolower($headerName);
            if (in_array($headerName, HttpHeaders::HTTP_REQUEST_HEADER_NAMES)) {
                $headerName = $this->dashesToCamelCase($headerName);
            }
            $headersToMap[$headerName] = implode(",", $header);
        }
        unset($headersToMap[self::HTTP_HEADER_PREFIX . HttpHeaders::CONTENT_TYPE]);

        $headers = $headerMapper->mapToMessageHeaders($headersToMap);
        if ($contentType === MediaType::MULTIPART_FORM_DATA) {
            $data = [];
            $contentType = MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::ARRAY)->toString();

            foreach ($source->getParsedBody() as $name => $value) {
                $data[$name] = $value;
            }

            $data = $this->addUploadedFiles("", $source->getUploadedFiles(), $data);
        }

        return MessageBuilder::withPayload(is_null($data) ? "" : $data)
            ->setHeader(HttpHeaders::REQUEST_URL, (string)$source->getUri())
            ->setHeader(HttpHeaders::REQUEST_METHOD, strtoupper($source->getMethod()))
            ->setHeader(MessageHeaders::CONTENT_TYPE, is_array($contentType) ? array_shift($contentType) : $contentType)
            ->setMultipleHeaders($headers)
            ->setMultipleHeaders($messageHeaders);
    }

    /**
     * @param string $key
     * @param array $uploadedFiles
     * @param array $dataToEnrich
     * @return mixed
     * @throws ReflectionException
     * @throws EnrichException
     * @throws MessagingException
     */
    private function addUploadedFiles(string $key, array $uploadedFiles, array $dataToEnrich)
    {
        foreach ($uploadedFiles as $name => $uploadedFileOrArray) {
            $key .= "[" . $name . "]";

            if ($uploadedFileOrArray instanceof UploadedFileInterface) {
                $metadata = $uploadedFileOrArray->getStream()->getMetadata();
                Assert::keyExists($metadata, self::URI_META_DATA_KEY, "Unknown uri for uploaded file");

                $uploadedMultipartFile = UploadedMultipartFile::createWith(
                    "file:/{$metadata[self::URI_META_DATA_KEY]}",
                    $uploadedFileOrArray->getClientFilename(),
                    $uploadedFileOrArray->getClientMediaType(),
                    $uploadedFileOrArray->getSize() ? $uploadedFileOrArray->getSize() : 0
                );
                $dataToEnrich = $this->getPropertyEditorAccessor()->enrichDataWith(
                    PropertyPath::createWith($key),
                    $dataToEnrich,
                    $uploadedMultipartFile,
                    MessageBuilder::withPayload("tmp")->build(),
                    null
                );
            } else {
                if (!$this->getPropertyReaderAccessor()->hasPropertyValue(PropertyPath::createWith($key), $dataToEnrich)) {
                    $dataToEnrich = $this->getPropertyEditorAccessor()->enrichDataWith(
                        PropertyPath::createWith($key),
                        $dataToEnrich,
                        [],
                        MessageBuilder::withPayload("tmp")->build(),
                        null
                    );
                }

                $dataToEnrich = $this->addUploadedFiles($key, $uploadedFileOrArray, $dataToEnrich);
            }
        }

        return $dataToEnrich;
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return "psrHttpMessageConverter";
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        return PsrHttpMessageConverter::create();
    }

    private function dashesToCamelCase(string $header)
    {
        $header = str_replace(' ', '', ucwords(str_replace('-', ' ', $header)));
        $header[0] = strtolower($header[0]);

        return self::HTTP_HEADER_PREFIX . $header;
    }

    /**
     * @return PropertyEditorAccessor
     * @throws MessagingException
     * @throws ReferenceNotFoundException
     */
    private function getPropertyEditorAccessor(): PropertyEditorAccessor
    {
        if (!$this->propertyEditorAccessor) {
           $this->propertyEditorAccessor = PropertyEditorAccessor::create(InMemoryReferenceSearchService::createWith([
               SymfonyExpressionEvaluationAdapter::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
           ]));
        }

        return $this->propertyEditorAccessor;
    }

    /**
     * @return PropertyReaderAccessor
     */
    private function getPropertyReaderAccessor(): PropertyReaderAccessor
    {
        if (!$this->propertyReaderAccessor) {
            $this->propertyReaderAccessor = new PropertyReaderAccessor();
        }

        return $this->propertyReaderAccessor;
    }
}