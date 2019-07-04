<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Messaging\Handler\Logger;

use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorObjectBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class ExceptionLoggingInterceptorBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Logger
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
                    $referenceSearchService->get(self::LOGGER_REFERENCE)
                )
            );
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [LoggingHandlerBuilder::LOGGER_REFERENCE];
    }
}