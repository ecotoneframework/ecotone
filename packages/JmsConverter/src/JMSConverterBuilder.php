<?php

namespace Ecotone\JMSConverter;

use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\ConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\Naming\CamelCaseNamingStrategy;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializerBuilder;

class JMSConverterBuilder implements ConverterBuilder
{
    /**
     * @var JMSHandlerAdapter[]
     */
    private array $converterHandlers;
    private JMSConverterConfiguration $jmsConverterConfiguration;
    private ?string $cacheDirectoryPath;

    public function __construct(array $converterHandlers, JMSConverterConfiguration $JMSConverterConfiguration, ?string $cacheDirectoryPath)
    {
        $this->converterHandlers = $converterHandlers;
        $this->jmsConverterConfiguration = $JMSConverterConfiguration;
        $this->cacheDirectoryPath = $cacheDirectoryPath;
    }

    public function build(ReferenceSearchService $referenceSearchService): Converter
    {
        $builder = SerializerBuilder::create()
            ->setPropertyNamingStrategy(
                $this->jmsConverterConfiguration->getNamingStrategy() === $this->jmsConverterConfiguration::IDENTICAL_PROPERTY_NAMING_STRATEGY
                    ? new IdenticalPropertyNamingStrategy()
                    : new CamelCaseNamingStrategy()
            )
            ->configureHandlers(function (HandlerRegistry $registry) use ($referenceSearchService) {
                foreach ($this->converterHandlers as $converterHandler) {
                    $registry->registerHandler(
                        $converterHandler->getDirection(),
                        $converterHandler->getRelatedClass(),
                        'json',
                        $converterHandler->getSerializerClosure($referenceSearchService)
                    );
                    $registry->registerHandler(
                        $converterHandler->getDirection(),
                        $converterHandler->getRelatedClass(),
                        'xml',
                        $converterHandler->getSerializerClosure($referenceSearchService)
                    );
                }
            });

        if ($this->cacheDirectoryPath) {
            $builder->setCacheDir($this->cacheDirectoryPath . DIRECTORY_SEPARATOR . 'jms');
        }

        $builder->setDocBlockTypeResolver(true);

        return new JMSConverter($builder->build(), $this->jmsConverterConfiguration);
    }

    public function getRequiredReferences(): array
    {
        return [];
    }
}
