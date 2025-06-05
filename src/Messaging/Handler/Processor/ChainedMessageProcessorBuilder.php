<?php

declare(strict_types=1);

/*
 * licence Apache-2.0
 */

namespace Ecotone\Messaging\Handler\Processor;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\MethodInterceptorsConfiguration;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\MessageProcessor;

class ChainedMessageProcessorBuilder implements CompilableBuilder
{
    private ?InterceptedMessageProcessorBuilder $interceptedProcessor = null;

    private function __construct(private array $processors = [])
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function chain(CompilableBuilder $processor): self
    {
        $this->processors[] = $processor;

        return $this;
    }

    public function chainInterceptedProcessor(InterceptedMessageProcessorBuilder $processor): self
    {
        if ($this->interceptedProcessor) {
            throw ConfigurationException::create('Intercepted processor is already set');
        }
        $this->interceptedProcessor = $processor;

        return $this->chain($processor);
    }

    public function getInterceptedInterface(): ?InterfaceToCallReference
    {
        return $this->interceptedProcessor?->getInterceptedInterface();
    }

    public function compileProcessor(MessagingContainerBuilder $builder, MethodInterceptorsConfiguration $interceptorsConfiguration): Definition|Reference
    {
        $compiledProcessors = [];
        // register before interceptors
        foreach ($interceptorsConfiguration->getBeforeInterceptors() as $beforeInterceptor) {
            $compiledProcessors[] = $beforeInterceptor;
        }
        foreach ($this->processors as $processor) {
            if ($processor === $this->interceptedProcessor) {
                $compiledProcessors[] = $processor->compile($builder, $interceptorsConfiguration->getAroundInterceptors());
                // register after interceptors
                foreach ($interceptorsConfiguration->getAfterInterceptors() as $afterInterceptor) {
                    $compiledProcessors[] = $afterInterceptor;
                }
            } else {
                $compiledProcessors[] = $processor->compile($builder);
            }
        }
        foreach ($compiledProcessors as $compiledProcessor) {
            if ($compiledProcessor instanceof Definition
                && ! is_a($compiledProcessor->getClassName(), MessageProcessor::class, true)) {
                throw ConfigurationException::create('Processor should implement ' . MessageProcessor::class . " interface, but got {$compiledProcessor->getClassName()}");
            }
        }

        return match(count($compiledProcessors)) {
            0 => throw ConfigurationException::create('At least one processor should be provided'),
            1 => $compiledProcessors[0],
            default => new Definition(ChainedMessageProcessor::class, [$compiledProcessors])
        };
    }

    private array $annotations = [];
    public function withEndpointAnnotations(array $annotations): self
    {
        $self = clone $this;
        $self->annotations = $annotations;

        return $self;
    }

    private iterable $requiredInterceptorNames = [];
    public function withRequiredInterceptorNames(array $requiredInterceptorNames): self
    {
        $self = clone $this;
        $self->requiredInterceptorNames = $requiredInterceptorNames;

        return $self;
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        $interceptedInterface = $this->getInterceptedInterface();
        $interceptorsConfiguration = $interceptedInterface
            ? $builder->getRelatedInterceptors(
                $interceptedInterface,
                $this->annotations,
                $this->requiredInterceptorNames,
            )
            : MethodInterceptorsConfiguration::createEmpty();

        return $this->compileProcessor($builder, $interceptorsConfiguration);
    }
}
