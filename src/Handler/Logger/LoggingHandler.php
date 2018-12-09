<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Logger;

use SimplyCodedSoftware\IntegrationMessaging\Conversion\ConversionService;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class LoggingHandler
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Logger
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