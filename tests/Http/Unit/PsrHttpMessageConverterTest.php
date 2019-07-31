<?php

namespace Test\Ecotone\Http\Unit;

use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\UploadedFile;
use Ecotone\Http\HttpHeaders;
use Ecotone\Http\KeepAsTemporaryMover\KeepAsTemporaryFileMover;
use Ecotone\Http\PsrHttpMessageConverter;
use Ecotone\Http\UploadedMultipartFile;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Test\Ecotone\Http\Fixture\ServerRequestMother;
use Test\Ecotone\Messaging\Unit\MessagingTest;
use function GuzzleHttp\Psr7\stream_for;

/**
 * Class PsrHttpMessageConverter
 * @package Test\Ecotone\Http
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PsrHttpMessageConverterTest extends MessagingTest
{
    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function TODO__test_transforming_get_request_to_message()
    {
        $psrHttpMessageConverter = $this->createPsrHttpMessageConverter();

        $this->assertEquals(
            MessageBuilder::withPayload("")
                ->setContentType(MediaType::createApplicationJson())
                ->setMultipleHeaders([
                    HttpHeaders::REQUEST_METHOD => HttpHeaders::METHOD_TYPE_GET,
                    HttpHeaders::REQUEST_URL => "http://localhost"
                ]),
            $psrHttpMessageConverter->toMessage(
                ServerRequestMother::createGet()
                    ->withAddedHeader(HttpHeaders::CONTENT_TYPE, MediaType::APPLICATION_JSON),
                [],
                DefaultHeaderMapper::createNoMapping()
            )
        );
    }

    /**
     * @return PsrHttpMessageConverter
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function createPsrHttpMessageConverter(): PsrHttpMessageConverter
    {
        $psrHttpMessageConverter = new PsrHttpMessageConverter(
            new KeepAsTemporaryFileMover(),
            PropertyEditorAccessor::create(InMemoryReferenceSearchService::createWith([
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])),
            new PropertyReaderAccessor()
        );

        return $psrHttpMessageConverter;
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function TODO__test_transforming_default_ocet_header_without_request_content_type()
    {
        $psrHttpMessageConverter = $this->createPsrHttpMessageConverter();

        $this->assertEquals(
            MessageBuilder::withPayload('')
                ->setContentType(MediaType::createApplicationOcetStream())
                ->setMultipleHeaders([
                    HttpHeaders::REQUEST_METHOD => HttpHeaders::METHOD_TYPE_GET,
                    HttpHeaders::REQUEST_URL => "http://localhost"
                ]),
            $psrHttpMessageConverter->toMessage(
                ServerRequestMother::createGet(),
                [],
                DefaultHeaderMapper::createNoMapping()
            )
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function TODO__test_transforming_multipart_parameters()
    {
        $psrHttpMessageConverter = $this->createPsrHttpMessageConverter();

        $this->assertEquals(
            MessageBuilder::withPayload([])
                ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter(TypeDescriptor::ARRAY))
                ->setMultipleHeaders([
                    HttpHeaders::REQUEST_METHOD => HttpHeaders::METHOD_TYPE_POST,
                    HttpHeaders::REQUEST_URL => "http://localhost"
                ])
                ->setPayload([
                    "name" => "johny",
                    "surname" => "bravo"
                ]),
            $psrHttpMessageConverter->toMessage(
                ServerRequestMother::createPost()
                    ->withHeader("content-type", MediaType::MULTIPART_FORM_DATA)
                    ->withParsedBody([
                        "name" => "johny",
                        "surname" => "bravo"
                    ]),
                [],
                DefaultHeaderMapper::createNoMapping()
            )
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function TODO__test_transforming_multipart_multi_dimension_array_parameter()
    {
        $psrHttpMessageConverter = $this->createPsrHttpMessageConverter();

        $this->assertEquals(
            MessageBuilder::withPayload([])
                ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter(TypeDescriptor::ARRAY))
                ->setMultipleHeaders([
                    HttpHeaders::REQUEST_METHOD => HttpHeaders::METHOD_TYPE_POST,
                    HttpHeaders::REQUEST_URL => "http://localhost"
                ])
                ->setPayload([
                    "order" => [
                        "name" => "good product",
                        "prices" => [
                            123, 100
                        ]
                    ]
                ]),
            $psrHttpMessageConverter->toMessage(
                ServerRequestMother::createPost()
                    ->withHeader("content-type", MediaType::MULTIPART_FORM_DATA)
                    ->withParsedBody([
                        "order" => [
                            "name" => "good product",
                            "prices" => [
                                123, 100
                            ]
                        ]
                    ]),
                [],
                DefaultHeaderMapper::createNoMapping()
            )
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function TODO__test_merging_multi_dimensional_parameters_and_files()
    {
        $psrHttpMessageConverter = $this->createPsrHttpMessageConverter();

        $this->assertEquals(
            MessageBuilder::withPayload([])
                ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter(TypeDescriptor::ARRAY))
                ->setMultipleHeaders([
                    HttpHeaders::REQUEST_METHOD => HttpHeaders::METHOD_TYPE_POST,
                    HttpHeaders::REQUEST_URL => "http://localhost"
                ])
                ->setPayload([
                    "order" => [
                        "name" => "good product",
                        "prices" => [
                            123, 100
                        ],
                        "files" => [
                            "images" => [
                                UploadedMultipartFile::createWith(
                                    "file://tmp/werdfds",
                                    "some.gif",
                                    MediaType::IMAGE_GIF,
                                    100
                                )
                            ]
                        ]
                    ]
                ]),
            $psrHttpMessageConverter->toMessage(
                ServerRequestMother::createPost()
                    ->withHeader(HttpHeaders::CONTENT_TYPE, MediaType::MULTIPART_FORM_DATA)
                    ->withParsedBody([
                        "order" => [
                            "name" => "good product",
                            "prices" => [
                                123, 100
                            ]
                        ]
                    ])
                    ->withUploadedFiles([
                        "order" => [
                            "files" => [
                                "images" => [
                                    new UploadedFile(
                                        FnStream::decorate(stream_for('bar'), [
                                            'getMetadata' => function () {
                                                return [
                                                    KeepAsTemporaryFileMover::WRAPPER_TYPE_META_DATA_KEY => "plainfile",
                                                    KeepAsTemporaryFileMover::URI_META_DATA_KEY => "/tmp/werdfds"
                                                ];
                                            }
                                        ]),
                                        100,
                                        0,
                                        'some.gif',
                                        MediaType::IMAGE_GIF
                                    )
                                ]
                            ]
                        ]
                    ]),
                [],
                DefaultHeaderMapper::createNoMapping()
            )
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function TODO__test_mapping_headers()
    {
        $psrHttpMessageConverter = $this->createPsrHttpMessageConverter();

        $token = "BEARER 1233da";
        $host = "http://example.com";
        $this->assertEquals(
            MessageBuilder::withPayload([])
                ->setContentType(MediaType::createApplicationXml())
                ->setMultipleHeaders([
                    HttpHeaders::REQUEST_METHOD => HttpHeaders::METHOD_TYPE_POST,
                    HttpHeaders::REQUEST_URL => "http://localhost"
                ])
                ->setMultipleHeaders([
                    "authorization" => $token,
                    "host" => $host
                ])
                ->setPayload("[]"),
            $psrHttpMessageConverter->toMessage(
                ServerRequestMother::createPost()
                    ->withHeader("content-type", MediaType::APPLICATION_JSON)
                    ->withHeader(HttpHeaders::AUTHORIZATION, $token)
                    ->withHeader(HttpHeaders::HOST, $host)
                    ->withBody(FnStream::decorate(stream_for(\json_encode([])),
                        ['getMetadata' => function () {
                            return [];
                        }]
                    )),
                [
                    MessageHeaders::CONTENT_TYPE => MediaType::APPLICATION_XML
                ],
                DefaultHeaderMapper::createWith([HttpHeaders::AUTHORIZATION, HttpHeaders::HOST], [])
            )
        );
    }

    public function TODO__test_converting_to_response()
    {

    }
}