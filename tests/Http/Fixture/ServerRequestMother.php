<?php

namespace Test\Ecotone\Http\Fixture;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Ecotone\Http\HttpHeaders;

/**
 * Class ServerRequest
 * @package Fixture
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServerRequestMother
{
    public static function createGet(): ServerRequestInterface
    {
        return self::createServerRequest()
            ->withMethod(HttpHeaders::METHOD_TYPE_GET);
    }

    /**
     * @return ServerRequestInterface
     */
    private static function createServerRequest(): ServerRequestInterface
    {
        return ServerRequest::fromGlobals();
    }

    public static function createPost(): ServerRequestInterface
    {
        return self::createServerRequest()
            ->withMethod(HttpHeaders::METHOD_TYPE_POST);
    }

    public static function createPut(): ServerRequestInterface
    {
        return self::createServerRequest()
            ->withMethod(HttpHeaders::METHOD_TYPE_PUT);
    }

    public static function createOptions(): ServerRequestInterface
    {
        return self::createServerRequest()
            ->withMethod(HttpHeaders::METHOD_TYPE_OPTIONS);
    }
}