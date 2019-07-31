<?php
declare(strict_types=1);

namespace Ecotone\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Ecotone\Http\KeepAsTemporaryMover\KeepAsTemporaryFileMover;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\MessageConverter\MessageConverter;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class PsrHttpMessageConverter
 * @package Ecotone\Http
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PsrHttpMessageConverter implements MessageConverter
{
    private const HTTP_HEADER_PREFIX = "http_";
    /**
     * @var UploadedFileMover
     */
    private $uploadedFileMover;
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
     * @param UploadedFileMover $uploadedFileMover
     * @param PropertyEditorAccessor $propertyEditorAccessor
     * @param PropertyReaderAccessor $propertyReaderAccessor
     */
    public function __construct(UploadedFileMover $uploadedFileMover, PropertyEditorAccessor $propertyEditorAccessor, PropertyReaderAccessor $propertyReaderAccessor)
    {
        $this->uploadedFileMover = $uploadedFileMover;
        $this->propertyEditorAccessor = $propertyEditorAccessor;
        $this->propertyReaderAccessor = $propertyReaderAccessor;
    }

    /**
     * @inheritDoc
     */
    public function fromMessage(Message $message, TypeDescriptor $targetType)
    {
        if (!$targetType->isClassOfType(ResponseInterface::class)) {
            return null;
        }

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

        $headerMapper = DefaultHeaderMapper::createWith(HttpHeaders::HTTP_REQUEST_HEADER_NAMES, []);
        $data = (string)$source->getBody();
        $contentType = $source->hasHeader(HttpHeaders::CONTENT_TYPE) ? $source->getHeaderLine(HttpHeaders::CONTENT_TYPE) : MediaType::APPLICATION_OCTET_STREAM;

        $headersToMap = [];
        foreach ($source->getHeaders() as $headerName => $header) {
            $headersToMap[strtolower($headerName)] = implode(",", $header);
        }
        $headers = $headerMapper->mapToMessageHeaders($headersToMap, self::HTTP_HEADER_PREFIX);
        if ($contentType === MediaType::MULTIPART_FORM_DATA) {
            $data = [];
            $contentType = MediaType::createApplicationXPHPObjectWithTypeParameter(TypeDescriptor::ARRAY)->toString();

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
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Handler\Enricher\EnrichException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function addUploadedFiles(string $key, array $uploadedFiles, array $dataToEnrich)
    {
        foreach ($uploadedFiles as $name => $uploadedFileOrArray) {
            $key .= "[" . $name . "]";

            if ($uploadedFileOrArray instanceof UploadedFileInterface) {
                $uploadedMultipartFile = UploadedMultipartFile::createWith(
                    $this->uploadedFileMover->move($uploadedFileOrArray),
                    $uploadedFileOrArray->getClientFilename(),
                    $uploadedFileOrArray->getClientMediaType(),
                    $uploadedFileOrArray->getSize() ? $uploadedFileOrArray->getSize() : 0
                );
                $dataToEnrich = $this->propertyEditorAccessor->enrichDataWith(
                    PropertyPath::createWith($key),
                    $dataToEnrich,
                    $uploadedMultipartFile,
                    MessageBuilder::withPayload("tmp")->build(),
                    null
                );
            }else {
                if (!$this->propertyReaderAccessor->hasPropertyValue(PropertyPath::createWith($key), $dataToEnrich)) {
                    $dataToEnrich = $this->propertyEditorAccessor->enrichDataWith(
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
}