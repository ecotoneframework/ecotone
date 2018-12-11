<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Logger;

use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class LoggingHandler
 * @package SimplyCodedSoftware\Messaging\Handler\Logger
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LoggingHandler implements MessageHandler
{
    /**
     * @var ConversionService
     */
    private $conversionService;

    /**
     * LoggingHandler constructor.
     * @param ConversionService $conversionService
     */
    public function __construct(ConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        echo "\n" . (string)$message . "\n";
    }
}