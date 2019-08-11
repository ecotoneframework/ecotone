<?php

namespace Test\Ecotone\Http\Unit;

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
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\UploadedFile;
use function json_encode;
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
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_transforming_get_request_to_message()
    {
        $psrHttpMessageConverter = $this->createPsrHttpMessageConverter();

        $this->assertEquals(
            MessageBuilder::withPayload("")
                ->setContentType(MediaType::createApplicationJson())
                ->setMultipleHeaders([
                    HttpHeaders::REQUEST_METHOD => HttpHeaders::METHOD_TYPE_GET,
                    HttpHeaders::REQUEST_URL => "http://localhost",
                    HttpHeaders::HOST => 'localhost'
                ]),
            $psrHttpMessageConverter->toMessage(
                ServerRequestMother::createGet()
                    ->withAddedHeader("content-type", MediaType::APPLICATION_JSON),
                []
            )
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_transforming_default_ocet_header_without_request_content_type()
    {
        $psrHttpMessageConverter = $this->createPsrHttpMessageConverter();

        $this->assertEquals(
            MessageBuilder::withPayload('')
                ->setContentType(MediaType::createApplicationOcetStream())
                ->setMultipleHeaders([
                    HttpHeaders::REQUEST_METHOD => HttpHeaders::METHOD_TYPE_GET,
                    HttpHeaders::REQUEST_URL => "http://localhost",
                    HttpHeaders::HOST => 'localhost'
                ]),
            $psrHttpMessageConverter->toMessage(
                ServerRequestMother::createGet(),
                []
            )
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_transforming_multipart_parameters()
    {
        $psrHttpMessageConverter = $this->createPsrHttpMessageConverter();

        $this->assertEquals(
            MessageBuilder::withPayload([])
                ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter(TypeDescriptor::ARRAY))
                ->setMultipleHeaders([
                    HttpHeaders::REQUEST_METHOD => HttpHeaders::METHOD_TYPE_POST,
                    HttpHeaders::REQUEST_URL => "http://localhost",
                    HttpHeaders::HOST => 'localhost'
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
                []
            )
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_transforming_multipart_multi_dimension_array_parameter()
    {
        $psrHttpMessageConverter = $this->createPsrHttpMessageConverter();

        $this->assertEquals(
            MessageBuilder::withPayload([])
                ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter(TypeDescriptor::ARRAY))
                ->setMultipleHeaders([
                    HttpHeaders::REQUEST_METHOD => HttpHeaders::METHOD_TYPE_POST,
                    HttpHeaders::REQUEST_URL => "http://localhost",
                    HttpHeaders::HOST => 'localhost'
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
                []
            )
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_merging_multi_dimensional_parameters_and_files()
    {
        $psrHttpMessageConverter = $this->createPsrHttpMessageConverter();

        $this->assertEquals(
            MessageBuilder::withPayload([])
                ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter(TypeDescriptor::ARRAY))
                ->setMultipleHeaders([
                    HttpHeaders::REQUEST_METHOD => HttpHeaders::METHOD_TYPE_POST,
                    HttpHeaders::REQUEST_URL => "http://localhost",
                    HttpHeaders::HOST => 'localhost'
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
                    ->withHeader("content-type", MediaType::MULTIPART_FORM_DATA)
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
                                                    PsrHttpMessageConverter::URI_META_DATA_KEY => "/tmp/werdfds"
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
                []
            )
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_mapping_headers()
    {
        $psrHttpMessageConverter = $this->createPsrHttpMessageConverter();

        $token = "BEARER 1233da";
        $host = "http://example.com";
        $this->assertEquals(
            MessageBuilder::withPayload([])
                ->setContentType(MediaType::createApplicationXml())
                ->setMultipleHeaders([
                    HttpHeaders::REQUEST_METHOD => HttpHeaders::METHOD_TYPE_POST,
                    HttpHeaders::REQUEST_URL => "http://localhost",
                    HttpHeaders::AUTHORIZATION => $token,
                    HttpHeaders::HOST => $host
                ])
                ->setPayload("[]"),
            $psrHttpMessageConverter->toMessage(
                ServerRequestMother::createPost()
                    ->withHeader("authorization", $token)
                    ->withHeader("host", $host)
                    ->withBody(FnStream::decorate(stream_for(json_encode([])),
                        ['getMetadata' => function () {
                            return [];
                        }]
                    )),
                [
                    MessageHeaders::CONTENT_TYPE => MediaType::APPLICATION_XML
                ]
            )
        );
    }

    /**
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_mapping_custom_headers()
    {
        $psrHttpMessageConverter = $this->createPsrHttpMessageConverter();

        $token = "BEARER 1233da";
        $this->assertEquals(
            MessageBuilder::withPayload([])
                ->setContentType(MediaType::createApplicationJson())
                ->setMultipleHeaders([
                    HttpHeaders::REQUEST_METHOD => HttpHeaders::METHOD_TYPE_POST,
                    HttpHeaders::REQUEST_URL => "http://localhost",
                    HttpHeaders::HOST => 'localhost',
                    "x-token" => $token
                ])
                ->setPayload("[]"),
            $psrHttpMessageConverter->toMessage(
                ServerRequestMother::createPost()
                    ->withHeader("content-type", MediaType::APPLICATION_JSON)
                    ->withHeader("x-token", $token)
                    ->withBody(FnStream::decorate(stream_for(json_encode([])),
                        ['getMetadata' => function () {
                            return [];
                        }]
                    )),
                []
            )
        );
    }

    /**
     * @return PsrHttpMessageConverter
     * @throws MessagingException
     */
    private function createPsrHttpMessageConverter(): PsrHttpMessageConverter
    {
        $psrHttpMessageConverter = new PsrHttpMessageConverter(
            PropertyEditorAccessor::create(InMemoryReferenceSearchService::createWith([
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])),
            new PropertyReaderAccessor()
        );

        return $psrHttpMessageConverter;
    }
}