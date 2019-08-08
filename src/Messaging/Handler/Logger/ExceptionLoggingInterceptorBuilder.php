<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Handler\Logger;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorObjectBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Class ExceptionLoggingInterceptorBuilder
 * @package Ecotone\Messaging\Handler\Logger
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ExceptionLoggingInterceptorBuilder implements AroundInterceptorObjectBuilder
{
    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): object
    {
        return new LoggingInterceptor(
                new LoggingService(
                    $referenceSearchService->get(ConversionService::REFERENCE_NAME),
                    $referenceSearchService->get(LoggingHandlerBuilder::LOGGER_REFERENCE)
                )
            );
    }

    /**
     * @inheritDoc
     */
    public function getInterceptingInterfaceClassName(): string
    {
        return LoggingInterceptor::class;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [LoggingHandlerBuilder::LOGGER_REFERENCE];
    }
}