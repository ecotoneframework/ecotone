<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Service Name
    |--------------------------------------------------------------------------
    |
    | If you are running distributed services (microservices) and want to use
    | Ecotone's capabilities for integration, then provide a name for the
    | service / application.
    |
    */
    'serviceName' => env('ECOTONE_SERVICE_NAME'),

    /*
    |--------------------------------------------------------------------------
    | Load Namespaces
    |--------------------------------------------------------------------------
    |
    | Whether or not Ecotone should automatically load all namespaces.
    |
    */
    'loadAppNamespaces' => true,

    /*
    |--------------------------------------------------------------------------
    | Namespaces
    |--------------------------------------------------------------------------
    |
    | A list of namespaces that Ecotone should look in for configurations,
    | command handlers, aggregates, projections, etc.
    |
    */
    'namespaces' => [],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Determines whether or not Ecotone should cache the configuration.
    |
    | If true, then Ecotone will cache all configurations. This will decrease
    | application load time, but results in slower feedback for the developer
    | as the cache will need to be cleared after changes are made. This is
    | best suited for production.
    |
    | If false, then Ecotone will not cache any configurations. This will
    | increase application load time, but results in quicker feedback for the
    | developer. This is best suited for local development.
    |
    */
    'cacheConfiguration' => env('ECOTONE_CACHE', false),

    /*
    |--------------------------------------------------------------------------
    | Default Serialization Media Type
    |--------------------------------------------------------------------------
    |
    | Describes the default serialization type within the application. If not
    | configured, the default serialization will be application/x-php-serialized,
    | which is a serialized PHP class.
    |
    */
    'defaultSerializationMediaType' => env('ECOTONE_DEFAULT_SERIALIZATION_TYPE'),

    /*
    |--------------------------------------------------------------------------
    | Default Error Channel
    |--------------------------------------------------------------------------
    |
    | Provides the default Poller configuration with an error channel for all
    | asynchronous consumers.
    |
    */
    'defaultErrorChannel' => env('ECOTONE_DEFAULT_ERROR_CHANNEL'),

    /*
    |--------------------------------------------------------------------------
    | Default Connection Exception Retry
    |--------------------------------------------------------------------------
    |
    | Provides the default connection retry strategy for asynchronous
    | consumers in case of connection failure.
    |
    | initialDelay - delay after first retry in milliseconds
    | multiplier - how much initialDelay should be multiplied with each try
    | maxAttempts - how many attempts should be made before closing the endpoint
    |
    */
    'defaultConnectionExceptionRetry' => null,
];
