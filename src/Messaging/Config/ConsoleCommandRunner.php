<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Gateway\MessagingEntrypointWithHeadersPropagation;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * licence Apache-2.0
 */
class ConsoleCommandRunner
{
    public function __construct(private MessagingEntrypointWithHeadersPropagation $entrypoint, private ConsoleCommandConfiguration $commandConfiguration)
    {
    }

    public function run(array $parameters): mixed
    {
        $arguments = [];

        foreach ($parameters as $argumentName => $value) {
            if ($argumentName === ConsoleCommandConfiguration::HEADER_PARAMETER_NAME) {
                Assert::isIterable($value, 'Header parameter should be iterable');

                foreach ($value as $header) {
                    $result = explode(':', $header);
                    if (count($result) !== 2) {
                        throw InvalidArgumentException::create("Invalid header format. Should be in format 'headerName:headerValue'");
                    }
                    [$headerName, $headerValue] = $result;

                    $arguments[$headerName] = $headerValue;
                }

                continue;
            }
            if (! $this->hasParameterWithGivenName($argumentName)) {
                continue;
            }

            $arguments[$this->commandConfiguration->getHeaderNameForParameterName($argumentName)] = $value;
        }
        foreach ($this->commandConfiguration->getParameters() as $commandParameter) {
            if (
                ! array_key_exists($this->commandConfiguration->getHeaderNameForParameterName($commandParameter->getName()), $arguments)
                && $commandParameter->getName() !== ConsoleCommandConfiguration::HEADER_PARAMETER_NAME
            ) {
                if (! $commandParameter->hasDefaultValue()) {
                    throw InvalidArgumentException::create("Missing argument with name {$commandParameter->getName()} for console command {$this->commandConfiguration->getName()}");
                }

                $arguments[$this->commandConfiguration->getHeaderNameForParameterName($commandParameter->getName())] = $commandParameter->getDefaultValue();
            }
        }

        return $this->entrypoint->sendWithHeaders([], $arguments, $this->commandConfiguration->getChannelName());
    }

    private function hasParameterWithGivenName(int|string $argumentName): bool
    {
        foreach ($this->commandConfiguration->getParameters() as $commandParameter) {
            if ($commandParameter->getName() === $argumentName) {
                return true;
            }
        }

        return false;
    }
}
