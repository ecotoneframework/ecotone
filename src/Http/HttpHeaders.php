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

    public const ACCEPT_TYPE = 'http_accept';
    public const ACCEPT_CHARSET = 'http_acceptCharset';
    public const ACCEPT_ENCODING = 'http_acceptEncoding';
    public const ACCEPT_LANGUAGE = 'http_acceptLanguage';
    public const ACCEPT_RANGES = "http_acceptRanges";
    public const ACCESS_CONTROL_ALLOW_CREDENTIALS = "http_accessControlAllowCredentials";
    public const ACCESS_CONTROL_ALLOW_HEADERS = "http_accessControlAllowHeaders";
    public const ACCESS_CONTROL_ALLOW_METHODS = "http_accessControlAllowMethods";
    public const ACCESS_CONTROL_ALLOW_ORIGIN = "http_accessControlAllowOrigin";
    public const ACCESS_CONTROL_EXPOSE_HEADERS = "http_accessControlExposeHeaders";
    public const ACCESS_CONTROL_MAX_AGE = "http_accessControlMax_age";
    public const ACCESS_CONTROL_REQUEST_HEADERS = "http_accessControlRequestHeaders";
    public const ACCESS_CONTROL_REQUEST_METHOD = "http_accessControlRequestMethod";
    public const AGE = "http_age";
    public const ALLOW = "http_allow";
    public const AUTHORIZATION = "http_authorization";
    public const CACHE_CONTROL = "http_cacheControl";
    public const CONNECTION = "http_connection";
    public const CONTENT_DISPOSITION = "http_contentDisposition";
    public const CONTENT_ENCODING = "http_contentEncoding";
    public const CONTENT_LANGUAGE = "http_contentLanguage";
    public const CONTENT_LENGTH = "http_contentLength";
    public const CONTENT_LOCATION = "http_contentLocation";
    public const CONTENT_RANGE = "http_contentRange";
    public const CONTENT_TYPE = 'contentType';
    public const COOKIE = "http_cookie";
    public const DATE = "http_date";
    public const EMPTY = "http_empty";
    public const ETAG = "http_etag";
    public const EXPECT = "http_expect";
    public const EXPIRES = "http_expires";
    public const FROM = "http_from";
    public const HOST = "http_host";
    public const IF_MATCH = "http_ifMatch";
    public const IF_MODIFIED_SINCE = "http_ifModifiedSince";
    public const IF_NONE_MATCH = "http_ifNoneMatch";
    public const IF_RANGE = "http_ifRange";
    public const IF_UNMODIFIED_SINCE = "http_ifUnmodifiedSince";
    public const LAST_MODIFIED = "http_lastModified";
    public const LINK = "http_link";
    public const LOCATION = "http_location";
    public const MAX_FORWARDS = "http_maxForwards";
    public const ORIGIN = "http_origin";
    public const PRAGMA = "http_pragma";
    public const PROXY_AUTHENTICATE = "http_proxyAuthenticate";
    public const PROXY_AUTHORIZATION = "http_proxyAuthorization";
    public const RANGE = "http_range";
    public const REFERER = "http_referer";
    public const RETRY_AFTER = "http_retryAfter";
    public const SERVER = "http_server";
    public const SET_COOKIE = "http_setCookie";
    public const SET_COOKIE2 = "http_setCookie2";
    public const TE = "http_te";
    public const TRAILER = "http_tailer";
    public const TRANSFER_ENCODING = "http_transferEncoding";
    public const UPGRADE = "http_upgrade";
    public const USER_AGENT = "http_userAgent";
    public const VARY = "http_httpVary";
    public const VIA = "http_via";
    public const WARNING = "http_warning";
    public const WWW_AUTHENTICATE = "http_wwwAuthenticate";

    public const METHOD_TYPE_GET = 'GET';
    public const METHOD_TYPE_POST = 'POST';
    public const METHOD_TYPE_PUT = 'PUT';
    public const METHOD_TYPE_OPTIONS = 'OPTIONS';

    public const HTTP_REQUEST_HEADER_NAMES = [
        'accept',
        'content-type',
        'accept-charset',
        'accept-encoding',
        'accept-language',
        "accept-ranges",
        "authorization",
        "cache_control",
        "connection",
        "content_length",
        "cookie",
        "date",
        "expect",
        "from",
        "host",
        "if_match",
        "if_modified_since",
        "if_none_match",
        "if_range",
        "if_unmodified_since",
        "max_forwards",
        "pragma",
        "proxy_authorization",
        "range",
        "referer",
        "te",
        "upgrade",
        "user_agent",
        "vary",
        "via"
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