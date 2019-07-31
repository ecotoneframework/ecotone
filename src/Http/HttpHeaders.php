<?php
declare(strict_types=1);

namespace Ecotone\Http;

/**
 * Interface HttpHeaders
 * @package Ecotone\Http
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface HttpHeaders
{
    public const REQUEST_URL = "http_requestUrl";
    public const REQUEST_METHOD = "http_requestMethod";

    public const ACCEPT_TYPE = 'accept';
    public const ACCEPT_CHARSET = 'accept-charset';
    public const ACCEPT_ENCODING = 'accept-encoding';
    public const ACCEPT_LANGUAGE = 'accept-language';
    public const ACCEPT_RANGES = "accept-ranges";
    public const ACCESS_CONTROL_ALLOW_CREDENTIALS = "access_control_allow_credentials";
    public const ACCESS_CONTROL_ALLOW_HEADERS = "access_control_allow_headers";
    public const ACCESS_CONTROL_ALLOW_METHODS = "access_control_allow_methods";
    public const ACCESS_CONTROL_ALLOW_ORIGIN = "access_control_allow_origin";
    public const ACCESS_CONTROL_EXPOSE_HEADERS = "access_control_expose_headers";
    public const ACCESS_CONTROL_MAX_AGE = "access_control_max_age";
    public const ACCESS_CONTROL_REQUEST_HEADERS = "access_control_request_headers";
    public const ACCESS_CONTROL_REQUEST_METHOD = "access_control_request_method";
    public const AGE = "age";
    public const ALLOW = "allow";
    public const AUTHORIZATION = "authorization";
    public const CACHE_CONTROL = "cache_control";
    public const CONNECTION = "connection";
    public const CONTENT_DISPOSITION = "content_disposition";
    public const CONTENT_ENCODING = "content_encoding";
    public const CONTENT_LANGUAGE = "content_language";
    public const CONTENT_LENGTH = "content_length";
    public const CONTENT_LOCATION = "content_location";
    public const CONTENT_RANGE = "content_range";
    public const CONTENT_TYPE = 'content-type';
    public const COOKIE = "cookie";
    public const DATE = "date";
    public const EMPTY = "empty";
    public const ETAG = "etag";
    public const EXPECT = "expect";
    public const EXPIRES = "expires";
    public const FROM = "from";
    public const HOST = "host";
    public const IF_MATCH = "if_match";
    public const IF_MODIFIED_SINCE = "if_modified_since";
    public const IF_NONE_MATCH = "if_none_match";
    public const IF_RANGE = "if_range";
    public const IF_UNMODIFIED_SINCE = "if_unmodified_since";
    public const LAST_MODIFIED = "last_modified";
    public const LINK = "link";
    public const LOCATION = "location";
    public const MAX_FORWARDS = "max_forwards";
    public const ORIGIN = "origin";
    public const PRAGMA = "pragma";
    public const PROXY_AUTHENTICATE = "proxy_authenticate";
    public const PROXY_AUTHORIZATION = "proxy_authorization";
    public const RANGE = "range";
    public const REFERER = "referer";
    public const RETRY_AFTER = "retry_after";
    public const SERVER = "server";
    public const SET_COOKIE = "set_cookie";
    public const SET_COOKIE2 = "set_cookie2";
    public const TE = "te";
    public const TRAILER = "tailer";
    public const TRANSFER_ENCODING = "transfer_encoding";
    public const UPGRADE = "upgrade";
    public const USER_AGENT = "user_agent";
    public const VARY = "vary";
    public const VIA = "via";
    public const WARNING = "warning";
    public const WWW_AUTHENTICATE = "www_authenticate";

    public const METHOD_TYPE_GET = 'GET';
    public const METHOD_TYPE_POST = 'POST';
    public const METHOD_TYPE_PUT = 'PUT';
    public const METHOD_TYPE_OPTIONS = 'OPTIONS';

    public const HTTP_REQUEST_HEADER_NAMES = [
        HttpHeaders::ACCEPT_TYPE,
        HttpHeaders::ACCEPT_CHARSET,
        HttpHeaders::ACCEPT_ENCODING,
        HttpHeaders::ACCEPT_LANGUAGE,
        HttpHeaders::ACCEPT_RANGES,
        HttpHeaders::AUTHORIZATION,
        HttpHeaders::CACHE_CONTROL,
        HttpHeaders::CONNECTION,
        HttpHeaders::CONTENT_LENGTH,
        HttpHeaders::CONTENT_TYPE,
        HttpHeaders::COOKIE,
        HttpHeaders::DATE,
        HttpHeaders::EXPECT,
        HttpHeaders::FROM,
        HttpHeaders::HOST,
        HttpHeaders::IF_MATCH,
        HttpHeaders::IF_MODIFIED_SINCE,
        HttpHeaders::IF_NONE_MATCH,
        HttpHeaders::IF_RANGE,
        HttpHeaders::IF_UNMODIFIED_SINCE,
        HttpHeaders::MAX_FORWARDS,
        HttpHeaders::PRAGMA,
        HttpHeaders::PROXY_AUTHORIZATION,
        HttpHeaders::RANGE,
        HttpHeaders::REFERER,
        HttpHeaders::TE,
        HttpHeaders::UPGRADE,
        HttpHeaders::USER_AGENT,
        HttpHeaders::VIA,
        HttpHeaders::WARNING
    ];

    public const HTTP_RESPONSE_HEADER_NAMES = [
        HttpHeaders::ACCEPT_RANGES,
        HttpHeaders::AGE,
        HttpHeaders::ALLOW,
        HttpHeaders::CACHE_CONTROL,
        HttpHeaders::CONNECTION,
        HttpHeaders::CONTENT_ENCODING,
        HttpHeaders::CONTENT_LANGUAGE,
        HttpHeaders::CONTENT_LENGTH,
        HttpHeaders::CONTENT_LOCATION,
        HttpHeaders::CONTENT_RANGE,
        HttpHeaders::CONTENT_TYPE,
        HttpHeaders::CONTENT_DISPOSITION,
        HttpHeaders::TRANSFER_ENCODING,
        HttpHeaders::DATE,
        HttpHeaders::ETAG,
        HttpHeaders::EXPIRES,
        HttpHeaders::LAST_MODIFIED,
        HttpHeaders::LOCATION,
        HttpHeaders::PRAGMA,
        HttpHeaders::PROXY_AUTHENTICATE,
        HttpHeaders::RETRY_AFTER,
        HttpHeaders::SERVER,
        HttpHeaders::SET_COOKIE,
        HttpHeaders::TRAILER,
        HttpHeaders::VARY,
        HttpHeaders::VIA,
        HttpHeaders::WARNING,
        HttpHeaders::WWW_AUTHENTICATE
    ];
}